<?php

namespace App\Services;

use App\Models\FileTypeSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class FileTypeService
{
    public function isFileTypeAllowed(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        
        // Check if extension is allowed
        $allowedExtensions = FileTypeSetting::getAllowedExtensions();
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }
        
        // Check if mime type is allowed
        $allowedMimeTypes = FileTypeSetting::getAllowedMimeTypes();
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return false;
        }
        
        // Check file size limit for this type
        $maxSize = FileTypeSetting::getMaxSizeForExtension($extension);
        if ($maxSize && $file->getSize() > $maxSize) {
            return false;
        }
        
        return true;
    }
    
    public function getFileTypeDetails(string $extension): ?FileTypeSetting
    {
        return FileTypeSetting::where('extension', strtolower($extension))->first();
    }
    
    public function getAllowedExtensionsForDisplay(): array
    {
        return Cache::remember('allowed_extensions_display', 3600, function () {
            return FileTypeSetting::allowed()
                ->orderBy('category')
                ->orderBy('priority', 'desc')
                ->orderBy('extension')
                ->get()
                ->groupBy('category')
                ->map(function ($items, $category) {
                    return [
                        'name' => FileTypeSetting::CATEGORIES[$category] ?? $category,
                        'extensions' => $items->pluck('extension')->toArray(),
                    ];
                })
                ->values()
                ->toArray();
        });
    }
    
    public function getConfigForFrontend(): array
    {
        return Cache::remember('file_type_config', 3600, function () {
            $settings = FileTypeSetting::allowed()
                ->orderBy('priority', 'desc')
                ->orderBy('extension')
                ->get();
                
            return [
                'allowed_extensions' => $settings->pluck('extension')->toArray(),
                'allowed_mime_types' => $settings->pluck('mime_type')->unique()->toArray(),
                'max_file_size' => config('quickdrop.max_file_size', 100 * 1024 * 1024), // Default 100MB
                'file_type_limits' => $settings
                    ->filter(fn($item) => $item->max_size)
                    ->mapWithKeys(fn($item) => [$item->extension => $item->max_size])
                    ->toArray(),
                'categories' => $this->getAllowedExtensionsForDisplay(),
            ];
        });
    }
    
    public function updateFileType(FileTypeSetting $fileType, array $data): FileTypeSetting
    {
        $fileType->update($data);
        
        // Clear caches
        Cache::forget('allowed_extensions_display');
        Cache::forget('file_type_config');
        
        return $fileType->fresh();
    }
    
    public function toggleFileType(FileTypeSetting $fileType): FileTypeSetting
    {
        $fileType->update(['is_allowed' => !$fileType->is_allowed]);
        
        // Clear caches
        Cache::forget('allowed_extensions_display');
        Cache::forget('file_type_config');
        
        return $fileType->fresh();
    }
    
    public function bulkToggleCategory(string $category, bool $allowed): int
    {
        $updated = FileTypeSetting::where('category', $category)
            ->update(['is_allowed' => $allowed]);
            
        // Clear caches
        Cache::forget('allowed_file_types');
        Cache::forget('allowed_mime_types');
        Cache::forget('allowed_extensions_display');
        Cache::forget('file_type_config');
        
        return $updated;
    }
    
    public function createFileType(array $data): FileTypeSetting
    {
        $fileType = FileTypeSetting::create($data);
        
        // Clear caches
        Cache::forget('allowed_extensions_display');
        Cache::forget('file_type_config');
        
        return $fileType;
    }
    
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        
        // Check extension
        if (!in_array($extension, FileTypeSetting::getAllowedExtensions())) {
            $errors[] = "File type .{$extension} is not allowed.";
        }
        
        // Check mime type
        if (!in_array($mimeType, FileTypeSetting::getAllowedMimeTypes())) {
            $errors[] = "MIME type {$mimeType} is not allowed.";
        }
        
        // Check file size
        $maxSize = FileTypeSetting::getMaxSizeForExtension($extension);
        if ($maxSize && $file->getSize() > $maxSize) {
            $errors[] = "File size exceeds the limit for .{$extension} files (" . $this->formatBytes($maxSize) . ").";
        }
        
        // Check global file size limit
        $globalMaxSize = config('quickdrop.max_file_size', 100 * 1024 * 1024);
        if ($file->getSize() > $globalMaxSize) {
            $errors[] = "File size exceeds the global limit (" . $this->formatBytes($globalMaxSize) . ").";
        }
        
        return $errors;
    }
    
    private function formatBytes(int $bytes): string
    {
        $sizes = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . $sizes[$factor];
    }
}