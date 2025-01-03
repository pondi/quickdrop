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

    public function createUploadRequest(
        int $userId,
        string $title,
        ?string $comment = null,
        ?string $referenceNumber = null,
        int $expiresInMinutes = 1440,
        bool $useEncryption = false,
        ?string $keyVerificationHash = null
    ): UploadRequest {
        $uploadRequest = new UploadRequest();
        $uploadRequest->requesting_user_id = $userId;
        $uploadRequest->title = $title;
        $uploadRequest->comment = $comment;
        $uploadRequest->reference_number = $referenceNumber;
        $uploadRequest->unique_request_id = Str::uuid();
        $uploadRequest->verification_token = Str::random(64);
        $uploadRequest->expires_at = now()->addMinutes($expiresInMinutes);
        $uploadRequest->is_encrypted = $useEncryption;
        $uploadRequest->key_verification_hash = $keyVerificationHash;
        $uploadRequest->save();

        return $uploadRequest;
    }

    public function handleFileUpload(
        UploadRequest $request,
        UploadedFile $file,
        int $ownerId
    ): UploadObject {
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
        $uploadObject->file_hash = hash_file('sha256', $file->getRealPath());
        $uploadObject->status = 'processing';
        $uploadObject->is_encrypted = $request->is_encrypted;

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
} 