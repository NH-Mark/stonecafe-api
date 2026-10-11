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