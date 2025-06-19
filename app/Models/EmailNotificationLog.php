<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailNotificationLog extends Model
{
    const TYPE_UPLOAD_COMPLETE = 'upload_complete';
    const TYPE_EXPIRATION_WARNING = 'expiration_warning';
    const TYPE_DOWNLOAD_ALERT = 'download_alert';
    const TYPE_QUICKDROP_SHARED = 'quickdrop_shared';
    const TYPE_ALL_UPLOADS_COMPLETE = 'all_uploads_complete';
    
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'notification_type',
        'recipient_email',
        'quickdrop_user_id',
        'upload_request_id',
        'upload_object_id',
        'subject',
        'data',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function quickdropUser(): BelongsTo
    {
        return $this->belongsTo(QuickDropUser::class, 'quickdrop_user_id');
    }

    public function uploadRequest(): BelongsTo
    {
        return $this->belongsTo(UploadRequest::class);
    }

    public function uploadObject(): BelongsTo
    {
        return $this->belongsTo(UploadObject::class);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }
}
