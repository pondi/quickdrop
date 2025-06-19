<?php

namespace App\Services;

use App\Models\QuickDropView;
use App\Models\ShareAnalytic;
use App\Models\UploadRequest;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShareAnalyticsService
{
    /**
     * Track a view for a QuickDrop
     */
    public static function trackView(UploadRequest $uploadRequest, Request $request): void
    {
        try {
            $isOwner = Auth::guard('quickdrop')->check() && 
                      Auth::guard('quickdrop')->id() === $uploadRequest->requesting_user_id;
            
            $userAgent = $request->userAgent();
            $deviceInfo = QuickDropView::parseUserAgent($userAgent);
            
            // Create view record
            $view = QuickDropView::create([
                'upload_request_id' => $uploadRequest->id,
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent,
                'referer' => $request->header('referer'),
                'country' => null, // Would need GeoIP service
                'region' => null,
                'city' => null,
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
                'os' => $deviceInfo['os'],
                'is_owner' => $isOwner,
                'quickdrop_user_id' => Auth::guard('quickdrop')->id(),
            ]);
            
            // Update analytics
            self::updateAnalytics($uploadRequest, $view, $request);
            
            // Log view event
            AuditService::logQuickDrop(
                AuditLog::EVENT_VIEW,
                "Viewed QuickDrop: {$uploadRequest->title}",
                $uploadRequest->id,
                [
                    'is_owner' => $isOwner,
                    'device_type' => $deviceInfo['device_type'],
                    'browser' => $deviceInfo['browser'],
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Failed to track view: ' . $e->getMessage());
        }
    }

    /**
     * Update analytics for a view
     */
    protected static function updateAnalytics(UploadRequest $uploadRequest, QuickDropView $view, Request $request): void
    {
        $today = now()->format('Y-m-d');
        $hour = now()->hour;
        
        // Get or create today's analytics
        $analytics = ShareAnalytic::getOrCreateForDate($uploadRequest->id, $today);
        
        // Update basic counts
        $analytics->increment('total_views');
        
        if ($view->is_owner) {
            $analytics->increment('owner_views');
        } else {
            $analytics->increment('public_views');
        }
        
        // Check if unique view (by IP in last hour)
        $cacheKey = "view_{$uploadRequest->id}_{$request->ip()}";
        if (!Cache::has($cacheKey)) {
            $analytics->increment('unique_views');
            Cache::put($cacheKey, true, 3600); // 1 hour
        }
        
        // Update breakdowns
        $analytics->updateDeviceBreakdown($view->device_type);
        $analytics->updateBrowserBreakdown($view->browser);
        $analytics->updateHourlyViews($hour);
        
        // Update country if we had GeoIP
        // $analytics->updateCountryBreakdown($view->country ?? 'unknown');
    }

    /**
     * Track a download
     */
    public static function trackDownload(UploadRequest $uploadRequest): void
    {
        try {
            $today = now()->format('Y-m-d');
            $analytics = ShareAnalytic::getOrCreateForDate($uploadRequest->id, $today);
            $analytics->increment('download_count');
        } catch (\Exception $e) {
            \Log::error('Failed to track download: ' . $e->getMessage());
        }
    }

    /**
     * Track an upload
     */
    public static function trackUpload(UploadRequest $uploadRequest): void
    {
        try {
            $today = now()->format('Y-m-d');
            $analytics = ShareAnalytic::getOrCreateForDate($uploadRequest->id, $today);
            $analytics->increment('upload_count');
        } catch (\Exception $e) {
            \Log::error('Failed to track upload: ' . $e->getMessage());
        }
    }

    /**
     * Get analytics data for a QuickDrop
     */
    public static function getAnalytics(int $uploadRequestId, int $days = 7): array
    {
        $endDate = now();
        $startDate = now()->subDays($days - 1)->startOfDay();
        
        // Get analytics data
        $analytics = ShareAnalytic::where('upload_request_id', $uploadRequestId)
            ->dateRange($startDate, $endDate)
            ->orderBy('date')
            ->get();
        
        // Get recent views for real-time data
        $recentViews = QuickDropView::where('upload_request_id', $uploadRequestId)
            ->where('created_at', '>=', now()->subHours(24))
            ->get();
        
        // Calculate totals
        $totals = [
            'total_views' => $analytics->sum('total_views'),
            'unique_views' => $analytics->sum('unique_views'),
            'download_count' => $analytics->sum('download_count'),
            'upload_count' => $analytics->sum('upload_count'),
        ];
        
        // Get device breakdown
        $deviceBreakdown = [];
        foreach ($analytics as $day) {
            foreach ($day->device_breakdown ?? [] as $device => $count) {
                $deviceBreakdown[$device] = ($deviceBreakdown[$device] ?? 0) + $count;
            }
        }
        
        // Get browser breakdown
        $browserBreakdown = [];
        foreach ($analytics as $day) {
            foreach ($day->browser_breakdown ?? [] as $browser => $count) {
                $browserBreakdown[$browser] = ($browserBreakdown[$browser] ?? 0) + $count;
            }
        }
        
        // Get country breakdown
        $countryBreakdown = [];
        foreach ($analytics as $day) {
            foreach ($day->country_breakdown ?? [] as $country => $count) {
                $countryBreakdown[$country] = ($countryBreakdown[$country] ?? 0) + $count;
            }
        }
        
        // Format daily data for charts
        $dailyData = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dayData = $analytics->firstWhere('date', $currentDate->format('Y-m-d'));
            $dailyData[] = [
                'date' => $currentDate->format('Y-m-d'),
                'views' => $dayData ? $dayData->total_views : 0,
                'unique_views' => $dayData ? $dayData->unique_views : 0,
                'downloads' => $dayData ? $dayData->download_count : 0,
                'uploads' => $dayData ? $dayData->upload_count : 0,
            ];
            $currentDate->addDay();
        }
        
        // Get hourly data for today
        $todayAnalytics = $analytics->firstWhere('date', now()->format('Y-m-d'));
        $hourlyData = $todayAnalytics ? $todayAnalytics->hourly_views : array_fill(0, 24, 0);
        
        // Get recent activity
        $recentActivity = $recentViews->map(function ($view) {
            return [
                'time' => $view->created_at,
                'type' => 'view',
                'device' => $view->device_type,
                'browser' => $view->browser,
                'is_owner' => $view->is_owner,
            ];
        })->take(10);
        
        return [
            'totals' => $totals,
            'daily_data' => $dailyData,
            'hourly_data' => $hourlyData,
            'device_breakdown' => $deviceBreakdown,
            'browser_breakdown' => $browserBreakdown,
            'country_breakdown' => $countryBreakdown,
            'recent_activity' => $recentActivity,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $days,
            ],
        ];
    }

    /**
     * Get analytics summary for multiple QuickDrops
     */
    public static function getMultipleAnalytics(array $uploadRequestIds, int $days = 7): array
    {
        $endDate = now();
        $startDate = now()->subDays($days - 1)->startOfDay();
        
        $analytics = ShareAnalytic::whereIn('upload_request_id', $uploadRequestIds)
            ->dateRange($startDate, $endDate)
            ->get()
            ->groupBy('upload_request_id');
        
        $results = [];
        foreach ($uploadRequestIds as $id) {
            $dropAnalytics = $analytics->get($id, collect());
            $results[$id] = [
                'total_views' => $dropAnalytics->sum('total_views'),
                'unique_views' => $dropAnalytics->sum('unique_views'),
                'download_count' => $dropAnalytics->sum('download_count'),
                'upload_count' => $dropAnalytics->sum('upload_count'),
            ];
        }
        
        return $results;
    }
}