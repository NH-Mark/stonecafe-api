<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendRegistrationGroupEmails;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventRegistrationEmailController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'group' => [
                'required',
                Rule::in([
                    'selected',
                    'not_selected',
                    'waitlisted',
                ]),
            ],
        ]);

        // Dispatch after validation. The job will query the actual
        // eligible registrations on the server.
        SendRegistrationGroupEmails::dispatch(
            (int) $validated['event_id'],
            $validated['group']
        );

        return response()->json([
            'message' => 'The email campaign has been queued.',
        ], 202);
    }
}
