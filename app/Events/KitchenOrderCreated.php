<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class KitchenOrderCreated implements ShouldBroadcastNow
{
     use Dispatchable, SerializesModels;


    public function __construct(
        public Order $order
    ){}


    public function broadcastOn()
    {
        return new Channel('kitchen');
    }


    public function broadcastAs()
    {
        return 'order.created';
    }


    public function broadcastWith()
    {
        Log::info('Broadcasting order '.$this->order->order_no);

        return [

            'order'=>$this->order->load([
                'items.menuItem',
                'items.modifiers.modifier',
                'payments.paymentMethod',
                'discounts.discount',
                'customer',
                'table',
                'cashier',
                'location',
                'orderType',
                'orderSource'
            ])

        ];
    }
}
