<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MagicLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'quickdrop_user_id',
        'token',
        'expires_at',
        'used_at',
        'ip_address',
        'user_agent',
        'purpose',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    public static function generateToken()
    {
        return Str::random(64);
    }

    public static function createForEmail($email, $purpose = 'login', $expiresInMinutes = 15, $metadata = [])
    {
        return static::create([
            'email' => $email,
            'token' => static::generateToken(),
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'purpose' => $purpose,
            'metadata' => $metadata,
        ]);
    }

    public function quickDropUser()
    {
        return $this->belongsTo(QuickDropUser::class, 'quickdrop_user_id');
    }

    public function isValid()
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isUsed()
    {
        return $this->used_at !== null;
    }

    public function markAsUsed($ipAddress = null, $userAgent = null)
    {
        $this->update([
            'used_at' => now(),
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }

    public function scopeValid($query)
    {
        return $query->whereNull('used_at')
                    ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeUsed($query)
    {
        return $query->whereNotNull('used_at');
    }

    public function scopeForEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function scopeForPurpose($query, $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    public static function cleanupExpired()
    {
        return static::where('expires_at', '<=', now()->subDays(7))->delete();
    }
}
