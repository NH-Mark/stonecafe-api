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

class KitchenOrderUpdated implements ShouldBroadcastNow
{

public function __construct(
    public Order $order
){}



public function broadcastOn()
{
    return new Channel('kitchen');
}



public function broadcastAs()
{
    return 'order.updated';
}



public function broadcastWith()
{
    return [
        'order'=>$this->order
    ];
}


}
