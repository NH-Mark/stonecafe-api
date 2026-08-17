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
        ]);

        $settings = $this->service->updateSettings(
            $validated['enabled'],
            $validated['recipients'],
            $validated['send_time']
        );

        return response()->json([
            'message' =>
                'Daily sales email settings updated successfully.',
            'data' => $settings,
        ]);
    }

    public function sendNow()
    {
        $this->service->sendNow();

        return response()->json([
            'message' =>
                'Daily sales summary email sent successfully.',
        ]);
    }
}