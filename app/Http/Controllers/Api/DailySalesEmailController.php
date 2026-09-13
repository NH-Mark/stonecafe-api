<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DailySalesEmailService;
use Illuminate\Http\Request;

class DailySalesEmailController extends Controller
{
    public function __construct(
        private DailySalesEmailService $service
    ) {}

    public function show()
    {
        return response()->json([
            'data' => $this->service->getSettings(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => [
                'required',
                'boolean',
            ],

            'recipients' => [
                'required',
                'array',
                'min:1',
            ],

            'recipients.*' => [
                'required',
                'email',
            ],

            'send_time' => [
                'required',
                'date_format:H:i',
            ],
            'date_range'=>['required','string'],

            'from_date' => [
                'required',
                'date',
            ],

            'to_date' => [
                'required',
                'date',
                'after_or_equal:from_date',
            ],
        ]);

        $settings = $this->service->updateSettings(
            $validated['enabled'],
            $validated['recipients'],
            $validated['send_time'],
            $validated['date_range'],
            $validated['from_date'],
            $validated['to_date']
        );

        return response()->json([
            'message' =>
                'Daily sales email settings updated successfully.',
            'data' => $settings,
        ]);
    }

    public function sendNow(Request $request)
    {
        $validated = $request->validate([
            'from_date' => [
                'required',
                'date',
            ],

            'to_date' => [
                'required',
                'date',
                'after_or_equal:from_date',
            ],
            'date_range'=>[
                'required',
                'string'
            ],
        ]);

        $this->service->sendNow(
            $validated['date_range'],
            $validated['from_date'],
            $validated['to_date']
        );

        return response()->json([
            'message' =>
                'Daily sales summary email sent successfully.',
        ]);
    }
}