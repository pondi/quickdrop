<?php

namespace App\Http\Controllers;

use App\Services\SystemSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BackstageSettingsController extends Controller
{
    protected $settingsService;

    public function __construct(SystemSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function index()
    {
        $settings = $this->settingsService->getAllSettings(true);
        
        // Transform settings for the frontend
        $formattedSettings = [];
        foreach ($settings as $group => $groupSettings) {
            foreach ($groupSettings as $setting) {
                $formattedSettings[$setting->key] = $setting->value;
            }
        }

        return Inertia::render('Backstage/Settings/Index', [
            'settings' => $formattedSettings,
            'settingsMetadata' => $settings->map(function ($groupSettings) {
                return $groupSettings->map(function ($setting) {
                    return [
                        'key' => $setting->key,
                        'label' => $setting->label,
                        'description' => $setting->description,
                        'type' => $setting->type,
                        'validation_rules' => $setting->validation_rules,
                    ];
                });
            }),
        ]);
    }

    public function update(Request $request)
    {
        try {
            // Handle both formats: settings nested under 'settings' key or direct
            $settings = $request->has('settings') ? $request->input('settings') : $request->all();
            
            $updatedKeys = $this->settingsService->updateSettings($settings);
            
            // Create audit log
            \App\Services\AuditService::log(
                'settings.updated',
                \App\Models\AuditLog::CATEGORY_SYSTEM,
                'System settings updated',
                null,
                null,
                null,
                null,
                ['changes' => array_intersect_key($settings, array_flip($updatedKeys))]
            );
            
            Log::info('System settings updated', [
                'user_id' => auth()->guard('web')->id(),
                'updated_keys' => $updatedKeys,
            ]);

            return back()->with('success', 'Settings updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to update system settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->guard('web')->id(),
            ]);
            
            return back()->with('error', 'Failed to update settings. Please try again.');
        }
    }

    public function reset()
    {
        try {
            $this->settingsService->resetToDefaults();
            
            Log::info('System settings reset to defaults', [
                'user_id' => auth()->guard('web')->id(),
            ]);

            return back()->with('success', 'Settings reset to defaults successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to reset system settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->guard('web')->id(),
            ]);
            
            return back()->with('error', 'Failed to reset settings. Please try again.');
        }
    }

    public function publicSettings()
    {
        $settings = $this->settingsService->getPublicSettings();
        
        return response()->json([
            'settings' => $settings,
        ]);
    }
    
    public function updateEmail(Request $request)
    {
        $request->validate([
            'email_settings' => 'required|array',
            'email_settings.from_address' => 'required|email',
            'email_settings.from_name' => 'required|string',
            'email_settings.reply_to' => 'nullable|email',
            'email_settings.notification_footer' => 'nullable|string',
        ]);
        
        foreach ($request->email_settings as $key => $value) {
            $this->settingsService->updateSetting("email.{$key}", $value);
        }
        
        return redirect()->back()->with('success', 'Email settings updated successfully.');
    }
    
    public function updateStorage(Request $request)
    {
        $request->validate([
            'storage_settings' => 'required|array',
            'storage_settings.disk' => 'required|in:local,s3',
            'storage_settings.path_prefix' => 'nullable|string',
            'storage_settings.cleanup_expired_after_days' => 'required|integer|min:1',
            'storage_settings.max_total_storage_gb' => 'required|integer|min:1',
        ]);
        
        foreach ($request->storage_settings as $key => $value) {
            $this->settingsService->updateSetting("storage.{$key}", $value);
        }
        
        return redirect()->back()->with('success', 'Storage settings updated successfully.');
    }
    
    public function updateSecurity(Request $request)
    {
        $request->validate([
            'security_settings' => 'required|array',
            'security_settings.max_login_attempts' => 'required|integer|min:1',
            'security_settings.lockout_duration_minutes' => 'required|integer|min:1',
            'security_settings.require_email_verification' => 'required|boolean',
            'security_settings.magic_link_expiry_minutes' => 'required|integer|min:5',
            'security_settings.session_lifetime_minutes' => 'required|integer|min:15',
        ]);
        
        foreach ($request->security_settings as $key => $value) {
            $this->settingsService->updateSetting("security.{$key}", $value);
        }
        
        return redirect()->back()->with('success', 'Security settings updated successfully.');
    }
    
    public function export()
    {
        $settings = $this->settingsService->getAllSettings(true);
        
        $exportData = [
            'settings' => $settings->flatMap(function ($groupSettings) {
                return $groupSettings->pluck('value', 'key');
            })->toArray(),
            'exported_at' => now()->toIso8601String(),
            'exported_by' => auth()->guard('web')->user()->email,
        ];
        
        return response()->json($exportData)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="quickdrop-settings.json"');
    }
    
    public function import(Request $request)
    {
        $request->validate([
            'settings_json' => 'required|json',
        ]);
        
        try {
            $data = json_decode($request->settings_json, true);
            
            if (!isset($data['settings'])) {
                throw new \Exception('Invalid settings format');
            }
            
            foreach ($data['settings'] as $key => $value) {
                $this->settingsService->updateSetting($key, $value);
            }
            
            return redirect()->back()->with('success', 'Settings imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to import settings: ' . $e->getMessage());
        }
    }
}
