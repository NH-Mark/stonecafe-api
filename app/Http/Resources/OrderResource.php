<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,

            'type' => $this->orderType?->name,
            'source' => $this->orderSource?->name,

            'customer' => $this->customer?->name,
            'table' => $this->table?->name,
            'cashier' => $this->cashier?->name,
            'location' => $this->location?->name,

            'status' => $this->status,
            'payment_status' => $this->payment_status,

            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'service_charge' => $this->service_charge,
            'total' => $this->total_amount,

            'notes' => $this->notes,

            'ordered_at' => optional($this->ordered_at)->format('Y-m-d H:i:s'),

            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'menu_item' => $item->menuItem?->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'notes' => $item->notes,
                ];
            }),
            'payments' => $this->payments->map(function ($payment) {

                return [
                    'id' => $payment->id,
                    'method' => $payment->paymentMethod?->name,
                    'amount' => $payment->amount,
                    'reference' => $payment->reference,
                    'received_by' => $payment->receivedBy?->name,
                    'paid_at' => $payment->paid_at?->format(
                        'Y-m-d H:i:s'
                    ),
                ];
            }),
        ];
    }
}
