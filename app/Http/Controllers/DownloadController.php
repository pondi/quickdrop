<?php

namespace App\Http\Controllers;

use App\Models\UploadRequest;
use App\Services\DownloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DownloadController extends Controller
{
    public function __construct(
        private readonly DownloadService $downloadService
    ) {}

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
            
            return $this->downloadService->getDownloadResponse($uploadRequest, $fileUuid);

        } catch (RuntimeException $e) {
            Log::warning('Download failed: ' . $e->getMessage(), [
                'requestId' => $requestId,
                'fileUuid' => $fileUuid,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Download error: ' . $e->getMessage(), [
                'requestId' => $requestId,
                'fileUuid' => $fileUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['message' => 'Download failed. Please try again.'], 500);
        }
    }
} 