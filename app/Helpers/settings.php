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
        return SystemSetting::getValue($key, $default);
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