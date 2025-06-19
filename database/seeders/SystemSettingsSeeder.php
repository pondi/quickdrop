<?php

namespace Database\Seeders;

use App\Services\SystemSettingsService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settingsService = app(SystemSettingsService::class);
        $settingsService->initializeSettings();
        
        $this->command->info('System settings initialized successfully.');
    }
}
