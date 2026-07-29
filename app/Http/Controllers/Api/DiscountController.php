<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;

class DiscountController extends Controller
{

public function index()
{

    return DiscountResource::collection(

        Discount::where('status',true)
            ->orderBy('name')
            ->get()

    );

}

}