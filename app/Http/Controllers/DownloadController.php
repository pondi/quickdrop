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

    // FEAT-003: File Download - Handle single file and bulk downloads
    // FEAT-004: Bulk Download (ZIP) - When fileUuid is null
    public function download(Request $request, string $requestId, ?string $fileUuid = null)
    {
        try {
            // Disable Inertia handling for this request
            if ($request->header('X-Inertia')) {
                return response()->json(['message' => 'Direct file downloads not supported via Inertia. Please use a direct download link.'], 400);
            }

            $uploadRequest = UploadRequest::where('unique_request_id', $requestId)
                ->where(function ($query) {
                    $query->where('status', 'active')
                        ->where(function ($q) {
                            $q->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                })
                ->firstOrFail();

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
