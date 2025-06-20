<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SystemSettingsService
{
    public function getAllSettings($includePrivate = false)
    {
        return SystemSetting::getGroupedSettings($includePrivate);
    }

    public function getSettingsForGroup($group, $includePrivate = false)
    {
        $settings = $this->getAllSettings($includePrivate);
        return $settings->get($group, collect());
    }

    public function getSetting($key, $default = null)
    {
        return SystemSetting::getValue($key, $default ?? $this->getDefaultValue($key));
    }

    public function updateSettings(array $settings)
    {
        $errors = [];
        $updated = [];

        foreach ($settings as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();
            
            if (!$setting) {
                // Create a basic setting if it doesn't exist
                $setting = new SystemSetting();
                $setting->key = $key;
                $setting->type = is_numeric($value) ? 'integer' : 'string';
                $setting->group = 'general';
                $setting->label = ucfirst(str_replace(['_', '.'], ' ', $key));
                $setting->is_public = false;
                $setting->order = 999;
                
                // Add basic validation for known keys
                if ($key === 'max_file_size' || $key === 'default_expiration_hours') {
                    $setting->validation_rules = 'required|integer|min:1';
                }
            }

            // Validate the value
            if ($setting->validation_rules) {
                $validator = Validator::make(
                    [$key => $value],
                    [$key => $setting->validation_rules]
                );

                if ($validator->fails()) {
                    $errors[$key] = $validator->errors()->first($key);
                    continue;
                }
            }

            // Update the setting
            $setting->value = $value;
            $setting->save();
            $updated[] = $key;
        }

        if (!empty($errors)) {
            // Prefix errors with 'settings.' to match expected format
            $prefixedErrors = [];
            foreach ($errors as $key => $error) {
                $prefixedErrors["settings.{$key}"] = $error;
            }
            throw ValidationException::withMessages($prefixedErrors);
        }

        // Clear all caches
        Cache::forget('system_settings');
        foreach ($updated as $key) {
            Cache::forget("system_setting_{$key}");
        }

        return $updated;
    }

    public function resetToDefaults()
    {
        $defaults = $this->getDefaultSettings();
        
        foreach ($defaults as $key => $config) {
            $setting = SystemSetting::where('key', $key)->first();
            if ($setting) {
                $setting->value = $config['value'];
                $setting->save();
            }
        }

        Cache::forget('system_settings');
    }

    protected function getDefaultValue($key)
    {
        $defaults = $this->getDefaultSettings();
        return $defaults[$key]['value'] ?? null;
    }

    public function getDefaultSettings()
    {
        return [
            // General Settings
            'app_name' => [
                'value' => config('app.name', 'QuickDrop'),
                'type' => 'string',
                'group' => 'general',
                'label' => 'Application Name',
                'description' => 'The name of your QuickDrop instance',
                'validation_rules' => 'required|string|max:50',
                'order' => 1,
                'is_public' => true,
            ],
            'require_reference_number' => [
                'value' => config('quickdrop.reference_number.required', false),
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Require Reference Number',
                'description' => 'Require users to enter a reference number when creating QuickDrops',
                'validation_rules' => 'required|boolean',
                'order' => 2,
                'is_public' => true,
            ],
            'reference_number_pattern' => [
                'value' => config('quickdrop.reference_number.pattern', '/^[A-Za-z0-9\-]+$/'),
                'type' => 'string',
                'group' => 'general',
                'label' => 'Reference Number Pattern',
                'description' => 'Regular expression pattern for validating reference numbers',
                'validation_rules' => 'required_if:require_reference_number,true|string',
                'order' => 3,
                'is_public' => false,
            ],
            'default_expiry_hours' => [
                'value' => 24,
                'type' => 'integer',
                'group' => 'general',
                'label' => 'Default Expiry (Hours)',
                'description' => 'Default expiration time for QuickDrops in hours',
                'validation_rules' => 'required|integer|min:1|max:720',
                'order' => 4,
                'is_public' => true,
            ],

            // Storage Settings
            'max_file_size_mb' => [
                'value' => intval(config('quickdrop.max_file_size') / 1024 / 1024),
                'type' => 'integer',
                'group' => 'storage',
                'label' => 'Max File Size (MB)',
                'description' => 'Maximum file size allowed per upload in megabytes',
                'validation_rules' => 'required|integer|min:1|max:5120',
                'order' => 1,
                'is_public' => true,
            ],
            'max_files_per_quickdrop' => [
                'value' => config('quickdrop.max_files_per_request', 10),
                'type' => 'integer',
                'group' => 'storage',
                'label' => 'Max Files per QuickDrop',
                'description' => 'Maximum number of files allowed per QuickDrop',
                'validation_rules' => 'required|integer|min:1|max:100',
                'order' => 2,
                'is_public' => true,
            ],
            'max_user_storage_gb' => [
                'value' => 10,
                'type' => 'integer',
                'group' => 'storage',
                'label' => 'Max User Storage (GB)',
                'description' => 'Maximum storage limit per user in gigabytes',
                'validation_rules' => 'required|integer|min:1|max:1000',
                'order' => 3,
                'is_public' => false,
            ],
            'storage_driver' => [
                'value' => config('filesystems.default', 'local'),
                'type' => 'string',
                'group' => 'storage',
                'label' => 'Storage Driver',
                'description' => 'Storage driver to use (local, s3, etc.)',
                'validation_rules' => 'required|string|in:local,public,s3',
                'order' => 4,
                'is_public' => false,
            ],

            // Email Settings
            'mail_from_address' => [
                'value' => config('mail.from.address', 'noreply@quickdrop.com'),
                'type' => 'string',
                'group' => 'email',
                'label' => 'From Email Address',
                'description' => 'Email address used as sender for system emails',
                'validation_rules' => 'required|email',
                'order' => 1,
                'is_public' => false,
            ],
            'mail_from_name' => [
                'value' => config('mail.from.name', 'QuickDrop'),
                'type' => 'string',
                'group' => 'email',
                'label' => 'From Name',
                'description' => 'Name displayed as sender for system emails',
                'validation_rules' => 'required|string|max:50',
                'order' => 2,
                'is_public' => false,
            ],
            'enable_email_notifications' => [
                'value' => true,
                'type' => 'boolean',
                'group' => 'email',
                'label' => 'Enable Email Notifications',
                'description' => 'Send email notifications for QuickDrop activities',
                'validation_rules' => 'required|boolean',
                'order' => 3,
                'is_public' => false,
            ],

            // Security Settings
            'allow_public_upload' => [
                'value' => true,
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Allow Public Uploads',
                'description' => 'Allow non-authenticated users to upload files to shared QuickDrops',
                'validation_rules' => 'required|boolean',
                'order' => 1,
                'is_public' => true,
            ],
            'allow_public_download' => [
                'value' => true,
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Allow Public Downloads',
                'description' => 'Allow non-authenticated users to download files from shared QuickDrops',
                'validation_rules' => 'required|boolean',
                'order' => 2,
                'is_public' => true,
            ],
            'require_auth_for_download' => [
                'value' => false,
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Require Authentication for Downloads',
                'description' => 'Require users to be logged in to download files',
                'validation_rules' => 'required|boolean',
                'order' => 3,
                'is_public' => true,
            ],
            'enable_client_encryption' => [
                'value' => true,
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Enable Client-Side Encryption',
                'description' => 'Allow users to encrypt files before uploading',
                'validation_rules' => 'required|boolean',
                'order' => 4,
                'is_public' => true,
            ],
        ];
    }

    public function initializeSettings()
    {
        $defaults = $this->getDefaultSettings();
        
        foreach ($defaults as $key => $config) {
            SystemSetting::firstOrCreate(
                ['key' => $key],
                $config
            );
        }
    }

    public function getPublicSettings()
    {
        $settings = SystemSetting::where('is_public', true)->get();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->value;
        }
        
        return $result;
    }

    public function updateSetting($key, $value)
    {
        $setting = SystemSetting::where('key', $key)->first();
        
        if (!$setting) {
            // Create new setting if it doesn't exist
            $setting = new SystemSetting();
            $setting->key = $key;
            $setting->type = 'string';
            $setting->group = 'general';
            $setting->label = ucfirst(str_replace(['.', '_'], ' ', $key));
            $setting->is_public = false;
            $setting->order = 999;
        }

        $setting->value = $value;
        $setting->save();

        // Clear caches
        Cache::forget('system_settings');
        Cache::forget("system_setting_{$key}");

        return $setting;
    }
}