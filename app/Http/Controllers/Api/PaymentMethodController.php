<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{

    public function __construct() {}
    
    public function index()
    {
        $payment_methods = PaymentMethod::get();
        return PaymentMethodResource::collection($payment_methods);
    }
  
}
