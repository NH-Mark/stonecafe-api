<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;

class EmployeeLogController extends Controller
{
    public function index()
    {
        return EmployeeLog::query()
            ->with([
                'user:id,name,email,status',
                'user.roles:id,name',
            ])
            ->latest('signed_in_at')
            ->get();
    }

    public function suspend(User $user): JsonResponse
    {
        return $this->updateStaffStatus($user, false);
    }

    public function restore(User $user): JsonResponse
    {
        return $this->updateStaffStatus($user, true);
    }

    protected function updateStaffStatus(
        User $user,
        bool $status
    ): JsonResponse {
        $timestamp = Carbon::now();

        $user->forceFill([
            'status' => $status,
        ])->save();

        if (! $status) {
            $user->tokens()->delete();

            EmployeeLog::query()
                ->where('user_id', $user->id)
                ->whereNull('signed_out_at')
                ->update([
                    'signed_out_at' => $timestamp,
                ]);
        }

        return response()->json([
            'message' => $status
                ? 'Staff restored successfully.'
                : 'Staff suspended successfully.',
            'data' => [
                'user_id' => $user->id,
                'status' => (bool) $user->status,
            ],
        ]);
    }
}