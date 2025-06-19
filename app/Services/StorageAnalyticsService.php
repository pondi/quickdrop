<?php

namespace App\Services;

use App\Models\StorageAnalytics;
use App\Models\UploadObject;
use App\Models\UploadRequest;
use App\Models\QuickDropUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StorageAnalyticsService
{
    public function calculateCurrentMetrics(): array
    {
        $activeRequests = UploadRequest::where('expires_at', '>', now())->with('uploadObjects');
        $expiredRequests = UploadRequest::where('expires_at', '<=', now())->with('uploadObjects');
        
        $totalStorage = UploadObject::sum('file_size');
        $activeStorage = UploadObject::whereHas('uploadRequests', function ($query) {
            $query->where('expires_at', '>', now());
        })->sum('file_size');
        $expiredStorage = $totalStorage - $activeStorage;
        
        $totalFiles = UploadObject::count();
        $activeFiles = UploadObject::whereHas('uploadRequests', function ($query) {
            $query->where('expires_at', '>', now());
        })->count();
        $expiredFiles = $totalFiles - $activeFiles;
        
        $totalQuickDrops = UploadRequest::count();
        $activeQuickDrops = $activeRequests->count();
        $expiredQuickDrops = $expiredRequests->count();
        
        $fileTypeBreakdown = $this->getFileTypeBreakdown();
        $userStorageBreakdown = $this->getUserStorageBreakdown();
        
        return [
            'total_storage_used' => $totalStorage,
            'active_storage_used' => $activeStorage,
            'expired_storage_used' => $expiredStorage,
            'total_files' => $totalFiles,
            'active_files' => $activeFiles,
            'expired_files' => $expiredFiles,
            'total_quickdrops' => $totalQuickDrops,
            'active_quickdrops' => $activeQuickDrops,
            'expired_quickdrops' => $expiredQuickDrops,
            'file_type_breakdown' => $fileTypeBreakdown,
            'user_storage_breakdown' => $userStorageBreakdown,
        ];
    }
    
    public function recordDailyMetrics(): void
    {
        $metrics = $this->calculateCurrentMetrics();
        $metrics['date'] = Carbon::today();
        
        StorageAnalytics::updateOrCreate(
            ['date' => $metrics['date']],
            $metrics
        );
    }
    
    public function getHistoricalData(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        return StorageAnalytics::where('date', '>=', $startDate)
            ->orderBy('date', 'asc')
            ->get()
            ->toArray();
    }
    
    public function getStorageOverview(): array
    {
        $current = $this->calculateCurrentMetrics();
        $historical = $this->getHistoricalData(30);
        
        $growthRate = $this->calculateGrowthRate($historical);
        $storageByUser = $this->getTopStorageUsers(10);
        $storageByType = $this->getStorageByFileType();
        
        return [
            'current' => $current,
            'historical' => $historical,
            'growth_rate' => $growthRate,
            'top_users' => $storageByUser,
            'file_types' => $storageByType,
        ];
    }
    
    private function getFileTypeBreakdown(): array
    {
        $breakdown = UploadObject::select('mime_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(file_size) as total_size'))
            ->groupBy('mime_type')
            ->orderByDesc('total_size')
            ->limit(10)
            ->get();
            
        $result = [];
        foreach ($breakdown as $item) {
            $extension = $this->getExtensionFromMimeType($item->mime_type);
            $result[$extension] = [
                'count' => $item->count,
                'size' => $item->total_size,
                'percentage' => 0,
            ];
        }
        
        $totalSize = array_sum(array_column($result, 'size'));
        foreach ($result as &$item) {
            $item['percentage'] = $totalSize > 0 ? round(($item['size'] / $totalSize) * 100, 2) : 0;
        }
        
        return $result;
    }
    
    private function getUserStorageBreakdown(): array
    {
        $breakdown = QuickDropUser::select('quickdrop_users.id', 'quickdrop_users.name', 'quickdrop_users.email')
            ->selectRaw('SUM(upload_objects.file_size) as total_size')
            ->selectRaw('COUNT(DISTINCT upload_requests.id) as quickdrop_count')
            ->selectRaw('COUNT(upload_objects.id) as file_count')
            ->join('upload_requests', 'upload_requests.quickdrop_user_id', '=', 'quickdrop_users.id')
            ->join('upload_request_upload_object', 'upload_request_upload_object.upload_request_id', '=', 'upload_requests.id')
            ->join('upload_objects', 'upload_objects.id', '=', 'upload_request_upload_object.upload_object_id')
            ->groupBy('quickdrop_users.id', 'quickdrop_users.name', 'quickdrop_users.email')
            ->orderByDesc('total_size')
            ->limit(10)
            ->get();
            
        return $breakdown->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name ?? 'Unknown',
                'email' => $user->email,
                'storage_used' => $user->total_size ?? 0,
                'quickdrop_count' => $user->quickdrop_count ?? 0,
                'file_count' => $user->file_count ?? 0,
            ];
        })->toArray();
    }
    
    private function calculateGrowthRate(array $historical): array
    {
        if (count($historical) < 2) {
            return [
                'storage' => 0,
                'files' => 0,
                'quickdrops' => 0,
            ];
        }
        
        $first = reset($historical);
        $last = end($historical);
        
        $storageDiff = $last['total_storage_used'] - $first['total_storage_used'];
        $filesDiff = $last['total_files'] - $first['total_files'];
        $quickdropsDiff = $last['total_quickdrops'] - $first['total_quickdrops'];
        
        return [
            'storage' => $first['total_storage_used'] > 0 ? round(($storageDiff / $first['total_storage_used']) * 100, 2) : 0,
            'files' => $first['total_files'] > 0 ? round(($filesDiff / $first['total_files']) * 100, 2) : 0,
            'quickdrops' => $first['total_quickdrops'] > 0 ? round(($quickdropsDiff / $first['total_quickdrops']) * 100, 2) : 0,
        ];
    }
    
    private function getTopStorageUsers(int $limit = 10): array
    {
        return Cache::remember('storage_top_users', 300, function () use ($limit) {
            return $this->getUserStorageBreakdown();
        });
    }
    
    private function getStorageByFileType(): array
    {
        return Cache::remember('storage_by_file_type', 300, function () {
            return $this->getFileTypeBreakdown();
        });
    }
    
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $mimeToExtension = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/json' => 'json',
            'application/xml' => 'xml',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
        ];
        
        return $mimeToExtension[$mimeType] ?? 'other';
    }
}