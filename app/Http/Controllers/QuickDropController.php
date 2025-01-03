<?php

namespace App\Http\Controllers;

use App\Models\UploadRequest;
use App\Services\QuickDropService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class QuickDropController extends Controller
{
    protected QuickDropService $quickDropService;

    public function __construct(QuickDropService $quickDropService)
    {
        $this->quickDropService = $quickDropService;
    }

    public function create()
    {
        return Inertia::render('QuickDropCreate', [
            'config' => [
                'allowed_mime_types' => config('quickdrop.allowed_mime_types'),
                'expiration_options' => config('quickdrop.expiration_options'),
                'file_size_options' => config('quickdrop.file_size_options'),
                'max_files_options' => config('quickdrop.max_files_options'),
                'defaults' => config('quickdrop.defaults'),
                'reference_number' => config('quickdrop.reference_number'),
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
                $request->input('key_verification_hash')
            );

            return to_route('quickdrop.show', [
                'unique_request_id' => $uploadRequest->unique_request_id,
            ])->with([
                'uploadRequest' => [
                    'id' => $uploadRequest->id,
                    'unique_request_id' => $uploadRequest->unique_request_id,
                    'is_encrypted' => $request->input('use_encryption', false),
                ],
                'showNewBoxMessage' => true,
                'encryptionKey' => $request->input('use_encryption', false)
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
            'min:' . config('quickdrop.reference_number.validation.min_length'),
            'max:' . config('quickdrop.reference_number.validation.max_length'),
            'regex:/' . config('quickdrop.reference_number.validation.pattern') . '/',
        ];
    }

    protected function createValidator(Request $request, array $referenceValidation)
    {
        return Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'comment' => 'nullable|string|max:1000',
            'reference_number' => $referenceValidation,
            'expires_in_minutes' => 'nullable|integer|min:5|max:' . collect(config('quickdrop.expiration_options'))->max('value'),
            'use_encryption' => 'nullable|boolean',
            'key_verification_hash' => 'required_if:use_encryption,true|nullable|string',
        ], [
            'reference_number.regex' => config('quickdrop.reference_number.validation.error_message'),
        ]);
    }

    public function upload(Request $request, string $unique_request_id)
    {
        \Log::debug('QuickDrop: Starting upload process', [
            'request_id' => $unique_request_id,
            'file_name' => $request->file('file')?->getClientOriginalName(),
            'file_size' => $request->file('file')?->getSize(),
        ]);

        $uploadRequest = UploadRequest::where('unique_request_id', $unique_request_id)
            ->where('status', 'active')
            ->firstOrFail();

        if ($uploadRequest->isExpired()) {
            \Log::debug('QuickDrop: Upload request expired', ['request_id' => $unique_request_id]);
            return back()->withErrors(['error' => 'Upload request has expired']);
        }

        $request->validate([
            'file' => ['required', 'file', function ($attribute, $value, $fail) use ($uploadRequest) {
                if ($uploadRequest->max_file_size && $value->getSize() > $uploadRequest->max_file_size) {
                    \Log::debug('QuickDrop: File size validation failed', [
                        'size' => $value->getSize(),
                        'max_size' => $uploadRequest->max_file_size
                    ]);
                    $fail('File size exceeds the maximum allowed size of ' . 
                        number_format($uploadRequest->max_file_size / 1024 / 1024, 2) . ' MB');
                }

                if ($uploadRequest->allowed_mime_types && 
                    !in_array($value->getMimeType(), $uploadRequest->allowed_mime_types)) {
                    \Log::debug('QuickDrop: File type validation failed', [
                        'type' => $value->getMimeType(),
                        'allowed_types' => $uploadRequest->allowed_mime_types
                    ]);
                    $fail('File type not allowed. Allowed types: ' . implode(', ', $uploadRequest->allowed_mime_types));
                }
            }],
            'verification_token' => ['required', 'string', function ($attribute, $value, $fail) use ($uploadRequest) {
                if ($value !== $uploadRequest->verification_token) {
                    \Log::debug('QuickDrop: Invalid verification token');
                    $fail('Invalid verification token.');
                }
            }],
        ]);

        try {
            \Log::debug('QuickDrop: Processing file upload');
            $uploadObject = $this->quickDropService->handleFileUpload(
                $uploadRequest,
                $request->file('file'),
                $uploadRequest->requesting_user_id
            );

            \Log::debug('QuickDrop: Upload successful', [
                'file_id' => $uploadObject->id,
                'file_name' => $uploadObject->original_name
            ]);

            return back()->with('file', [
                'name' => $uploadObject->original_name,
                'size' => $uploadObject->file_size,
                'type' => $uploadObject->mime_type,
                'id' => $uploadObject->unique_id,
                'uploaded_at' => $uploadObject->created_at,
            ]);
        } catch (\Exception $e) {
            \Log::error('QuickDrop: Upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function download(string $unique_id)
    {
        $uploadObject = UploadObject::with('uploadRequests')->where('unique_id', $unique_id)->firstOrFail();

        if (!$uploadObject->uploadRequests->contains('requesting_user_id', auth()->id())) {
            return back()->withErrors(['error' => 'Unauthorized access to file']);
        }

        if ($uploadObject->is_encrypted) {
            $key = request()->input('key');
            if (!$key) {
                return back()->withErrors(['error' => 'Encryption key is required']);
            }

            try {
                $content = $this->quickDropService->decryptFile($uploadObject, $key);
                return Response::make($content, 200, [
                    'Content-Type' => $uploadObject->mime_type,
                    'Content-Disposition' => 'attachment; filename="' . $uploadObject->original_name . '"',
                ]);
            } catch (\Exception $e) {
                return back()->withErrors(['error' => 'Failed to decrypt file']);
            }
        }

        return Storage::disk('quickdrops')->download(
            $uploadObject->storage_path,
            $uploadObject->original_name,
            ['Content-Type' => $uploadObject->mime_type]
        );
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
        return [
            'id' => $uploadRequest->id,
            'unique_request_id' => $uploadRequest->unique_request_id,
            'title' => $uploadRequest->title,
            'verification_token' => $uploadRequest->verification_token,
            'expires_at' => $uploadRequest->expires_at,
            'is_expired' => $uploadRequest->isExpired(),
            'is_active' => $uploadRequest->isActive(),
            'max_file_size' => $uploadRequest->max_file_size,
            'max_files' => $uploadRequest->max_files,
            'allowed_mime_types' => $uploadRequest->allowed_mime_types,
            'is_encrypted' => $uploadRequest->is_encrypted,
            'key_verification_hash' => $uploadRequest->key_verification_hash,
        ];
    }

    protected function prepareFilesData($uploadObjects): array
    {
        return $uploadObjects->map(fn($obj) => [
            'id' => $obj->unique_id,
            'name' => $obj->original_name,
            'size' => $obj->file_size,
            'type' => $obj->mime_type,
            'uploaded_at' => $obj->created_at,
        ])->toArray();
    }

    protected function renderOwnerView(array $data)
    {
        return Inertia::render('QuickDrop', [
            'uploadRequest' => $data,
            'canViewFiles' => true,
            'showNewBoxMessage' => session('showNewBoxMessage', false),
            'encryptionKey' => session('encryptionKey', false),
            'files' => $data['files'] ?? [],
        ]);
    }

    protected function renderPublicView(array $data)
    {
        return Inertia::render('PublicQuickDrop', [
            'uploadRequest' => $data,
            'canViewFiles' => false,
            'showNewBoxMessage' => session('showNewBoxMessage', false),
            'encryptionKey' => session('encryptionKey', false),
            'files' => [],
        ]);
    }

    public function index()
    {
        $uploadRequests = UploadRequest::where('requesting_user_id', auth()->id())
            ->with('uploadObjects')
            ->latest()
            ->get()
            ->map(fn($request) => $this->prepareUploadRequestListData($request));

        return Inertia::render('QuickDropList', [
            'uploadRequests' => $uploadRequests
        ]);
    }

    protected function prepareUploadRequestListData(UploadRequest $request): array
    {
        return [
            'id' => $request->unique_request_id,
            'title' => $request->title,
            'created_at' => $request->created_at,
            'expires_at' => $request->expires_at,
            'is_expired' => $request->isExpired(),
            'is_active' => $request->isActive(),
            'max_file_size' => $request->max_file_size,
            'max_files' => $request->max_files,
            'allowed_mime_types' => $request->allowed_mime_types,
            'is_encrypted' => $request->is_encrypted,
            'files_count' => $request->uploadObjects->count(),
            'total_size' => $request->uploadObjects->sum('file_size'),
            'upload_url' => route('quickdrop.show', ['unique_request_id' => $request->unique_request_id]),
        ];
    }
}
