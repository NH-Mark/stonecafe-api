<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PrintJob;
use App\Services\SkipCashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{


    public function skipcash(
        Request $request,
        SkipCashService $skipcash
    ) {

        $order =
            Order::with('customer')
            ->findOrFail(
                $request->order_id
            );


        $result =
            $skipcash->createPayment(

                (float) $order->total_amount,

                $order->order_no,

                $order->customer

            );


        $order->update([

            'payment_reference' =>
            $result['id'] ?? null

        ]);


        return response()->json([

            'payment_url' =>
            $result['payUrl']

        ]);
    }


    public function skipcashCallback(
        Request $request,
        $order
    ) {

        Log::info('SkipCash Callback', [
            'data' => $request->all(),
            'url_order' => $order
        ]);


        $order = Order::where(
            'order_no',
            $request->transId
        )->firstOrFail();


        if (
            $request->status === 'Paid' &&
            (int) $request->statusId === 2
        ) {

            // $order->update([
            //     'payment_status' => 'paid',
            //     'payment_reference' => $request->id,
            //     'status' => Order::STATUS_CONFIRMED,
            // ]);

            // PrintJob::firstOrCreate(
            //     [
            //         'order_id' => $order->id,
            //         'printer' => 'EPSON TM-T20III Receipt',
            //     ],
            //     [
            //         'status' => 'pending',
            //     ]
            // );


            return redirect(
                config('app.customer_url')
                    . '/order-success/'
                    . $order->id
            );
        }


        $order->update([
            'payment_status' => 'failed',
        ]);


        return redirect(
            config('app.customer_url')
                . '/order-failed/'
                . $order->id
        );
    }

    public function retrySkipcash(
        Order $order,
        SkipCashService $skipcash
    ) {

        if ($order->payment_status === 'paid') {

            return response()->json([
                'message' => 'Order already paid'
            ], 400);
        }


        $result = $skipcash->createPayment(

            (float) $order->total_amount,

            $order->order_no,

            $order->customer

        );



        $order->update([

            'payment_reference' =>
            $result['id']

        ]);


        return response()->json([

            'payment_url' =>
            $result['payUrl']

        ]);
    }
}
