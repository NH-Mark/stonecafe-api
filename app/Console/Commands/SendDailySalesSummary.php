<?php

namespace App\Console\Commands;

use App\Services\DailySalesEmailService;
use Illuminate\Console\Command;

class SendDailySalesSummary extends Command
{
    protected $signature = 'sales:send-daily-summary';

    protected $description = 'Send the daily sales summary email';

    public function handle(
        DailySalesEmailService $service
    ): int {

        $this->info('Sending daily sales summary...');

        $service->send();

        $this->info('Daily sales summary completed.');

        return self::SUCCESS;
    }
}