<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'validation_rules',
        'order',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'order' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget('system_settings');
            Cache::forget("system_setting_{$setting->key}");
        });

        static::deleted(function ($setting) {
            Cache::forget('system_settings');
            Cache::forget("system_setting_{$setting->key}");
        });
    }

    public function getValueAttribute($value)
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    public function setValueAttribute($value)
    {
        switch ($this->type) {
            case 'boolean':
                $this->attributes['value'] = $value ? 'true' : 'false';
                break;
            case 'json':
                $this->attributes['value'] = json_encode($value);
                break;
            default:
                $this->attributes['value'] = $value;
        }
    }

    public static function getValue($key, $default = null)
    {
        return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function setValue($key, $value)
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = $value;
        $setting->save();
        return $setting;
    }

    public static function getGroupedSettings($includePrivate = false)
    {
        return Cache::remember('system_settings', 3600, function () use ($includePrivate) {
            $query = static::orderBy('group')->orderBy('order');
            
            if (!$includePrivate) {
                $query->where('is_public', true);
            }

            return $query->get()->groupBy('group');
        });
    }

    public function getValidationRulesArray()
    {
        return $this->validation_rules ? explode('|', $this->validation_rules) : [];
    }
}
