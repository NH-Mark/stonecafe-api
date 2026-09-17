<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class KitchenOrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('kitchen');
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    public function broadcastWith(): array
    {
        Log::info(
            'Broadcasting kitchen order update '.$this->order->order_no
        );

        return [
            'order_id' => $this->order->id,
        ];
    }
}