<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class QuickDropUser extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'quickdrop_users';

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'last_login_at',
        'is_active',
        'timezone',
        'preferences',
        'storage_used',
        'storage_limit',
        'notify_on_upload_complete',
        'notify_on_download',
        'notify_on_expiration_warning',
        'notify_marketing',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'preferences' => 'array',
        'storage_used' => 'integer',
        'storage_limit' => 'integer',
        'notify_on_upload_complete' => 'boolean',
        'notify_on_download' => 'boolean',
        'notify_on_expiration_warning' => 'boolean',
        'notify_marketing' => 'boolean',
    ];

    public function uploadRequests()
    {
        return $this->hasMany(UploadRequest::class, 'quickdrop_user_id');
    }

    public function uploadObjects()
    {
        return $this->hasMany(UploadObject::class, 'quickdrop_owner_id');
    }

    public function magicLinks()
    {
        return $this->hasMany(MagicLink::class, 'email', 'email');
    }

    public function getStorageUsedPercentageAttribute()
    {
        if ($this->storage_limit === 0) {
            return 0;
        }
        return round(($this->storage_used / $this->storage_limit) * 100, 2);
    }

    public function getStorageRemainingAttribute()
    {
        return max(0, $this->storage_limit - $this->storage_used);
    }

    public function hasStorageSpace($sizeInBytes)
    {
        return ($this->storage_used + $sizeInBytes) <= $this->storage_limit;
    }

    public function incrementStorageUsed($sizeInBytes)
    {
        $this->increment('storage_used', $sizeInBytes);
    }

    public function decrementStorageUsed($sizeInBytes)
    {
        $this->decrement('storage_used', min($sizeInBytes, $this->storage_used));
    }

    public function updateLastLogin()
    {
        $this->update([
            'last_login_at' => now(),
        ]);
    }

    public function isVerified()
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }
}
