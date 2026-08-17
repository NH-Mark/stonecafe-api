<?php

namespace App\Services;

use App\Mail\DailySalesSummaryMail;
use App\Models\DailySalesEmailSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DailySalesEmailService
{
    public function getSettings(): array
    {
        $settings = DailySalesEmailSetting::first();

        if (!$settings) {
            return [
                'enabled' => false,
                'recipients' => [],
                'send_time' => '18:00',
            ];
        }

        return [
            'enabled' => $settings->enabled,
            'recipients' => $settings->recipients ?? [],
            'send_time' => substr(
                $settings->send_time,
                0,
                5
            ),
        ];
    }

    public function updateSettings(
        bool $enabled,
        array $recipients,
        string $sendTime
    ): array {

        $recipients = collect($recipients)
            ->map(
                fn($email) =>
                trim(strtolower($email))
            )
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $settings =
            DailySalesEmailSetting::updateOrCreate(
                ['id' => 1],
                [
                    'enabled' => $enabled,
                    'recipients' => $recipients,
                    'send_time' => $sendTime,
                ]
            );

        return [
            'enabled' => $settings->enabled,
            'recipients' =>
            $settings->recipients ?? [],
            'send_time' => substr(
                $settings->send_time,
                0,
                5
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Scheduled Send
    |--------------------------------------------------------------------------
    */

    public function send(): void
    {
        Log::info('Daily sales scheduler checked', [
            'time' => now()->format('Y-m-d H:i:s'),
        ]);

        $settings = DailySalesEmailSetting::first();

        if (!$settings || !$settings->enabled) {
            Log::info('Daily sales email disabled');
            return;
        }

        $recipients = $settings->recipients ?? [];

        if (empty($recipients)) {
            Log::info('Daily sales email has no recipients');
            return;
        }

        $configuredTime = substr(
            $settings->send_time,
            0,
            5
        );

        $currentTime = now()->format('H:i');

        Log::info('Daily sales email time check', [
            'configured' => $configuredTime,
            'current' => $currentTime,
        ]);

        if ($configuredTime !== $currentTime) {
            return;
        }

        Log::info('Sending daily sales email', [
            'recipients' => $recipients,
        ]);

        $this->sendEmail($recipients);
    }
    /*
    |--------------------------------------------------------------------------
    | Manual Test Send
    |--------------------------------------------------------------------------
    */

    public function sendNow(): void
    {
        $settings =
            DailySalesEmailSetting::first();

        if (!$settings) {
            throw new \Exception(
                'Daily sales email settings not configured.'
            );
        }

        $recipients =
            $settings->recipients ?? [];

        if (empty($recipients)) {
            throw new \Exception(
                'No recipient emails configured.'
            );
        }

        // IMPORTANT:
        // Do not check enabled or send_time here.
        // This is a manual test.

        $this->sendEmail($recipients);
    }

    /*
    |--------------------------------------------------------------------------
    | Actual Email
    |--------------------------------------------------------------------------
    */

    private function sendEmail(
        array $recipients
    ): void {

        $dashboardService =
            app(SalesDashboardService::class);

        $request = request();

        $request->merge([
            'range' => 'yesterday',
        ]);

        $dashboard =
            $dashboardService
            ->dashboard($request);

        Mail::to($recipients)
            ->send(
                new DailySalesSummaryMail(
                    $dashboard,
                    now()->subDay()
                )
            );
    }
}
