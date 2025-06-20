<?php

namespace App\Http\Controllers;

use App\Models\UploadRequest;
use App\Services\DownloadService;
use App\Services\QuickDropService;
use App\Services\AuditService;
use App\Services\ShareAnalyticsService;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class QuickDropController extends Controller
{
    public function __construct(
        protected readonly QuickDropService $quickDropService,
        protected readonly DownloadService $downloadService
    ) {
    }

    public function create()
    {
        return Inertia::render('QuickDropCreate', [
            'config' => [
                'allowed_mime_types' => config('quickdrop.allowed_mime_types'),
                'expiration_options' => config('quickdrop.expiration_options'),
                'file_size_options'  => config('quickdrop.file_size_options'),
                'max_files_options'  => config('quickdrop.max_files_options'),
                'defaults'           => [
                    'expiration' => settings('default_expiry_hours', 24) * 60, // Convert hours to minutes
                ],
                'reference_number'   => [
                    'enabled' => true,
                    'required' => settings('require_reference_number', false),
                    'pattern' => settings('reference_number_pattern', '/^[A-Za-z0-9\-]+$/'),
                ],
                'max_file_size' => settings('max_file_size_mb', 100) * 1024 * 1024, // Convert MB to bytes
                'max_files_per_request' => settings('max_files_per_quickdrop', 10),
            ],
        ]);
    }

    public function createQuickDrop(Request $request)
    {
        try {
            $referenceValidation = $this->buildReferenceValidation();
            $validator = $this->createValidator($request, $referenceValidation);

            if ($validator->fails()) {
                return back()->withErrors($validator);
            }

            $uploadRequest = $this->quickDropService->createUploadRequest(
                auth()->guard('quickdrop')->id(),
                $request->input('title'),
                $request->input('comment'),
                $request->input('reference_number'),
                $request->input('expires_in_minutes', 1440),
                $request->input('use_encryption', false),
                $request->input('key_verification_hash'),
                $request->input('allow_public_download', false),
                $request->input('allow_public_delete', false),
                $request->input('allow_public_upload', true)
            );

            // Log QuickDrop creation
            AuditService::logQuickDrop(
                AuditLog::EVENT_CREATE,
                "Created QuickDrop: {$uploadRequest->title}",
                $uploadRequest->id,
                [
                    'title' => $uploadRequest->title,
                    'is_encrypted' => $request->input('use_encryption', false),
                    'expires_in_minutes' => $request->input('expires_in_minutes', 1440),
                    'reference_number' => $request->input('reference_number'),
                ]
            );

            return to_route('quickdrop.public', [
                'unique_request_id' => $uploadRequest->unique_request_id,
            ])->with([
                'uploadRequest' => [
                    'id'                => $uploadRequest->id,
                    'unique_request_id' => $uploadRequest->unique_request_id,
                    'is_encrypted'      => $request->input('use_encryption', false),
                ],
                'showNewBoxMessage' => true,
                'encryptionKey'     => $request->input('use_encryption', false),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create QuickDrop box', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Failed to create QuickDrop box: ' . $e->getMessage()]);
        }
    }

    protected function buildReferenceValidation(): array
    {
        $required = settings('require_reference_number', false);
        $pattern = settings('reference_number_pattern', '/^[A-Za-z0-9\-]+$/');

        return [
            $required ? 'required' : 'nullable',
            'string',
            'min:3',
            'max:50',
            "regex:{$pattern}",
        ];
    }

    protected function createValidator(Request $request, array $referenceValidation)
    {
        return Validator::make($request->all(), [
            'title'                 => 'required|string|max:255',
            'comment'               => 'nullable|string|max:1000',
            'reference_number'      => $referenceValidation,
            'expires_in_minutes'    => 'nullable|integer|min:5|max:43200', // 30 days max
            'use_encryption'        => 'nullable|boolean',
            'key_verification_hash' => 'required_if:use_encryption,true|nullable|string',
            'allow_public_download' => 'boolean',
            'allow_public_delete'   => 'boolean',
            'allow_public_upload'   => 'boolean',
        ], [
            'reference_number.regex' => config('quickdrop.reference_number.validation.error_message'),
        ]);
    }

    public function upload(Request $request, string $unique_request_id)
    {
        $uploadRequest = UploadRequest::where('unique_request_id', $unique_request_id)
            ->where('status', 'active')
            ->firstOrFail();

        if ($uploadRequest->isExpired()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Upload request has expired'], 403);
            }
            return back()->withErrors(['error' => 'Upload request has expired']);
        }

        // Check if public uploads are allowed
        if (!$uploadRequest->allow_public_upload) {
            // Check if the current user is the owner
            $currentUser = auth()->guard('quickdrop')->user();
            if (!$currentUser || $currentUser->id !== $uploadRequest->quickdrop_user_id) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Public uploads are not allowed for this request'], 403);
                }
                return back()->withErrors(['error' => 'Public uploads are not allowed for this QuickDrop']);
            }
        }

        // Validate the upload request
        $this->validateUploadRequest($request, $uploadRequest);

        try {
            $fileHash = $request->input('file_hash');
            $fileName = $request->file('file')->getClientOriginalName();

            // First check for exact duplicates (same hash AND name) within this request
            $exactDuplicate = $uploadRequest->uploadObjects()
                ->where('file_hash', $fileHash)
                ->where('original_name', $fileName)
                ->exists();

            if ($exactDuplicate) {
                \Log::info('Skipping exact duplicate file upload', [
                    'name'       => $fileName,
                    'file_hash'  => $fileHash,
                    'request_id' => $uploadRequest->id,
                ]);

                return response()->json([
                    'message'   => 'Exact duplicate file already exists in this request',
                    'duplicate' => true,
                    'type'      => 'exact',
                ], 200);
            }

            // Check for name duplicates to handle versioning
            $existingFile = $uploadRequest->uploadObjects()
                ->where('original_name', $fileName)
                ->orderBy('version', 'desc')
                ->first();

            $nextVersion = 1;
            $originalFileId = null;

            if ($existingFile) {
                $nextVersion = $existingFile->version + 1;
                $originalFileId = $existingFile->original_file_id ?? $existingFile->id;

            }

            $uploadObject = $this->quickDropService->handleFileUpload(
                $uploadRequest,
                $request->file('file'),
                $uploadRequest->quickdrop_user_id,
                $nextVersion,
                $originalFileId
            );

            // Log file upload
            AuditService::logFile(
                AuditLog::EVENT_UPLOAD,
                "Uploaded file: {$uploadObject->original_name}",
                $uploadObject->id,
                [
                    'quickdrop_id' => $uploadRequest->id,
                    'file_name' => $uploadObject->original_name,
                    'file_size' => $uploadObject->file_size,
                    'version' => $uploadObject->version,
                    'is_encrypted' => $uploadObject->is_encrypted,
                ]
            );

            // Track upload for analytics
            ShareAnalyticsService::trackUpload($uploadRequest);

            // Send upload completion notification
            if ($uploadRequest->quickdropUser) {
                app(\App\Services\EmailNotificationService::class)->sendUploadCompleteNotification($uploadObject);
            }

            // Return the file data with version information
            $fileData = [
                'id'                => $uploadObject->unique_id,
                'name'              => $uploadObject->original_name,
                'size'              => $uploadObject->file_size,
                'type'              => $uploadObject->mime_type,
                'version'           => $uploadObject->version,
                'file_hash'         => $uploadObject->file_hash,
                'request_id'        => $uploadRequest->id,
                'uploaded_at'       => $uploadObject->created_at,
                'is_latest_version' => $this->isLatestVersion($uploadObject),
                'is_duplicate'      => $existingFile !== null,
                'duplicate_type'    => $existingFile ? 'name' : null,
            ];

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'file' => $fileData]);
            }

            return back()->with('file', $fileData);
        } catch (\Exception $e) {
            \Log::error('QuickDrop: Upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function validateUploadRequest(Request $request, UploadRequest $uploadRequest)
    {
        $request->validate([
            'file' => ['required', 'file', function ($attribute, $value, $fail) use ($uploadRequest) {
                if ($uploadRequest->max_file_size && $value->getSize() > $uploadRequest->max_file_size) {
                    $fail('File size exceeds the maximum allowed size of '.
                        number_format($uploadRequest->max_file_size / 1024 / 1024, 2).' MB');
                }

                if ($uploadRequest->allowed_mime_types &&
                    !in_array($value->getMimeType(), $uploadRequest->allowed_mime_types)) {
                    $fail('File type not allowed. Allowed types: '.implode(', ', $uploadRequest->allowed_mime_types));
                }
            }],
            'verification_token' => ['required', 'string', function ($attribute, $value, $fail) use ($uploadRequest) {
                if ($value !== $uploadRequest->verification_token) {
                    $fail('Invalid verification token.');
                }
            }],
        ]);
    }

    public function showQuickDrop(Request $request, string $unique_request_id)
    {
        $uploadRequest = UploadRequest::where('unique_request_id', $unique_request_id)
            ->with('uploadObjects')
            ->firstOrFail();

        // Check if the QuickDrop is expired and return 404 if it is
        if ($uploadRequest->isExpired()) {
            abort(404, 'This QuickDrop has expired.');
        }

        // Check if the QuickDrop is inactive and return 404 if it is
        if (!$uploadRequest->is_active) {
            abort(404, 'This QuickDrop is no longer active.');
        }

        // Track view for analytics - wrapped in try-catch to prevent test failures
        try {
            ShareAnalyticsService::trackView($uploadRequest, $request);
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            \Log::error('Failed to track view: ' . $e->getMessage());
        }

        $data = $this->prepareUploadRequestData($uploadRequest);
        $isOwner = auth()->guard('quickdrop')->check() && 
                   auth()->guard('quickdrop')->id() === $uploadRequest->quickdrop_user_id;

        if ($isOwner) {
            $data['files'] = $this->prepareFilesData($uploadRequest->uploadObjects);

            return $this->renderOwnerView($data);
        }

        return $this->renderPublicView($data, $uploadRequest->uploadObjects);
    }

    // Verify reference number for public access
    public function verifyReferenceNumber(Request $request, string $unique_request_id)
    {
        $uploadRequest = UploadRequest::where('unique_request_id', $unique_request_id)->firstOrFail();
        
        $request->validate([
            'reference_number' => 'required|string',
        ]);
        
        if ($uploadRequest->reference_number !== $request->reference_number) {
            // For web requests, redirect back with error
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Invalid reference number'], 403);
            }
            return back()->withErrors(['reference_number' => 'Invalid reference number']);
        }
        
        // Store in session for subsequent access
        session(['quickdrop_access.' . $unique_request_id => true]);
        
        // For web requests, redirect back
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('quickdrop.public', $unique_request_id);
    }

    protected function prepareUploadRequestData(UploadRequest $uploadRequest): array
    {
        $isOwner = auth()->guard('quickdrop')->check() && 
                   auth()->guard('quickdrop')->id() === $uploadRequest->quickdrop_user_id;
        $data = [
            'id'                    => $uploadRequest->id,
            'unique_request_id'     => $uploadRequest->unique_request_id,
            'title'                 => $uploadRequest->title,
            'comment'               => $uploadRequest->comment,
            'verification_token'    => $uploadRequest->verification_token,
            'expires_at'            => $uploadRequest->expires_at,
            'is_expired'            => $uploadRequest->isExpired(),
            'is_active'             => $uploadRequest->isActive(),
            'max_file_size'         => $uploadRequest->max_file_size ?? (settings('max_file_size_mb', 100) * 1024 * 1024),
            'max_files'             => $uploadRequest->max_files ?? settings('max_files_per_quickdrop', 10),
            'allowed_mime_types'    => $uploadRequest->allowed_mime_types ?? config('quickdrop.allowed_mime_types'),
            'is_encrypted'          => $uploadRequest->is_encrypted,
            'key_verification_hash' => $uploadRequest->key_verification_hash,
        ];

        // Add reference number requirement info
        $data['requires_reference_number'] = !empty($uploadRequest->reference_number);
        $data['reference_number'] = $isOwner ? $uploadRequest->reference_number : null;

        if ($isOwner) {
            $data['allow_public_download'] = $uploadRequest->allow_public_download;
            $data['allow_public_delete'] = $uploadRequest->allow_public_delete;
            $data['allow_public_upload'] = $uploadRequest->allow_public_upload;
        } else {
            $data['can_download'] = $uploadRequest->allow_public_download;
            $data['can_delete'] = $uploadRequest->allow_public_delete;
            $data['can_upload'] = $uploadRequest->allow_public_upload;
            // Also include allow_public_upload for public view
            $data['allow_public_upload'] = $uploadRequest->allow_public_upload;
        }

        return $data;
    }

    protected function prepareFilesData($uploadObjects): array
    {
        return $uploadObjects->map(function ($obj) {
            $uploadRequest = $obj->uploadRequests->first();
            $data = [
                'id'                => $obj->unique_id,
                'name'              => $obj->original_name,
                'size'              => $obj->file_size,
                'type'              => $obj->mime_type,
                'version'           => $obj->version,
                'file_hash'         => $obj->file_hash,
                'hash'              => $obj->file_hash,
                'request_id'        => $obj->pivot->upload_request_id,
                'uploaded_at'       => $obj->created_at,
                'is_encrypted'      => $obj->is_encrypted,
                'unique_id'         => $obj->unique_id,
                'is_latest_version' => $this->isLatestVersion($obj),
                'created_at'        => $obj->created_at->toIso8601String(),
            ];

            // Add preview URLs for supported file types
            if ($uploadRequest && !$obj->is_encrypted) {
                // Add preview URL for previewable files
                if ($this->isPreviewable($obj->mime_type)) {
                    $data['url'] = route('preview.file', [
                        'requestId' => $uploadRequest->unique_request_id,
                        'fileUuid' => $obj->unique_id,
                    ]);
                }

                // Add thumbnail URL for images
                if (str_starts_with($obj->mime_type, 'image/')) {
                    $data['thumbnail_url'] = route('preview.thumbnail', [
                        'requestId' => $uploadRequest->unique_request_id,
                        'fileUuid' => $obj->unique_id,
                    ]);
                }
            }

            return $data;
        })->toArray();
    }

    protected function isPreviewable($mimeType): bool
    {
        $previewableTypes = [
            'image/',
            'video/',
            'audio/',
            'application/pdf',
            'text/',
            'application/json',
            'application/xml',
            'application/javascript',
        ];

        foreach ($previewableTypes as $type) {
            if (str_contains($mimeType, $type)) {
                return true;
            }
        }

        return false;
    }

    protected function isLatestVersion($uploadObject): bool
    {
        // Only check versions within the same request
        return !$uploadObject->original_file_id ||
            ($uploadObject->version >= $uploadObject->uploadRequest->uploadObjects()
                ->where('original_name', $uploadObject->original_name)
                ->max('version'));
    }

    protected function renderOwnerView(array $data)
    {
        return Inertia::render('QuickDrop', [
            'uploadRequest'     => $data,
            'canViewFiles'      => true,
            'showNewBoxMessage' => session('showNewBoxMessage', false),
            'encryptionKey'     => session('encryptionKey', false),
            'files'             => $data['files'] ?? [],
        ]);
    }

    protected function renderPublicView(array $data, $uploadObjects = null)
    {
        $files = [];
        if (($data['can_download'] ?? false) && $uploadObjects) {
            $files = $this->prepareFilesData($uploadObjects);
        }

        return Inertia::render('PublicQuickDrop', [
            'uploadRequest'     => $data,
            'canViewFiles'      => false,
            'showNewBoxMessage' => session('showNewBoxMessage', false),
            'encryptionKey'     => session('encryptionKey', false),
            'files'             => $files,
        ]);
    }

    public function index()
    {
        $uploadRequests = UploadRequest::where('quickdrop_user_id', auth()->guard('quickdrop')->id())
            ->with('uploadObjects')
            ->latest()
            ->get()
            ->map(fn ($request) => $this->prepareUploadRequestListData($request));

        return Inertia::render('QuickDropList', [
            'uploadRequests' => $uploadRequests,
        ]);
    }

    protected function prepareUploadRequestListData(UploadRequest $request): array
    {
        return [
            'id'                 => $request->unique_request_id,
            'title'              => $request->title,
            'created_at'         => $request->created_at,
            'expires_at'         => $request->expires_at,
            'is_expired'         => $request->isExpired(),
            'is_active'          => $request->isActive(),
            'max_file_size'      => $request->max_file_size,
            'max_files'          => $request->max_files,
            'allowed_mime_types' => $request->allowed_mime_types,
            'is_encrypted'       => $request->is_encrypted,
            'files_count'        => $request->uploadObjects->count(),
            'total_size'         => $request->uploadObjects->sum('file_size'),
            'upload_url'         => route('quickdrop.public', ['unique_request_id' => $request->unique_request_id]),
        ];
    }

    public function generateShareLink(string $unique_request_id)
    {
        $uploadRequest = UploadRequest::where('unique_request_id', $unique_request_id)
            ->where('quickdrop_user_id', auth()->guard('quickdrop')->id())
            ->firstOrFail();

        $shareUrl = route('quickdrop.public', ['unique_request_id' => $uploadRequest->unique_request_id]);

        return response()->json([
            'shareUrl' => $shareUrl,
            'title' => $uploadRequest->title,
            'expiresAt' => $uploadRequest->expires_at,
            'isEncrypted' => $uploadRequest->is_encrypted,
            'isExpired' => $uploadRequest->isExpired(),
            'fileCount' => $uploadRequest->uploadObjects()->count(),
            'requiresReferenceNumber' => !empty($uploadRequest->reference_number),
            'isActive' => $uploadRequest->is_active,
            'permissions' => [
                'upload' => $uploadRequest->allow_public_upload,
                'download' => $uploadRequest->allow_public_download,
                'delete' => $uploadRequest->allow_public_delete,
            ],
        ]);
    }

    public function analytics(Request $request, string $unique_request_id)
    {
        $uploadRequest = UploadRequest::where('unique_request_id', $unique_request_id)
            ->where('quickdrop_user_id', auth()->guard('quickdrop')->id())
            ->firstOrFail();

        $days = $request->get('days', 7);
        $analytics = ShareAnalyticsService::getAnalytics($uploadRequest->id, $days);

        return response()->json($analytics);
    }

    public function showAnalytics(string $unique_request_id)
    {
        $uploadRequest = UploadRequest::where('unique_request_id', $unique_request_id)
            ->where('quickdrop_user_id', auth()->guard('quickdrop')->id())
            ->firstOrFail();

        return Inertia::render('ShareAnalytics', [
            'uploadRequest' => [
                'id' => $uploadRequest->unique_request_id,
                'title' => $uploadRequest->title,
                'created_at' => $uploadRequest->created_at,
                'expires_at' => $uploadRequest->expires_at,
                'is_expired' => $uploadRequest->isExpired(),
                'is_encrypted' => $uploadRequest->is_encrypted,
            ],
        ]);
    }
}
