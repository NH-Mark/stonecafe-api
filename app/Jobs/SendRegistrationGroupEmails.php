<?php

namespace App\Jobs;

use App\Mail\ThrowdownNotSelectedMail;
use App\Mail\ThrowdownSelectedMail;
use App\Mail\ThrowdownWaitlistedMail;
use App\Models\EventRegistration;
use App\Models\EventRegistrationEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendRegistrationGroupEmails implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $eventId,
        public string $group
    ) {}

    public function handle(): void
    {
        if (!in_array($this->group, [
            'selected',
            'not_selected',
            'waitlisted',
        ], true)) {
            return;
        }

        EventRegistration::query()
            ->with(['event', 'timeSlot'])
            ->where('event_id', $this->eventId)
            ->where('selection_status', $this->group)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($registrations) {
                foreach ($registrations as $registration) {
                    $this->sendToRegistration($registration);
                }
            });
    }

    private function sendToRegistration(
        EventRegistration $registration
    ): void {
        // Do not send this email type again if it was already sent.
        $alreadySent = EventRegistrationEmail::query()
            ->where('event_registration_id', $registration->id)
            ->where('email_type', $this->group)
            ->where('status', 'sent')
            ->exists();

        if ($alreadySent) {
            return;
        }

        $emailLog = EventRegistrationEmail::create([
            'event_registration_id' => $registration->id,
            'email_type' => $this->group,
            'recipient_email' => $registration->email,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        try {
            $mail = match ($this->group) {
                'selected' => new ThrowdownSelectedMail($registration),
                'not_selected' => new ThrowdownNotSelectedMail($registration),
                'waitlisted' => new ThrowdownWaitlistedMail($registration),
            };

            Mail::to($registration->email)->send($mail);

            $emailLog->update([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            $emailLog->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    60000
                ),
            ]);

            report($exception);
        }
    }
}