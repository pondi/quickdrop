<?php

namespace App\Services;

use App\Models\UploadRequest;
use App\Models\UploadObject;
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
            $timeHash = hash('xxh3', microtime(true) . uniqid('', true));
            
            // Ensure we have a valid string
            if (empty($token) || empty($timeHash)) {
                throw new \Exception('Failed to generate secure token components');
            }
            
            return $token . $timeHash;
        } catch (\Exception $e) {
            \Log::error('Failed to generate secure token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback to a simpler but still secure method
            return Str::random(64) . uniqid('', true);
        }
    }

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
                'hash' => $fileHash
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
                'version' => $version
            ]);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $request->requesting_user_id . '/' . $request->unique_request_id . '/' . $filename;

        $uploadObject = new UploadObject();
        $uploadObject->owner_id = $ownerId;
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

    public function createUploadRequest(
        int $userId,
        string $title,
        ?string $comment,
        ?string $referenceNumber,
        int $expiresInMinutes,
        bool $useEncryption,
        ?string $keyVerificationHash
    ): UploadRequest {
        $uploadRequest = new UploadRequest();
        $uploadRequest->requesting_user_id = $userId;
        $uploadRequest->title = $title;
        $uploadRequest->comment = $comment;
        $uploadRequest->reference_number = $referenceNumber;
        $uploadRequest->unique_request_id = $this->generateSecureToken();
        $uploadRequest->verification_token = $this->generateSecureToken();
        $uploadRequest->expires_at = now()->addMinutes($expiresInMinutes);
        $uploadRequest->is_encrypted = $useEncryption;
        $uploadRequest->key_verification_hash = $keyVerificationHash;
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