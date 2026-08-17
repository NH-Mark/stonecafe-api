<?php

use App\Console\Commands\SendDailySalesSummary;
use App\Services\DailySalesEmailService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command(
//     'orders:send-daily-summary'
// )->dailyAt('23:00');

Schedule::call(function () {
    app(DailySalesEmailService::class)->send();
})->everyMinute();