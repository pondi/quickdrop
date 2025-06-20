<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'event_category',
        'description',
        'model_type',
        'model_id',
        'user_id',
        'user_type',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'old_values',
        'new_values',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Event types
    const EVENT_LOGIN = 'login';
    const EVENT_LOGOUT = 'logout';
    const EVENT_CREATE = 'create';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';
    const EVENT_DOWNLOAD = 'download';
    const EVENT_UPLOAD = 'upload';
    const EVENT_VIEW = 'view';
    const EVENT_SHARE = 'share';
    const EVENT_EXTEND = 'extend';

    // Event categories
    const CATEGORY_AUTH = 'auth';
    const CATEGORY_QUICKDROP = 'quickdrop';
    const CATEGORY_FILE = 'file';
    const CATEGORY_USER = 'user';
    const CATEGORY_SYSTEM = 'system';

    /**
     * Get the admin user that performed the action
     */
    public function adminUser()
    {
        return $this->belongsTo(User::class, 'user_id')->where('user_type', 'users');
    }

    /**
     * Get the quickdrop user that performed the action
     */
    public function quickDropUser()
    {
        return $this->belongsTo(QuickDropUser::class, 'user_id')->where('user_type', 'quickdrop_users');
    }
    
    /**
     * Get the user that performed the action (generic accessor)
     */
    public function getUser()
    {
        if ($this->user_type === 'users') {
            return $this->adminUser;
        } elseif ($this->user_type === 'quickdrop_users') {
            return $this->quickDropUser;
        }
        
        return null;
    }

    /**
     * Get the model that was affected
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('auditable', 'model_type', 'model_id');
    }

    /**
     * Scope for filtering by event type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope for filtering by category
     */
    public function scopeOfCategory($query, $category)
    {
        return $query->where('event_category', $category);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, $userId, $userType = 'quickdrop_users')
    {
        return $query->where('user_id', $userId)->where('user_type', $userType);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Get formatted user display name
     */
    public function getUserDisplayName()
    {
        if ($this->user_type === 'users' && $this->user_id) {
            $user = User::find($this->user_id);
            return $user ? $user->name : 'System';
        } elseif ($this->user_type === 'quickdrop_users' && $this->user_id) {
            $user = QuickDropUser::find($this->user_id);
            return $user ? ($user->name ?? $user->email) : 'Anonymous';
        }
        
        return 'System';
    }

    /**
     * Get icon for event type
     */
    public function getEventIcon()
    {
        return match($this->event_type) {
            self::EVENT_LOGIN => 'login',
            self::EVENT_LOGOUT => 'logout',
            self::EVENT_CREATE => 'plus',
            self::EVENT_UPDATE => 'edit',
            self::EVENT_DELETE => 'trash',
            self::EVENT_DOWNLOAD => 'download',
            self::EVENT_UPLOAD => 'upload',
            self::EVENT_VIEW => 'eye',
            self::EVENT_SHARE => 'share',
            self::EVENT_EXTEND => 'clock',
            default => 'activity'
        };
    }

    /**
     * Get color class for event type
     */
    public function getEventColor()
    {
        return match($this->event_type) {
            self::EVENT_LOGIN, self::EVENT_CREATE => 'success',
            self::EVENT_LOGOUT => 'info',
            self::EVENT_UPDATE, self::EVENT_EXTEND => 'warning',
            self::EVENT_DELETE => 'danger',
            self::EVENT_DOWNLOAD, self::EVENT_UPLOAD => 'primary',
            default => 'secondary'
        };
    }
}