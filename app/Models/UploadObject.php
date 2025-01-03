<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class UploadObject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'original_name',
        'stored_name',
        'storage_path',
        'mime_type',
        'unique_id',
        'file_size',
        'file_extension',
        'file_hash',
        'is_encrypted',
        'status',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_encrypted' => 'boolean',
        'metadata' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function uploadRequests(): BelongsToMany
    {
        return $this->belongsToMany(UploadRequest::class, 'upload_request_upload_object');
    }

    public function getStoragePath(): string
    {
        return Storage::disk('quickdrops')->path($this->storage_path);
    }

    public function delete(): ?bool
    {
        // Delete the physical file
        Storage::disk('quickdrops')->delete($this->storage_path);
        
        return parent::delete();
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function markAsComplete(): void
    {
        $this->update(['status' => 'complete']);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
