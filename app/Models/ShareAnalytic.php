<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'upload_request_id',
        'date',
        'total_views',
        'unique_views',
        'owner_views',
        'public_views',
        'download_count',
        'upload_count',
        'device_breakdown',
        'browser_breakdown',
        'country_breakdown',
        'hourly_views',
    ];

    protected $casts = [
        'date' => 'date',
        'total_views' => 'integer',
        'unique_views' => 'integer',
        'owner_views' => 'integer',
        'public_views' => 'integer',
        'download_count' => 'integer',
        'upload_count' => 'integer',
        'device_breakdown' => 'array',
        'browser_breakdown' => 'array',
        'country_breakdown' => 'array',
        'hourly_views' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the upload request these analytics belong to
     */
    public function uploadRequest(): BelongsTo
    {
        return $this->belongsTo(UploadRequest::class);
    }

    /**
     * Increment a specific metric
     */
    public function incrementMetric(string $metric, int $amount = 1): void
    {
        $this->increment($metric, $amount);
    }

    /**
     * Update device breakdown
     */
    public function updateDeviceBreakdown(string $deviceType): void
    {
        $breakdown = $this->device_breakdown ?? [];
        $breakdown[$deviceType] = ($breakdown[$deviceType] ?? 0) + 1;
        $this->device_breakdown = $breakdown;
        $this->save();
    }

    /**
     * Update browser breakdown
     */
    public function updateBrowserBreakdown(string $browser): void
    {
        $breakdown = $this->browser_breakdown ?? [];
        $breakdown[$browser] = ($breakdown[$browser] ?? 0) + 1;
        $this->browser_breakdown = $breakdown;
        $this->save();
    }

    /**
     * Update country breakdown
     */
    public function updateCountryBreakdown(string $country): void
    {
        $breakdown = $this->country_breakdown ?? [];
        $breakdown[$country] = ($breakdown[$country] ?? 0) + 1;
        $this->country_breakdown = $breakdown;
        $this->save();
    }

    /**
     * Update hourly views
     */
    public function updateHourlyViews(int $hour): void
    {
        $hourlyViews = $this->hourly_views ?? array_fill(0, 24, 0);
        $hourlyViews[$hour] = ($hourlyViews[$hour] ?? 0) + 1;
        $this->hourly_views = $hourlyViews;
        $this->save();
    }

    /**
     * Get or create analytics for a specific date
     */
    public static function getOrCreateForDate(int $uploadRequestId, string $date): self
    {
        return self::firstOrCreate(
            [
                'upload_request_id' => $uploadRequestId,
                'date' => $date,
            ],
            [
                'total_views' => 0,
                'unique_views' => 0,
                'owner_views' => 0,
                'public_views' => 0,
                'download_count' => 0,
                'upload_count' => 0,
                'device_breakdown' => [],
                'browser_breakdown' => [],
                'country_breakdown' => [],
                'hourly_views' => array_fill(0, 24, 0),
            ]
        );
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }
}