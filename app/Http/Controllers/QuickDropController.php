<?php

namespace App\Http\Controllers;

use App\Models\UploadRequest;
use App\Services\DownloadService;
use App\Services\QuickDropService;
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
                'defaults'           => config('quickdrop.defaults'),
                'reference_number'   => config('quickdrop.reference_number'),
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
                auth()->id(),
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

            return to_route('quickdrop.show', [
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
            return back()->withErrors(['error' => 'Failed to create QuickDrop box']);
        }
    }

    protected function buildReferenceValidation(): array
    {
        if (!config('quickdrop.reference_number.enabled')) {
            return ['nullable', 'string'];
        }

        return [
            config('quickdrop.reference_number.required') ? 'required' : 'nullable',
            'string',
            'min:'.config('quickdrop.reference_number.validation.min_length'),
            'max:'.config('quickdrop.reference_number.validation.max_length'),
            'regex:/'.config('quickdrop.reference_number.validation.pattern').'/',
        ];
    }

    protected function createValidator(Request $request, array $referenceValidation)
    {
        return Validator::make($request->all(), [
            'title'                 => 'required|string|max:255',
            'comment'               => 'nullable|string|max:1000',
            'reference_number'      => $referenceValidation,
            'expires_in_minutes'    => 'nullable|integer|min:5|max:'.collect(config('quickdrop.expiration_options'))->max('value'),
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
        \Log::debug('QuickDrop: Starting upload process', [
            'request_id' => $unique_request_id,
            'file_name'  => $request->file('file')?->getClientOriginalName(),
            'file_size'  => $request->file('file')?->getSize(),
            'file_hash'  => $request->input('file_hash'),
        ]);

        $uploadRequest = UploadRequest::where('unique_request_id', $unique_request_id)
            ->where('status', 'active')
            ->firstOrFail();

        if ($uploadRequest->isExpired()) {
            \Log::debug('QuickDrop: Upload request expired', ['request_id' => $unique_request_id]);

            return back()->withErrors(['error' => 'Upload request has expired']);
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

                \Log::debug('QuickDrop: Creating new version', [
                    'name'             => $fileName,
                    'new_version'      => $nextVersion,
                    'original_file_id' => $originalFileId,
                    'request_id'       => $uploadRequest->id,
                ]);
            }

            $uploadObject = $this->quickDropService->handleFileUpload(
                $uploadRequest,
                $request->file('file'),
                $uploadRequest->requesting_user_id,
                $nextVersion,
                $originalFileId
            );

            // Return the file data with version information
            return back()->with('file', [
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
            ]);
        } catch (\Exception $e) {
            \Log::error('QuickDrop: Upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function validateUploadRequest(Request $request, UploadRequest $uploadRequest)
    {
        $request->validate([
            'file' => ['required', 'file', function ($attribute, $value, $fail) use ($uploadRequest) {
                if ($uploadRequest->max_file_size && $value->getSize() > $uploadRequest->max_file_size) {
                    \Log::debug('QuickDrop: File size validation failed', [
                        'size'     => $value->getSize(),
                        'max_size' => $uploadRequest->max_file_size,
                    ]);
                    $fail('File size exceeds the maximum allowed size of '.
                        number_format($uploadRequest->max_file_size / 1024 / 1024, 2).' MB');
                }

                if ($uploadRequest->allowed_mime_types &&
                    !in_array($value->getMimeType(), $uploadRequest->allowed_mime_types)) {
                    \Log::debug('QuickDrop: File type validation failed', [
                        'type'          => $value->getMimeType(),
                        'allowed_types' => $uploadRequest->allowed_mime_types,
                    ]);
                    $fail('File type not allowed. Allowed types: '.implode(', ', $uploadRequest->allowed_mime_types));
                }
            }],
            'verification_token' => ['required', 'string', function ($attribute, $value, $fail) use ($uploadRequest) {
                if ($value !== $uploadRequest->verification_token) {
                    \Log::debug('QuickDrop: Invalid verification token');
                    $fail('Invalid verification token.');
                }
            }],
        ]);
    }

    public function showQuickDrop(string $unique_request_id)
    {
        $uploadRequest = UploadRequest::where('unique_request_id', $unique_request_id)
            ->with('uploadObjects')
            ->firstOrFail();

        $data = $this->prepareUploadRequestData($uploadRequest);
        $isOwner = auth()->check() && auth()->id() === $uploadRequest->requesting_user_id;

        if ($isOwner) {
            $data['files'] = $this->prepareFilesData($uploadRequest->uploadObjects);

            return $this->renderOwnerView($data);
        }

        return $this->renderPublicView($data);
    }

    protected function prepareUploadRequestData(UploadRequest $uploadRequest): array
    {
        $isOwner = auth()->check() && auth()->id() === $uploadRequest->requesting_user_id;
        $data = [
            'id'                    => $uploadRequest->id,
            'unique_request_id'     => $uploadRequest->unique_request_id,
            'title'                 => $uploadRequest->title,
            'verification_token'    => $uploadRequest->verification_token,
            'expires_at'            => $uploadRequest->expires_at,
            'is_expired'            => $uploadRequest->isExpired(),
            'is_active'             => $uploadRequest->isActive(),
            'max_file_size'         => $uploadRequest->max_file_size ?? config('quickdrop.max_file_size'),
            'max_files'             => $uploadRequest->max_files ?? config('quickdrop.max_files'),
            'allowed_mime_types'    => $uploadRequest->allowed_mime_types ?? config('quickdrop.allowed_mime_types'),
            'is_encrypted'          => $uploadRequest->is_encrypted,
            'key_verification_hash' => $uploadRequest->key_verification_hash,
        ];

        if ($isOwner) {
            $data['allow_public_download'] = $uploadRequest->allow_public_download;
            $data['allow_public_delete'] = $uploadRequest->allow_public_delete;
            $data['allow_public_upload'] = $uploadRequest->allow_public_upload;
        } else {
            $data['can_download'] = $uploadRequest->allow_public_download;
            $data['can_delete'] = $uploadRequest->allow_public_delete;
            $data['can_upload'] = $uploadRequest->allow_public_upload;
        }

        return $data;
    }

    protected function prepareFilesData($uploadObjects): array
    {
        return $uploadObjects->map(fn ($obj) => [
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
        ])->toArray();
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

    protected function renderPublicView(array $data)
    {
        $files = [];
        if ($data['can_download'] ?? false) {
            $uploadRequest = UploadRequest::where('unique_request_id', $data['unique_request_id'])
                ->with('uploadObjects')
                ->firstOrFail();
            $files = $this->prepareFilesData($uploadRequest->uploadObjects);
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
        $uploadRequests = UploadRequest::where('requesting_user_id', auth()->id())
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
            'upload_url'         => route('quickdrop.show', ['unique_request_id' => $request->unique_request_id]),
        ];
    }
}
