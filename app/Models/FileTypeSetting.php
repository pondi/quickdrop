<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FileTypeSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'extension',
        'mime_type',
        'display_name',
        'category',
        'is_allowed',
        'max_size',
        'icon_class',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
        'max_size' => 'integer',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    const CATEGORY_IMAGE = 'image';
    const CATEGORY_VIDEO = 'video';
    const CATEGORY_AUDIO = 'audio';
    const CATEGORY_DOCUMENT = 'document';
    const CATEGORY_ARCHIVE = 'archive';
    const CATEGORY_OTHER = 'other';

    const CATEGORIES = [
        self::CATEGORY_IMAGE => 'Images',
        self::CATEGORY_VIDEO => 'Videos',
        self::CATEGORY_AUDIO => 'Audio Files',
        self::CATEGORY_DOCUMENT => 'Documents',
        self::CATEGORY_ARCHIVE => 'Archives',
        self::CATEGORY_OTHER => 'Other Files',
    ];

    public static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('allowed_file_types');
            Cache::forget('allowed_mime_types');
        });

        static::deleted(function () {
            Cache::forget('allowed_file_types');
            Cache::forget('allowed_mime_types');
        });
    }

    public function scopeAllowed($query)
    {
        return $query->where('is_allowed', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public static function getAllowedExtensions(): array
    {
        return Cache::remember('allowed_file_types', 3600, function () {
            return self::allowed()
                ->orderBy('priority', 'desc')
                ->orderBy('extension')
                ->pluck('extension')
                ->toArray();
        });
    }

    public static function getAllowedMimeTypes(): array
    {
        return Cache::remember('allowed_mime_types', 3600, function () {
            return self::allowed()
                ->orderBy('priority', 'desc')
                ->orderBy('mime_type')
                ->pluck('mime_type')
                ->unique()
                ->toArray();
        });
    }

    public static function getMaxSizeForExtension(string $extension): ?int
    {
        return self::where('extension', $extension)
            ->where('is_allowed', true)
            ->value('max_size');
    }

    public static function getCategoryStats(): array
    {
        return self::selectRaw('category, COUNT(*) as total, SUM(CASE WHEN is_allowed = true THEN 1 ELSE 0 END) as allowed')
            ->groupBy('category')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->category => [
                    'name' => self::CATEGORIES[$item->category] ?? $item->category,
                    'total' => $item->total,
                    'allowed' => $item->allowed,
                ]];
            })
            ->toArray();
    }

    public function getFormattedMaxSizeAttribute(): ?string
    {
        if (!$this->max_size) {
            return null;
        }

        $sizes = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($this->max_size) - 1) / 3);
        
        return sprintf("%.2f", $this->max_size / pow(1024, $factor)) . ' ' . $sizes[$factor];
    }
}
