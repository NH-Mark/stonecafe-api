<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventRegistrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => $this->id,

            'event_id' =>
                $this->event_id,

            'event_time_slot_id' =>
                $this->event_time_slot_id,

            'full_name' =>
                $this->full_name,

            'email' =>
                $this->email,

            'phone' =>
                $this->phone,

            'gender' =>
                $this->gender,

            'company' =>
                $this->company,

            'status' =>
                $this->status,
            
            'selection_status' =>
                $this->selection_status,

            'payment_status' =>
                $this->payment_status,

            'payment_amount' =>
                $this->payment_amount,

            'payment_currency' =>
                $this->payment_currency,

            'payment_reference' =>
                $this->payment_reference,

            'payment_transaction_id' =>
                $this->payment_transaction_id,

            'paid_at' =>
                $this->paid_at,

            'seat_count' =>
                $this->seat_count,

            'metadata' =>
                $this->metadata,

            'mail_status' => $this->whenLoaded(
                'emailLogs',
                function () {
                    $latest = $this->emailLogs
                        ->sortByDesc('id')
                        ->first();

                    return $latest?->status;
                }
            ),

            'mail_logs' => $this->whenLoaded(
                'emailLogs',
                function () {
                    return $this->emailLogs
                        ->sortByDesc('id')
                        ->values()
                        ->map(function ($log) {
                            return [
                                'id' => $log->id,
                                'email_type' => $log->email_type,
                                'recipient_email' => $log->recipient_email,
                                'status' => $log->status,
                                'queued_at' => $log->queued_at,
                                'sent_at' => $log->sent_at,
                                'failed_at' => $log->failed_at,
                                'error_message' => $log->error_message,
                            ];
                        });
                }
            ),

            'event' => $this->whenLoaded(
                'event',
                function () {
                    return [
                        'id' =>
                            $this->event->id,

                        'name' =>
                            $this->event->name,

                        'slug' =>
                            $this->event->slug,

                        'date' =>
                            $this->event->date,
                    ];
                }
            ),

            'timeSlot' => $this->whenLoaded(
                'timeSlot',
                function () {
                    return [
                        'id' =>
                            $this->timeSlot->id,

                        'name' =>
                            $this->timeSlot->name,

                        'start_time' =>
                            $this->timeSlot->start_time,

                        'end_time' =>
                            $this->timeSlot->end_time,

                        'capacity' =>
                            $this->timeSlot->capacity,

                        'is_active' =>
                            $this->timeSlot->is_active,
                    ];
                }
            ),

            'created_at' =>
                $this->created_at,

            'updated_at' =>
                $this->updated_at,
        ];
    }
}