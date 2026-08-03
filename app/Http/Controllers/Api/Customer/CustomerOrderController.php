<?php

namespace App\Http\Controllers\Api\Customer;


use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CustomerOrderService;
use Illuminate\Http\Request;


class CustomerOrderController extends Controller
{


    public function __construct(
        private CustomerOrderService $service
    ){}



    public function store(Request $request)
    {


        $validated = $request->validate([


            'customer.name'=>'required',

            'customer.phone'=>'required',


            'items'=>'required|array',


            'payment'=>'required',


            'subtotal'=>'required|numeric',

            'tax_amount'=>'nullable|numeric',

            'total_amount'=>'required|numeric',


            'notes'=>'nullable|string',
            'order_type'=>'required|string'
        ]);



        $order =
            $this->service->create(
                $validated
            );



        return response()->json([

            'success'=>true,

            'message'=>'Order placed successfully',

            'data'=>$order

        ],201);


    }

    public function show($id)
    {
        $order = Order::findOrFail($id);

        return response()->json([
            'id' => $order->id,
            'order_no' => $order->order_no,
            'payment_status' => $order->payment_status,
            'payment_reference' => $order->payment_reference,
            'total_amount' => $order->total_amount,
        ]);
    }


}