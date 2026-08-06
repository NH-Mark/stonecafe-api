<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Events\KitchenOrderUpdated;

class KitchenController extends Controller
{

    public function index()
    {

        $orders = Order::where(
                'ordered_at',
                '>=',
                now()->startOfDay()
            )
            ->where(
                'status',
                'confirmed'
            )
            ->where(function($query){

            $query
                ->whereIn(
                    'kitchen_status',
                    [
                        'pending',
                        'preparing',
                    ]
                )

                ->orWhere(function($q){

                    $q->where(
                        'kitchen_status',
                        'ready'
                    )
                    ->where(
                        
                        'completed_at',
                        '>=',
                        now()->subMinutes(10)
                    );

                });

            })

            ->with([

                'items.menuItem',
                'items.modifiers.modifier',
                'table',
                'customer'

            ])
            ->orderBy(
                'ordered_at',
                'asc'
            )
            ->get();


        return response()->json([
            'data'=>$orders
        ]);

    }

    public function updateStatus(
        Request $request,
        Order $order
    ){

        $request->validate([
            'kitchen_status'=>'required|in:pending,preparing,ready'
        ]);
        $status = $request->kitchen_status;


        $order->update([

            'kitchen_status'=>
                $request->kitchen_status,
            'completed_at'=>
                $status === 'ready'
                ? now()
                : null


        ]);


        $order->load([
            'items.menuItem',
            'items.modifiers.modifier',
            'table',
            'customer'
        ]);


        event(
            new KitchenOrderUpdated($order)
        );


        return response()->json([
            'data'=>$order
        ]);

    }

}