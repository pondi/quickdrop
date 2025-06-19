<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageAnalytics extends Model
{
    protected $fillable = [
        'date',
        'total_storage_used',
        'active_storage_used',
        'expired_storage_used',
        'total_files',
        'active_files',
        'expired_files',
        'total_quickdrops',
        'active_quickdrops',
        'expired_quickdrops',
        'file_type_breakdown',
        'user_storage_breakdown',
    ];

    protected $casts = [
        'date' => 'date',
        'file_type_breakdown' => 'array',
        'user_storage_breakdown' => 'array',
    ];
}
