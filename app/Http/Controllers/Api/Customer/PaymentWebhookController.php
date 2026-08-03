<?php

namespace App\Http\Controllers\Api\Customer;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('SkipCash Webhook Received', [
            'payload' => $request->all()
        ]);


        // Example SkipCash response fields
        $paymentId = $request->input('id');
        $transactionId = $request->input('transactionId');
        $status = $request->input('status');


        if (!$transactionId) {

            Log::error('SkipCash webhook missing transactionId');

            return response()->json([
                'message' => 'Missing transactionId'
            ], 400);
        }


        $order = Order::where(
            'order_no',
            $transactionId
        )->first();


        if (!$order) {

            Log::error('Order not found', [
                'transactionId' => $transactionId
            ]);

            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }


        switch ($status) {

            case 'paid':

                $order->update([
                    'payment_status' => 'paid',
                    'payment_reference' => $paymentId,
                ]);

                break;


            case 'failed':

                $order->update([
                    'payment_status' => 'failed',
                ]);

                break;


            case 'cancelled':

                $order->update([
                    'payment_status' => 'cancelled',
                ]);

                break;
        }


        return response()->json([
            'success' => true
        ]);
    }
}