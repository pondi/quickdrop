<?php

namespace App\Console\Commands;

use App\Services\StorageAnalyticsService;
use Illuminate\Console\Command;

class RecordStorageMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:record-metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Record daily storage analytics metrics';

    /**
     * Execute the console command.
     */
    public function handle(StorageAnalyticsService $storageAnalyticsService): int
    {
        $this->info('Recording storage metrics...');
        
        try {
            $storageAnalyticsService->recordDailyMetrics();
            $this->info('Storage metrics recorded successfully.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to record storage metrics: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
