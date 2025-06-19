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
            $updatedKeys = $this->settingsService->updateSettings($request->all());
            
            Log::info('System settings updated', [
                'user_id' => auth()->id(),
                'updated_keys' => $updatedKeys,
            ]);

            return back()->with('success', 'Settings updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to update system settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            
            return back()->with('error', 'Failed to update settings. Please try again.');
        }
    }

    public function reset()
    {
        try {
            $this->settingsService->resetToDefaults();
            
            Log::info('System settings reset to defaults', [
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Settings reset to defaults successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to reset system settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
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
}
