<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailySalesSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $dashboard,
        public string $dateRange,
        public string $fromDate,
        public string $toDate
    ) {}

    public function build()
    {
        $labels = [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'custom' => '',
        ];

        $rangeLabel =
            $labels[$this->dateRange]
            ?? 'Sales Report';

        $dateLabel =
            $this->fromDate === $this->toDate
                ? $this->fromDate
                : $this->fromDate . ' - ' . $this->toDate;

        return $this
            ->subject(
                'Sales Summary — ' .
                $rangeLabel .
                ' (' .
                $dateLabel .
                ')'
            )
            ->view('emails.daily-sales-summary');
    }
}