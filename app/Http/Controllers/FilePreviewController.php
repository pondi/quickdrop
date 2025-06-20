<?php

namespace App\Http\Controllers;

use App\Models\UploadObject;
use App\Models\UploadRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class FilePreviewController extends Controller
{
    // Wrapper method for test compatibility
    public function previewById(Request $request, string $requestId, int $fileId)
    {
        // Get the upload request
        $uploadRequest = UploadRequest::where('unique_request_id', $requestId)->firstOrFail();
        
        // Get the file by ID
        $file = $uploadRequest->uploadObjects()->findOrFail($fileId);
        
        // Call the existing preview method with the file's unique_id
        return $this->preview($request, $requestId, $file->unique_id);
    }

    public function preview(Request $request, $requestId, $fileUuid)
    {
        $uploadRequest = UploadRequest::where('unique_request_id', $requestId)->firstOrFail();
        
        // Check if request is expired
        if ($uploadRequest->isExpired()) {
            abort(404, 'This QuickDrop has expired.');
        }

        $uploadObject = UploadObject::where('unique_id', $fileUuid)
            ->whereHas('uploadRequests', function ($query) use ($uploadRequest) {
                $query->where('upload_requests.id', $uploadRequest->id);
            })
            ->firstOrFail();


        // Generate preview based on file type
        if ($this->isImage($uploadObject->mime_type)) {
            return $this->generateImagePreview($uploadObject);
        } elseif ($this->isPDF($uploadObject->mime_type)) {
            return $this->generatePDFPreview($uploadObject);
        } elseif ($this->isText($uploadObject->mime_type)) {
            return $this->generateTextPreview($uploadObject);
        } else {
            // For other file types, return the original file
            return redirect()->route('download.file', [
                'requestId' => $requestId,
                'fileUuid' => $fileUuid
            ]);
        }
    }

    public function thumbnail(Request $request, $requestId, $fileUuid)
    {
        $uploadRequest = UploadRequest::where('unique_request_id', $requestId)->firstOrFail();
        
        // Check if request is expired
        if ($uploadRequest->isExpired()) {
            abort(404, 'This QuickDrop has expired.');
        }

        $uploadObject = UploadObject::where('unique_id', $fileUuid)
            ->whereHas('uploadRequests', function ($query) use ($uploadRequest) {
                $query->where('upload_requests.id', $uploadRequest->id);
            })
            ->firstOrFail();

        // Only generate thumbnails for images
        if (!$this->isImage($uploadObject->mime_type)) {
            abort(404);
        }

        return $this->generateImageThumbnail($uploadObject);
    }

    protected function generateImagePreview($uploadObject)
    {
        $path = $uploadObject->storage_path;
        $disk = Storage::disk($uploadObject->storage_disk ?? 'quickdrops');

        if (!$disk->exists($path)) {
            abort(404, 'File not found.');
        }

        // For images, we can serve them directly with proper headers
        $file = $disk->get($path);
        $mimeType = $uploadObject->mime_type;

        return Response::make($file, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    protected function generateImageThumbnail($uploadObject)
    {
        // For now, just serve the original image as thumbnail
        // In production, you would want to use Intervention Image or similar
        // to generate actual thumbnails
        return $this->generateImagePreview($uploadObject);
    }

    protected function generatePDFPreview($uploadObject)
    {
        // For PDFs, we'll serve them directly with proper headers
        // Modern browsers can display PDFs inline
        $path = $uploadObject->storage_path;
        $disk = Storage::disk($uploadObject->storage_disk ?? 'quickdrops');

        if (!$disk->exists($path)) {
            abort(404, 'File not found.');
        }

        $file = $disk->get($path);

        return Response::make($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $uploadObject->original_name . '"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    protected function generateTextPreview($uploadObject)
    {
        $path = $uploadObject->storage_path;
        $disk = Storage::disk($uploadObject->storage_disk ?? 'quickdrops');

        if (!$disk->exists($path)) {
            abort(404, 'File not found.');
        }

        // Read only first 10KB for preview
        $stream = $disk->readStream($path);
        $content = fread($stream, 10240); // 10KB
        fclose($stream);

        // Convert to UTF-8 if needed
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return Response::make($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    protected function isImage($mimeType)
    {
        return str_starts_with($mimeType, 'image/');
    }

    protected function isPDF($mimeType)
    {
        return $mimeType === 'application/pdf';
    }

    protected function isText($mimeType)
    {
        $textTypes = [
            'text/plain',
            'text/html',
            'text/css',
            'text/javascript',
            'application/json',
            'application/xml',
            'text/xml',
            'application/javascript',
            'text/csv',
            'text/markdown',
        ];

        return in_array($mimeType, $textTypes) || str_starts_with($mimeType, 'text/');
    }
}