<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\PrintJob;
use Illuminate\Support\Facades\Log;

class PrintJobController extends Controller
{


    public function pending()
    {
        $jobs = PrintJob::where('status', 'pending')
            ->with([
                'order.orderType',
                'order.orderSource',
                'order.customer',
                'order.table',
                'order.cashier',
                'order.location',
                'order.items.menuItem',
                'order.items.modifiers.modifier',
                'order.payments.paymentMethod',
                'order.payments.receivedBy',
                'order.discounts.discount',
            ])
            ->get();

        return response()->json(
            $jobs->map(function ($job) {
                return [
                    'id' => $job->id,
                    'printer' => $job->printer,
                    'status' => $job->status,
                    'order' => (new OrderResource($job->order))->resolve(),
                ];
            })
        );
    }



    public function done($id)
    {
          Log::info("Print job completed", [
                'job_id' => $id
            ]);


        $job =
            PrintJob::findOrFail($id);


        $job->update([
            'status' => 'printed',
            'printed_at' => now()
        ]);


        return response()->json([
            "success" => true
        ]);
    }
}
