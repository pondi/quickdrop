<?php

namespace App\Services;

use App\Models\UploadObject;
use App\Models\UploadRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuickDropService
{
    protected function generateSecureToken(): string
    {
        try {
            // Generate 32 bytes of random data (256 bits)
            $randomBytes = random_bytes(32);

            // Create a base64 URL-safe string and remove padding
            $token = rtrim(strtr(base64_encode($randomBytes), '+/', '-_'), '=');

            // Add timestamp hash to make it even more unique
            $timeHash = hash('xxh3', microtime(true).uniqid('', true));

            // Ensure we have a valid string
            if (empty($token) || empty($timeHash)) {
                throw new \Exception('Failed to generate secure token components');
            }

            return $token.$timeHash;
        } catch (\Exception $e) {
            \Log::error('Failed to generate secure token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to a simpler but still secure method
            return Str::random(64).uniqid('', true);
        }
    }

    // FEAT-002: File Upload System - Process file upload with deduplication
    // FEAT-005: File Versioning - Handle version management
    // FEAT-016: File Hash Tracking - Calculate and store file hash
    public function handleFileUpload(
        UploadRequest $request,
        UploadedFile $file,
        int $ownerId
    ): UploadObject {
        // Calculate file hash before any processing
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Check for duplicate by hash first
        $existingFileByHash = UploadObject::where('file_hash', $fileHash)
            ->whereHas('uploadRequests', function ($query) use ($request) {
                $query->where('upload_request_id', $request->id);
            })
            ->first();

        if ($existingFileByHash) {
            \Log::info('Duplicate file detected by hash', [
                'original_name' => $file->getClientOriginalName(),
                'hash'          => $fileHash,
            ]);

            return $existingFileByHash;
        }

        // Check for existing file by name
        $existingFileByName = UploadObject::where('original_name', $file->getClientOriginalName())
            ->whereHas('uploadRequests', function ($query) use ($request) {
                $query->where('upload_request_id', $request->id);
            })
            ->orderByDesc('version')
            ->first();

        $version = 1;
        $originalFileId = null;

        if ($existingFileByName) {
            // This is a new version of an existing file
            $version = $existingFileByName->getNextVersion();
            $originalFileId = $existingFileByName->original_file_id ?? $existingFileByName->id;

            \Log::info('Creating new version of file', [
                'original_name' => $file->getClientOriginalName(),
                'version'       => $version,
            ]);
        }

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $request->quickdrop_user_id.'/'.$request->unique_request_id.'/'.$filename;

        $uploadObject = new UploadObject();
        $uploadObject->quickdrop_owner_id = $ownerId;
        $uploadObject->original_name = $file->getClientOriginalName();
        $uploadObject->stored_name = $filename;
        $uploadObject->storage_path = $path;
        $uploadObject->mime_type = $file->getMimeType();
        $uploadObject->unique_id = Str::uuid();
        $uploadObject->file_size = $file->getSize();
        $uploadObject->file_extension = $file->getClientOriginalExtension();
        $uploadObject->file_hash = $fileHash;
        $uploadObject->status = 'processing';
        $uploadObject->is_encrypted = $request->is_encrypted;
        $uploadObject->version = $version;
        $uploadObject->original_file_id = $originalFileId;

        // Store the file directly since encryption is handled client-side
        Storage::disk('quickdrops')->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        $uploadObject->save();
        $request->uploadObjects()->attach($uploadObject->id);
        $uploadObject->markAsComplete();

        return $uploadObject;
    }

    // FEAT-001: QuickDrop Creation - Create new upload request
    // FEAT-006: Client-Side Encryption - Store encryption metadata
    // FEAT-007: Reference Number Validation - Store reference number
    // FEAT-008: Expiration Management - Set expiration time
    // FEAT-014: Public Download Control - Set public permissions
    public function createUploadRequest(
        int $userId,
        string $title,
        ?string $comment,
        ?string $referenceNumber,
        int $expiresInMinutes,
        bool $useEncryption = false,
        ?string $keyVerificationHash = null,
        bool $allowPublicDownload = false,
        bool $allowPublicDelete = false,
        bool $allowPublicUpload = true
    ): UploadRequest {
        $uploadRequest = new UploadRequest();
        $uploadRequest->quickdrop_user_id = $userId;
        $uploadRequest->title = $title;
        $uploadRequest->comment = $comment;
        $uploadRequest->reference_number = $referenceNumber;
        $uploadRequest->expires_at = now()->addMinutes($expiresInMinutes);
        $uploadRequest->unique_request_id = Str::uuid();
        $uploadRequest->verification_token = Str::random(64);
        $uploadRequest->is_encrypted = $useEncryption;
        $uploadRequest->key_verification_hash = $keyVerificationHash;
        $uploadRequest->allow_public_download = $allowPublicDownload;
        $uploadRequest->allow_public_delete = $allowPublicDelete;
        $uploadRequest->allow_public_upload = $allowPublicUpload;
        $uploadRequest->save();

        return $uploadRequest;
    }

    public function decryptFile(UploadObject $uploadObject, string $key): string
    {
        if (!$uploadObject->is_encrypted) {
            throw new \Exception('File is not encrypted');
        }

        $content = Storage::disk('quickdrops')->get($uploadObject->storage_path);
        if (!$content) {
            throw new \Exception('Failed to read file content');
        }

        // Implement your decryption logic here
        // This is a placeholder - you should implement proper decryption
        return $content;
    }
}
