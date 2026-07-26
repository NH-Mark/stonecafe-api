<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderTypeResource;
use App\Models\OrderType;

class OrderTypeController extends Controller
{

    public function __construct(
        
    ) {}

    public function index()
    {
        $order_types = OrderType::get();

        return OrderTypeResource::collection($order_types);
    }

    
}
