<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with([
            'orderType',
            'orderSource',
            'customer',
            'table',
            'cashier',
            'location',
            'items.menuItem',
            'payments.paymentMethod',
            'payments.receivedBy',
        ])
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }
}
