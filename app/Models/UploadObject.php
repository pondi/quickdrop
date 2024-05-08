<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadObject extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'original_name', 'stored_name', 'storage_path', 'mime_type',
        'unique_id', 'file_size', 'file_extension', 'file_hash'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function uploadRequests()
    {
        return $this->belongsToMany(UploadRequest::class);
    }
}
