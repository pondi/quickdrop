<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickDropView extends Model
{
    use HasFactory;

    protected $table = 'quickdrop_views';

    protected $fillable = [
        'upload_request_id',
        'ip_address',
        'user_agent',
        'referer',
        'country',
        'region',
        'city',
        'device_type',
        'browser',
        'os',
        'is_owner',
        'quickdrop_user_id',
    ];

    protected $casts = [
        'is_owner' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the upload request that was viewed
     */
    public function uploadRequest(): BelongsTo
    {
        return $this->belongsTo(UploadRequest::class);
    }

    /**
     * Get the user who viewed (if authenticated)
     */
    public function quickDropUser(): BelongsTo
    {
        return $this->belongsTo(QuickDropUser::class);
    }

    /**
     * Parse user agent to get device info
     */
    public static function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'device_type' => 'unknown',
                'browser' => 'unknown',
                'os' => 'unknown',
            ];
        }

        // Simple device type detection
        $deviceType = 'desktop';
        if (preg_match('/Mobile|Android|iPhone/i', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/iPad|Tablet/i', $userAgent)) {
            $deviceType = 'tablet';
        }

        // Simple browser detection
        $browser = 'other';
        if (preg_match('/Chrome/i', $userAgent) && !preg_match('/Edge/i', $userAgent)) {
            $browser = 'chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'firefox';
        } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'edge';
        }

        // Simple OS detection
        $os = 'other';
        if (preg_match('/Windows/i', $userAgent)) {
            $os = 'windows';
        } elseif (preg_match('/Mac OS|macOS/i', $userAgent)) {
            $os = 'macos';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $os = 'ios';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope for owner views only
     */
    public function scopeOwnerViews($query)
    {
        return $query->where('is_owner', true);
    }

    /**
     * Scope for public views only
     */
    public function scopePublicViews($query)
    {
        return $query->where('is_owner', false);
    }
}