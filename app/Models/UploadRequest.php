<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\FileTypeSetting;

class UploadRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'requesting_user_id',
        'quickdrop_user_id',
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
        'expires_at'         => 'datetime',
        'allowed_mime_types' => 'array',
        'max_file_size'      => 'integer',
        'max_files'          => 'integer',
    ];

    public function requestingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    public function quickDropUser(): BelongsTo
    {
        return $this->belongsTo(QuickDropUser::class, 'quickdrop_user_id');
    }

    public function uploadObjects(): BelongsToMany
    {
        return $this->belongsToMany(UploadObject::class, 'upload_request_upload_object');
    }

    public function views(): HasMany
    {
        return $this->hasMany(QuickDropView::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(ShareAnalytic::class);
    }

    public function emailNotificationLogs(): HasMany
    {
        return $this->hasMany(EmailNotificationLog::class);
    }

    // FEAT-008: Expiration Management - Check if request has expired
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    // FEAT-008: Expiration Management - Check if request is active
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
    
    // Get allowed mime types from dynamic file type settings
    public function getAllowedMimeTypesAttribute(): array
    {
        // Check if FileTypeSetting table exists
        if (!\Schema::hasTable('file_type_settings')) {
            // Fall back to config if table doesn't exist yet
            return config('quickdrop.allowed_mime_types', []);
        }
        
        return FileTypeSetting::getAllowedMimeTypes();
    }
}
