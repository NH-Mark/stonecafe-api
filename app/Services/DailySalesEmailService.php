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
                'date_range' => 'yesterday',
                'from_date' => now()->subDay()->toDateString(),
                'to_date' => now()->subDay()->toDateString(),
            ];
        }

        return [
            'enabled' => $settings->enabled,
            'recipients' => $settings->recipients ?? [],
            'date_range' => $settings->date_range,
            'send_time' => substr(
                $settings->send_time,
                0,
                5
            ),
            'from_date' => $settings->from_date,
            'to_date' => $settings->to_date,
        ];
    }

    public function updateSettings(
        bool $enabled,
        array $recipients,
        string $sendTime,
        string $dateRange,
        string $fromDate,
        string $toDate
    ): array {

        $recipients = collect($recipients)
            ->map(
                fn ($email) =>
                trim(strtolower($email))
            )
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $settings = DailySalesEmailSetting::updateOrCreate(
            ['id' => 1],
            [
                'enabled' => $enabled,
                'recipients' => $recipients,
                'send_time' => $sendTime,
                'date_range' => $dateRange,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ]
        );

        return [
            'enabled' => $settings->enabled,
            'recipients' => $settings->recipients ?? [],
            'send_time' => substr(
                $settings->send_time,
                0,
                5
            ),
            'date_range' => $settings->date_range,
            'from_date' => $settings->from_date,
            'to_date' => $settings->to_date,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Date Range
    |--------------------------------------------------------------------------
    */

    private function resolveDateRange(
        string $dateRange,
        ?string $savedFromDate = null,
        ?string $savedToDate = null
    ): array {
        $today = now();

        return match ($dateRange) {

            'today' => [
                'from_date' => $today->toDateString(),
                'to_date' => $today->toDateString(),
            ],

            'yesterday' => [
                'from_date' => $today->copy()
                    ->subDay()
                    ->toDateString(),

                'to_date' => $today->copy()
                    ->subDay()
                    ->toDateString(),
            ],

            'this_week' => [
                'from_date' => $today->copy()
                    ->startOfWeek()
                    ->toDateString(),

                'to_date' => $today->toDateString(),
            ],

            'this_month' => [
                'from_date' => $today->copy()
                    ->startOfMonth()
                    ->toDateString(),

                'to_date' => $today->toDateString(),
            ],

            'last_month' => [
                'from_date' => $today->copy()
                    ->subMonthNoOverflow()
                    ->startOfMonth()
                    ->toDateString(),

                'to_date' => $today->copy()
                    ->subMonthNoOverflow()
                    ->endOfMonth()
                    ->toDateString(),
            ],

            'custom' => [
                'from_date' => $savedFromDate,
                'to_date' => $savedToDate,
            ],

            default => throw new \InvalidArgumentException(
                "Invalid date range: {$dateRange}"
            ),
        };
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

        if ($configuredTime !== $currentTime) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Always calculate predefined ranges using today's date
        |--------------------------------------------------------------------------
        */

        $dateRange = $settings->date_range;

        $dates = $this->resolveDateRange(
            $dateRange,
            $settings->from_date,
            $settings->to_date
        );

        Log::info('Daily sales email date range resolved', [
            'date_range' => $dateRange,
            'from_date' => $dates['from_date'],
            'to_date' => $dates['to_date'],
        ]);

        $this->sendEmail(
            $recipients,
            $dateRange,
            $dates['from_date'],
            $dates['to_date']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Manual Test Send
    |--------------------------------------------------------------------------
    */

    public function sendNow(
        string $dateRange,
        string $fromDate,
        string $toDate
    ): void {

        $settings = DailySalesEmailSetting::first();

        if (!$settings) {
            throw new \Exception(
                'Daily sales email settings not configured.'
            );
        }

        $recipients = $settings->recipients ?? [];

        if (empty($recipients)) {
            throw new \Exception(
                'No recipient emails configured.'
            );
        }

        $dates = $this->resolveDateRange(
            $dateRange,
            $fromDate,
            $toDate
        );

        $this->sendEmail(
            $recipients,
            $dateRange,
            $dates['from_date'],
            $dates['to_date']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actual Email
    |--------------------------------------------------------------------------
    */

    private function sendEmail(
        array $recipients,
        string $dateRange,
        string $fromDate,
        string $toDate
    ): void {

        $dashboardService =
            app(SalesDashboardService::class);

        $request = request();

        $request->merge([
            'range' => 'custom',
            'start_date' => $fromDate,
            'end_date' => $toDate,
        ]);

        $dashboard =
            $dashboardService->dashboard($request);

        Mail::to($recipients)
            ->send(
                new DailySalesSummaryMail(
                    $dashboard,
                    $dateRange,
                    $fromDate,
                    $toDate
                )
            );
    }
}