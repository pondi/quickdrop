<?php

namespace App\Console\Commands;

use App\Services\EmailNotificationService;
use Illuminate\Console\Command;

class SendExpirationWarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickdrop:send-expiration-warnings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send expiration warning emails for QuickDrops expiring in the next 24 hours';

    protected EmailNotificationService $emailService;

    /**
     * Create a new command instance.
     */
    public function __construct(EmailNotificationService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending expiration warnings...');
        
        $this->emailService->sendExpirationWarnings();
        
        $this->info('Expiration warnings sent successfully.');
        
        return 0;
    }
}
