<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'upload_request_id',
        'upload_object_id',
        'user_id',
        'download_type',
        'ip_address',
        'user_agent',
        'referer',
        'bytes_downloaded',
        'completed',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function uploadRequest(): BelongsTo
    {
        return $this->belongsTo(UploadRequest::class);
    }

    public function uploadObject(): BelongsTo
    {
        return $this->belongsTo(UploadObject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(QuickDropUser::class, 'user_id');
    }

    public function markAsCompleted(int $bytesDownloaded): void
    {
        $this->update([
            'completed' => true,
            'completed_at' => now(),
            'bytes_downloaded' => $bytesDownloaded,
        ]);
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('download_type', $type);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }
}
