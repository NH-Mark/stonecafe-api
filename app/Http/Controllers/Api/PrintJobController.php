<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrintJob;

class PrintJobController extends Controller
{


    public function pending()
    {

        $jobs =
            PrintJob::where(
                'status',
                'pending'
            )
            ->with([
                'order.items.modifiers',
                'order.payments'
            ])
            ->get();


        return response()->json($jobs);
    }



    public function done($id)
    {

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
