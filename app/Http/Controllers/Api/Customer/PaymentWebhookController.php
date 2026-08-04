<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PrintJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('SkipCash Webhook Received', [
            'payload' => $request->all()
        ]);


        $paymentId =
            $request->input('PaymentId');

        $transactionId =
            $request->input('TransactionId');

        $statusId =
            (int) $request->input('StatusId');



        if (!$transactionId) {

            Log::error('SkipCash webhook missing TransactionId');

            return response()->json([
                'message' => 'Missing TransactionId'
            ], 400);

        }



        $order = Order::where(
            'order_no',
            $transactionId
        )->first();



        if (!$order) {

            Log::error('Order not found', [
                'TransactionId' => $transactionId
            ]);

            return response()->json([
                'message' => 'Order not found'
            ], 404);

        }




        /*
        |--------------------------------------------------------------------------
        | StatusId 2 = Paid
        |--------------------------------------------------------------------------
        */

        if ($statusId === 2) {


            // Prevent duplicate webhook processing
            if ($order->payment_status !== 'paid') {


                $order->update([

                    'payment_status' =>
                        'paid',

                    'payment_reference' =>
                        $paymentId,

                    'status' =>
                        Order::STATUS_CONFIRMED,
                    'paid_at' => now(),


                ]);



                PrintJob::firstOrCreate(
                    [
                        'order_id' =>
                            $order->id,

                        'printer' =>
                            'EPSON TM-T20III Receipt',
                    ],
                    [
                        'status' =>
                            'pending',
                    ]
                );


                Log::info('SkipCash payment completed', [
                    'order_id' => $order->id
                ]);

            }


        } else {


            // Any other status = not paid

            $order->update([

                'payment_status' =>
                    'unpaid',

            ]);


            Log::warning('SkipCash payment failed', [
                'order_id' => $order->id,
                'status_id' => $statusId
            ]);

        }




        return response()->json([
            'success' => true
        ]);

    }
}