<?php

namespace App\Services;

use App\Mail\DailySalesSummaryMail;
use App\Models\DailySalesEmailSetting;
use App\Models\Location;
use App\Models\Order;
use Illuminate\Http\Request;
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
                    ->startOfWeek(\Carbon\Carbon::SUNDAY)
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

    // private function sendEmail(
    //     array $recipients,
    //     string $dateRange,
    //     string $fromDate,
    //     string $toDate
    // ): void {

    //     $dashboardService =
    //         app(SalesDashboardService::class);

    //     $request = request();

    //     Log::info('Daily sales email sendEmail start', [
    //         'date_range' => $dateRange,
    //         'from_date' => $fromDate,
    //         'to_date' => $toDate,
    //         'recipients_count' => count($recipients),
    //     ]);

    //     $locationIds = Order::query()
    //         ->where('status', Order::STATUS_COMPLETED)
    //         ->whereNotNull('location_id')
    //         ->whereBetween('ordered_at', [
    //             $fromDate . ' 00:00:00',
    //             $toDate . ' 23:59:59',
    //         ])
    //         ->distinct()
    //         ->pluck('location_id');

    //     Log::info('Daily sales email location ids resolved', [
    //         'count' => $locationIds->count(),
    //         'location_ids' => $locationIds->values()->all(),
    //         'from_date' => $fromDate,
    //         'to_date' => $toDate,
    //     ]);

    //     $locations = Location::query()
    //         ->whereIn('id', $locationIds)
    //         ->orderBy('name')
    //         ->get(['id', 'name']);

    //     Log::info('Daily sales email locations loaded', [
    //         'count' => $locations->count(),
    //         'locations' => $locations->map(fn ($location) => [
    //             'id' => $location->id,
    //             'name' => $location->name,
    //         ])->all(),
    //     ]);



    //     // Fallback to one overall report if no location has data in the range.
    //     if ($locations->isEmpty()) {
    //         Log::info('Daily sales email no locations found, sending overall report', [
    //             'from_date' => $fromDate,
    //             'to_date' => $toDate,
    //         ]);

    //         $dashboard = $dashboardService->dashboard(new Request([
    //             'range' => 'custom',
    //             'start_date' => $fromDate,
    //             'end_date' => $toDate,
    //         ]));

    //         Mail::to($recipients)->send(
    //             new DailySalesSummaryMail(
    //                 $dashboard,
    //                 $dateRange,
    //                 $fromDate,
    //                 $toDate,
    //                 null
    //             )
    //         );

    //         Log::info('Daily sales email sent overall report', [
    //             'to' => $recipients,
    //             'from_date' => $fromDate,
    //             'to_date' => $toDate,
    //         ]);

    //         return;
    //     }

    //     foreach ($locations as $location) {
    //         Log::info('Daily sales email preparing location email', [
    //             'location_id' => $location->id,
    //             'location_name' => $location->name,
    //             'from_date' => $fromDate,
    //             'to_date' => $toDate,
    //         ]);

    //         $request = new Request([
    //             'range' => 'custom',
    //             'start_date' => $fromDate,
    //             'end_date' => $toDate,
    //             'location_id' => $location->id,
    //         ]);

    //         $dashboard = $dashboardService->dashboard($request);

    //         Mail::to($recipients)->send(
    //             new DailySalesSummaryMail(
    //                 $dashboard,
    //                 $dateRange,
    //                 $fromDate,
    //                 $toDate,
    //                 $location->name
    //             )
    //         );

    //         Log::info('Daily sales email sent location report', [
    //             'location_id' => $location->id,
    //             'location_name' => $location->name,
    //             'from_date' => $fromDate,
    //             'to_date' => $toDate,
    //         ]);
    //     }
    // }

    
private function sendEmail(
    array $recipients,
    string $dateRange,
    string $fromDate,
    string $toDate
): void {

    $dashboardService = app(SalesDashboardService::class);

    Log::info('Daily sales email sendEmail start', [
        'date_range' => $dateRange,
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'recipients_count' => count($recipients),
    ]);

    // Get locations having completed orders within the date range.
    $locationIds = Order::query()
        ->where('status', Order::STATUS_COMPLETED)
        ->whereNotNull('location_id')
        ->whereBetween('ordered_at', [
            $fromDate . ' 00:00:00',
            $toDate . ' 23:59:59',
        ])
        ->distinct()
        ->pluck('location_id');

    $locations = Location::query()
        ->whereIn('id', $locationIds)
        ->orderBy('name')
        ->get(['id', 'name']);

    Log::info('Daily sales email locations loaded', [
        'count' => $locations->count(),
        'locations' => $locations->map(fn ($location) => [
            'id' => $location->id,
            'name' => $location->name,
        ])->all(),
    ]);

    /*
     * SEND OVERALL REPORT
     *
     * Only send when orders exist in multiple locations.
     * If no locations have orders, preserve the overall fallback.
     */
    if ($locations->count() > 1 || $locations->isEmpty()) {

        Log::info('Preparing overall sales report');

        $overallRequest = new Request([
            'range' => 'custom',
            'start_date' => $fromDate,
            'end_date' => $toDate,
        ]);

        $overallDashboard = $dashboardService->dashboard(
            $overallRequest
        );

        Mail::to($recipients)->send(
            new DailySalesSummaryMail(
                $overallDashboard,
                $dateRange,
                $fromDate,
                $toDate,
                null
            )
        );

        Log::info('Overall sales report email sent');
    }

    /*
     * SEND SEPARATE LOCATION REPORTS
     */
    foreach ($locations as $location) {

        Log::info('Preparing location sales report', [
            'location_id' => $location->id,
            'location_name' => $location->name,
        ]);

        $locationRequest = new Request([
            'range' => 'custom',
            'start_date' => $fromDate,
            'end_date' => $toDate,
            'location_id' => $location->id,
        ]);

        $dashboard = $dashboardService->dashboard(
            $locationRequest
        );

        Mail::to($recipients)->send(
            new DailySalesSummaryMail(
                $dashboard,
                $dateRange,
                $fromDate,
                $toDate,
                $location->name
            )
        );

        Log::info('Location sales report email sent', [
            'location_id' => $location->id,
            'location_name' => $location->name,
        ]);
    }
}

}