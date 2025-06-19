<?php

namespace App\Http\Controllers;

use App\Models\UploadObject;
use App\Models\UploadRequest;
use App\Models\User;
use App\Models\QuickDropUser;
use App\Models\DownloadLog;
use App\Services\DownloadTrackingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BackstageDashboardController extends Controller
{
    public function __construct(
        private readonly DownloadTrackingService $downloadTrackingService
    ) {
    }

    public function index(Request $request): Response
    {
        $statistics = $this->getStatistics();

        return Inertia::render('Backstage/Dashboard', [
            'statistics' => $statistics,
        ]);
    }

    public function stats(Request $request)
    {
        return response()->json($this->getStatistics());
    }

    private function getStatistics()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Count QuickDrop users, not admin users
        $totalUsers = QuickDropUser::count();
        $totalActiveUsers = QuickDropUser::whereHas('uploadRequests', function ($query) use ($startOfMonth) {
            $query->where('created_at', '>=', $startOfMonth);
        })->count();

        $totalRequests = UploadRequest::count();
        $activeRequests = UploadRequest::where('expires_at', '>', $now)->count();
        $expiredRequests = UploadRequest::where('expires_at', '<=', $now)->count();

        $monthlyRequests = UploadRequest::where('created_at', '>=', $startOfMonth)->count();
        $lastMonthRequests = UploadRequest::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $requestsGrowth = $lastMonthRequests > 0 
            ? round((($monthlyRequests - $lastMonthRequests) / $lastMonthRequests) * 100, 1) 
            : 100;

        $totalFiles = UploadObject::count();
        $totalFileSize = UploadObject::sum('file_size');

        $monthlyUploads = UploadObject::where('created_at', '>=', $startOfMonth)->count();
        $lastMonthUploads = UploadObject::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $uploadsGrowth = $lastMonthUploads > 0 
            ? round((($monthlyUploads - $lastMonthUploads) / $lastMonthUploads) * 100, 1) 
            : 100;

        // Download statistics
        $totalDownloads = DownloadLog::count();
        $monthlyDownloads = DownloadLog::where('created_at', '>=', $startOfMonth)->count();
        $lastMonthDownloads = DownloadLog::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $downloadsGrowth = $lastMonthDownloads > 0 
            ? round((($monthlyDownloads - $lastMonthDownloads) / $lastMonthDownloads) * 100, 1) 
            : 100;
        
        $downloadStats = $this->downloadTrackingService->getDownloadStats('month');
        $topDownloads = $this->downloadTrackingService->getTopDownloads(5);

        $recentActivity = UploadRequest::with('quickDropUser')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'request_id' => $request->unique_request_id,
                    'title' => $request->title,
                    'user' => $request->quickDropUser ? [
                        'id' => $request->quickDropUser->id,
                        'name' => $request->quickDropUser->name,
                        'email' => $request->quickDropUser->email,
                    ] : null,
                    'files_count' => $request->uploadObjects()->count(),
                    'total_size' => $request->uploadObjects()->sum('file_size'),
                    'created_at' => $request->created_at->toIso8601String(),
                    'expires_at' => $request->expires_at->toIso8601String(),
                    'is_expired' => $request->isExpired(),
                ];
            });

        $dailyStats = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->startOfDay();
            $endDate = $date->copy()->endOfDay();
            
            $dailyStats->push([
                'date' => $date->format('Y-m-d'),
                'uploads' => UploadRequest::whereBetween('created_at', [$date, $endDate])->count(),
                'downloads' => DownloadLog::whereBetween('created_at', [$date, $endDate])->count(),
                'files' => UploadObject::whereBetween('created_at', [$date, $endDate])->count(),
            ]);
        }

        $fileTypeStats = UploadObject::selectRaw("
                CASE 
                    WHEN mime_type LIKE 'image/%' THEN 'Images'
                    WHEN mime_type LIKE 'video/%' THEN 'Videos'
                    WHEN mime_type LIKE 'audio/%' THEN 'Audio'
                    WHEN mime_type IN ('application/pdf') THEN 'PDFs'
                    WHEN mime_type IN (
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                    ) THEN 'Documents'
                    WHEN mime_type IN (
                        'application/zip',
                        'application/x-rar-compressed',
                        'application/x-7z-compressed',
                        'application/x-tar',
                        'application/gzip'
                    ) THEN 'Archives'
                    ELSE 'Other'
                END as type,
                COUNT(*) as count,
                SUM(file_size) as total_size
            ")
            ->groupBy('type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'count' => $item->count,
                    'total_size' => $item->total_size,
                    'percentage' => 0, // Will be calculated client-side
                ];
            });

        $topUsers = QuickDropUser::withCount('uploadRequests')
            ->orderBy('upload_requests_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'uploads_count' => $user->upload_requests_count,
                    'total_size' => $user->storage_used ?? 0,
                ];
            });

        return [
            'overview' => [
                'total_users' => $totalUsers,
                'active_users' => $totalActiveUsers,
                'total_requests' => $totalRequests,
                'active_requests' => $activeRequests,
                'expired_requests' => $expiredRequests,
                'total_files' => $totalFiles,
                'total_storage' => $totalFileSize,
                'monthly_uploads' => $monthlyUploads,
                'uploads_growth' => $uploadsGrowth,
                'monthly_requests' => $monthlyRequests,
                'requests_growth' => $requestsGrowth,
                'total_downloads' => $totalDownloads,
                'monthly_downloads' => $monthlyDownloads,
                'downloads_growth' => $downloadsGrowth,
                'download_stats' => $downloadStats,
            ],
            'daily_stats' => $dailyStats,
            'file_types' => $fileTypeStats,
            'top_users' => $topUsers,
            'recent_activity' => $recentActivity,
            'top_downloads' => $topDownloads,
        ];
    }
}