<?php

namespace App\Services;

use App\Models\DownloadLog;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DownloadTrackingService
{
    public function trackDownload(
        UploadRequest $uploadRequest,
        ?UploadObject $uploadObject = null,
        Request $request = null,
        string $type = 'single'
    ): DownloadLog {
        $request = $request ?? request();
        
        $downloadLog = DownloadLog::create([
            'upload_request_id' => $uploadRequest->id,
            'upload_object_id' => $uploadObject?->id,
            'user_id' => Auth::guard('quickdrop')->id(),
            'download_type' => $uploadObject ? 'single' : 'bulk',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'started_at' => now(),
            'metadata' => [
                'request_id' => $uploadRequest->unique_request_id,
                'file_name' => $uploadObject?->original_name,
                'file_size' => $uploadObject?->file_size,
                'mime_type' => $uploadObject?->mime_type,
            ],
        ]);
        
        return $downloadLog;
    }
    
    public function completeDownload(DownloadLog $downloadLog, int $bytesDownloaded): void
    {
        $downloadLog->markAsCompleted($bytesDownloaded);
    }
    
    public function getDownloadStats(string $period = 'today'): array
    {
        $query = DownloadLog::query();
        
        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }
        
        $stats = [
            'total_downloads' => $query->count(),
            'completed_downloads' => $query->completed()->count(),
            'single_downloads' => $query->byType('single')->count(),
            'bulk_downloads' => $query->byType('bulk')->count(),
            'total_bytes' => $query->completed()->sum('bytes_downloaded'),
            'unique_users' => $query->distinct('user_id')->count('user_id'),
            'unique_ips' => $query->distinct('ip_address')->count('ip_address'),
        ];
        
        return $stats;
    }
    
    public function getTopDownloads(int $limit = 10): array
    {
        $topFiles = DownloadLog::select('upload_object_id', 'upload_objects.original_name')
            ->selectRaw('COUNT(*) as download_count')
            ->selectRaw('SUM(download_logs.bytes_downloaded) as total_bytes')
            ->join('upload_objects', 'upload_objects.id', '=', 'download_logs.upload_object_id')
            ->whereNotNull('upload_object_id')
            ->where('completed', true)
            ->groupBy('upload_object_id', 'upload_objects.original_name')
            ->orderByDesc('download_count')
            ->limit($limit)
            ->get();
            
        $topQuickDrops = DownloadLog::select('upload_request_id', 'upload_requests.title')
            ->selectRaw('COUNT(*) as download_count')
            ->selectRaw('SUM(download_logs.bytes_downloaded) as total_bytes')
            ->join('upload_requests', 'upload_requests.id', '=', 'download_logs.upload_request_id')
            ->where('completed', true)
            ->groupBy('upload_request_id', 'upload_requests.title')
            ->orderByDesc('download_count')
            ->limit($limit)
            ->get();
            
        return [
            'files' => $topFiles,
            'quickdrops' => $topQuickDrops,
        ];
    }
    
    public function getDownloadTrend(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        $trend = DownloadLog::selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total_downloads')
            ->selectRaw('COUNT(CASE WHEN completed = true THEN 1 END) as completed_downloads')
            ->selectRaw('SUM(CASE WHEN completed = true THEN bytes_downloaded ELSE 0 END) as bytes_downloaded')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->date => [
                        'total' => $item->total_downloads,
                        'completed' => $item->completed_downloads,
                        'bytes' => $item->bytes_downloaded,
                    ]
                ];
            })
            ->toArray();
            
        // Fill in missing dates
        $result = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[$date] = $trend[$date] ?? [
                'total' => 0,
                'completed' => 0,
                'bytes' => 0,
            ];
        }
        
        return $result;
    }
}