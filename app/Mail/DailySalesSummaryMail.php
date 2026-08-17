<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailySalesSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $dashboard,
        public Carbon $reportDate
    ) {}

    public function build()
    {
        return $this
            ->subject(
                'Daily Sales Summary — ' .
                $this->reportDate->format('d M Y')
            )
            ->view('emails.daily-sales-summary');
    }
}