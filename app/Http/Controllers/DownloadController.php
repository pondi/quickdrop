<?php

namespace App\Http\Controllers;

use App\Models\UploadRequest;
use App\Services\DownloadService;
use App\Services\AuditService;
use App\Services\ShareAnalyticsService;
use App\Services\DownloadTrackingService;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DownloadController extends Controller
{
    public function __construct(
        private readonly DownloadService $downloadService,
        private readonly DownloadTrackingService $downloadTrackingService
    ) {
    }
    
    // Wrapper method for test compatibility
    public function downloadFile(Request $request, string $requestId, int $fileId)
    {
        // Get the upload request
        $uploadRequest = UploadRequest::where('unique_request_id', $requestId)->firstOrFail();
        
        // Get the file by ID
        $file = $uploadRequest->uploadObjects()->findOrFail($fileId);
        
        // Call the existing download method with the file's unique_id
        return $this->download($request, $requestId, $file->unique_id);
    }
    
    // Wrapper method for downloading all files
    public function downloadAll(Request $request, string $requestId)
    {
        // Call the existing download method without fileUuid for bulk download
        return $this->download($request, $requestId, null);
    }

    public function download(Request $request, string $requestId, ?string $fileUuid = null)
    {
        try {
            // Disable Inertia handling for this request
            if ($request->header('X-Inertia')) {
                return response()->json(['message' => 'Direct file downloads not supported via Inertia. Please use a direct download link.'], 400);
            }

            $uploadRequest = UploadRequest::where('unique_request_id', $requestId)->first();
            
            if (!$uploadRequest) {
                return response()->json(['message' => 'QuickDrop not found'], 404);
            }
            
            // Check if expired
            if ($uploadRequest->expires_at && $uploadRequest->expires_at->isPast()) {
                return response()->json(['message' => 'This QuickDrop has expired'], 404);
            }
            
            // Check if inactive
            if ($uploadRequest->status !== 'active') {
                return response()->json(['message' => 'This QuickDrop is no longer available'], 404);
            }

            // Check session-based access control
            $sessionKey = 'quickdrop_access.' . $uploadRequest->unique_request_id;
            if (!session()->has($sessionKey)) {
                // Allow access if user is authenticated and owns the upload request
                if (auth()->guard('quickdrop')->check()) {
                    $user = auth()->guard('quickdrop')->user();
                    if ($uploadRequest->quickdrop_user_id !== $user->id) {
                        return response()->json(['message' => 'Access denied'], 403);
                    }
                } else {
                    // For public users, require session access
                    return response()->json(['message' => 'Access denied'], 403);
                }
            }

            // Check max downloads limit
            if ($uploadRequest->max_downloads > 0 && $uploadRequest->downloads_count >= $uploadRequest->max_downloads) {
                return response()->json(['message' => 'Maximum download limit reached'], 403);
            }

            // Prevent timeout for large files
            set_time_limit(0);

            // Track download start
            $file = null;
            if ($fileUuid) {
                $file = $uploadRequest->uploadObjects()->where('unique_id', $fileUuid)->first();
            }
            $downloadLog = $this->downloadTrackingService->trackDownload($uploadRequest, $file, $request);

            // Log download activity
            if ($fileUuid) {
                // Single file download
                if ($file) {
                    AuditService::logFile(
                        AuditLog::EVENT_DOWNLOAD,
                        "Downloaded file: {$file->original_name}",
                        $file->id,
                        [
                            'quickdrop_id' => $uploadRequest->id,
                            'file_name' => $file->original_name,
                            'file_size' => $file->file_size,
                        ]
                    );
                }
            } else {
                // Bulk download
                AuditService::logQuickDrop(
                    AuditLog::EVENT_DOWNLOAD,
                    "Downloaded all files from QuickDrop: {$uploadRequest->title}",
                    $uploadRequest->id,
                    [
                        'file_count' => $uploadRequest->uploadObjects()->count(),
                        'total_size' => $uploadRequest->uploadObjects()->sum('file_size'),
                    ]
                );
            }

            // Track download for analytics
            ShareAnalyticsService::trackDownload($uploadRequest);

            // Send download notification
            if ($fileUuid && $file && $uploadRequest->quickdropUser) {
                $downloaderInfo = auth()->guard('quickdrop')->check() 
                    ? auth()->guard('quickdrop')->user()->email 
                    : 'Anonymous';
                app(\App\Services\EmailNotificationService::class)->sendDownloadAlertNotification($file, $downloaderInfo);
            }

            // Get download response
            $response = $this->downloadService->getDownloadResponse($uploadRequest, $fileUuid);
            
            // Mark download as completed
            $bytesDownloaded = $file ? $file->file_size : $uploadRequest->uploadObjects()->sum('file_size');
            $this->downloadTrackingService->completeDownload($downloadLog, $bytesDownloaded);

            return $response;
        } catch (RuntimeException $e) {
            Log::warning('Download failed: '.$e->getMessage(), [
                'requestId' => $requestId,
                'fileUuid'  => $fileUuid,
                'error'     => $e->getMessage(),
            ]);

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Download error: '.$e->getMessage(), [
                'requestId' => $requestId,
                'fileUuid'  => $fileUuid,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Download failed. Please try again.'], 500);
        }
    }
}
