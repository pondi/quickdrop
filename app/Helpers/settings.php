<?php

use App\Models\SystemSetting;

if (!function_exists('settings')) {
    /**
     * Get a system setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function settings($key, $default = null)
    {
        try {
            return SystemSetting::getValue($key, $default);
        } catch (\Exception $e) {
            // In case of database errors (like during testing), return default
            \Log::warning("Failed to get setting: {$key}", ['error' => $e->getMessage()]);
            return $default;
        }
    }
}

if (!function_exists('setting')) {
    /**
     * Alias for settings()
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key, $default = null)
    {
        return settings($key, $default);
    }
}