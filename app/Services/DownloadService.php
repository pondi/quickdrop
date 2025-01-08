<?php

namespace App\Services;

use App\Models\UploadObject;
use App\Models\UploadRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use RuntimeException;
use ZipArchive;

class DownloadService
{
    private const CHUNK_SIZE = 1024 * 1024; // 1MB chunks for memory efficiency

    public function __construct(
        private readonly string $tempPath = 'temp/downloads'
    ) {
    }

    public function getDownloadResponse(UploadRequest $request, ?string $fileUuid = null)
    {
        if ($fileUuid) {
            $file = $request->uploadObjects()->where('unique_id', $fileUuid)->firstOrFail();

            if ($file->is_encrypted) {
                return $this->getEncryptedFileResponse($file);
            }

            return $this->getSingleFileResponse($file);
        }

        // Check if any files are encrypted
        if ($request->uploadObjects()->where('is_encrypted', true)->exists()) {
            throw new RuntimeException('Cannot download encrypted files in bulk. Please download them individually.');
        }

        return $this->getZipResponse($request);
    }

    private function getSingleFileResponse(UploadObject $file)
    {
        if ($this->isS3Storage()) {
            return $this->getS3FileResponse($file);
        }

        return Storage::disk('quickdrops')->download(
            $file->storage_path,
            $file->original_name,
            ['Content-Type' => $file->mime_type]
        );
    }

    private function getEncryptedFileResponse(UploadObject $file)
    {
        // For encrypted files, we always stream through our server to ensure security
        return Storage::disk('quickdrops')->download(
            $file->storage_path,
            $file->original_name,
            [
                'Content-Type'     => $file->mime_type,
                'X-Encrypted-File' => 'true',
            ]
        );
    }

    private function getS3FileResponse(UploadObject $file)
    {
        if (method_exists(Storage::disk('quickdrops'), 'download')) {
            return Storage::disk('quickdrops')->download(
                $file->storage_path,
                $file->original_name,
                ['Content-Type' => $file->mime_type]
            );
        }

        // Fallback to temporary URL if download method is not available
        $temporaryUrl = Storage::disk('quickdrops')->temporaryUrl(
            $file->storage_path,
            now()->addMinutes(5)
        );

        return redirect()->away($temporaryUrl);
    }

    private function getZipResponse(UploadRequest $request)
    {
        $zipName = "quickdrop-{$request->unique_request_id}.zip";
        $tempPath = storage_path("app/temp/{$zipName}");

        // Ensure temp directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create zip file');
        }

        try {
            foreach ($request->uploadObjects as $file) {
                try {
                    if ($this->isS3Storage()) {
                        // For S3, download to temp file first
                        $tempFile = $this->downloadToTemp($file);
                        $zip->addFile($tempFile, $file->original_name);
                    } else {
                        $zip->addFile(
                            Storage::disk('quickdrops')->path($file->storage_path),
                            $file->original_name
                        );
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to add file to ZIP', [
                        'file'  => $file->original_name,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            $zip->close();

            // Create response and cleanup
            $response = response()->download($tempPath, $zipName, [
                'Content-Type'        => 'application/zip',
                'Content-Disposition' => 'attachment; filename="'.$zipName.'"',
            ])->deleteFileAfterSend(true);

            // Clean up any temp files
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }

            return $response;
        } catch (\Exception $e) {
            // Clean up on error
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            throw $e;
        }
    }

    private function downloadToTemp(UploadObject $file): string
    {
        $tempPath = storage_path("app/{$this->tempPath}/".Str::random(40));

        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        if ($this->isS3Storage()) {
            $tempUrl = Storage::disk('quickdrops')->temporaryUrl(
                $file->storage_path,
                now()->addMinutes(5)
            );
            copy($tempUrl, $tempPath);
        } else {
            copy(
                Storage::disk('quickdrops')->path($file->storage_path),
                $tempPath
            );
        }

        return $tempPath;
    }

    private function isS3Storage(): bool
    {
        $driver = Storage::disk('quickdrops')->getDriver();
        $adapter = method_exists($driver, 'getAdapter')
            ? $driver->getAdapter()
            : $driver;

        return $adapter instanceof AwsS3V3Adapter;
    }
}
