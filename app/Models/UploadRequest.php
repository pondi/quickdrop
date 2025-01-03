<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class UploadRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'requesting_user_id',
        'unique_request_id',
        'verification_token',
        'expires_at',
        'status',
        'allowed_mime_types',
        'max_file_size',
        'max_files',
        'is_encrypted',
        'key_verification_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'allowed_mime_types' => 'array',
        'max_file_size' => 'integer',
        'max_files' => 'integer',
    ];

    public function requestingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    public function uploadObjects(): BelongsToMany
    {
        return $this->belongsToMany(UploadObject::class, 'upload_request_upload_object');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function canAcceptMoreFiles(): bool
    {
        if (!$this->max_files) {
            return true;
        }

        return $this->uploadObjects()->count() < $this->max_files;
    }

    public function validateFileSize(int $size): bool
    {
        if (!$this->max_file_size) {
            return true;
        }

        return $size <= $this->max_file_size;
    }

    public function validateMimeType(string $mimeType): bool
    {
        if (!$this->allowed_mime_types) {
            return true;
        }

        return in_array($mimeType, $this->allowed_mime_types);
    }
}
