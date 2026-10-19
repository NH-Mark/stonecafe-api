<?php

namespace App\Services;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\EmployeeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthService
{
    // public function login(LoginRequest $request): array
    // {
    //     if (! Auth::attempt($request->validated())) {

    //         throw new UnauthorizedHttpException('', 'Invalid email or password');

    //     }

    //     $request->session()->regenerate();

    //     $user = $request->user()->load('location');

    //     return [
    //         'message' => 'Login successful',
    //         'user' => new UserResource($user),
    //     ];
    // }
    public function login(LoginRequest $request): array
    {
        if (! Auth::attempt($request->validated())) {
            throw new UnauthorizedHttpException('', 'Invalid email or password');
        }

        $user = Auth::user()->load('location');

        if (! $user->status) {
            Auth::logout();

            throw new UnauthorizedHttpException(
                '',
                'Your account is inactive. Please contact an administrator.'
            );
        }

        $token = $user->createToken('next-app');

        EmployeeLog::create([
            'user_id' => $user->id,
            'personal_access_token_id' => $token->accessToken->id,
            'signed_in_at' => Carbon::now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return [
            'message' => 'Login successful',
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
        ];
    }

    // public function logout($request): array
    // {
    //     Auth::logout();
    //     $request->session()->invalidate();
    //     $request->session()->regenerateToken();

    //     return [
    //         'message' => 'Logged out successfully'
    //     ];
    // }

    public function logout($request): array
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            EmployeeLog::query()
                ->where('user_id', $request->user()->id)
                ->where('personal_access_token_id', $token->id)
                ->whereNull('signed_out_at')
                ->latest('signed_in_at')
                ->first()?->update([
                    'signed_out_at' => Carbon::now(),
                ]);
        }

        $token?->delete();

        return [
            'message' => 'Logged out successfully'
        ];
    }

    public function me($request): UserResource
    {
        return new UserResource(
            $request->user()->load('location')
        );
    }

    public function forgotPassword($request): array
    {
        $request->validate([
            'email' => [
                'required',
                'email'
            ]
        ]);


        $status = Password::sendResetLink(
            $request->only('email')
        );


        if ($status !== Password::RESET_LINK_SENT) {
            throw new \Exception(
                __($status)
            );
        }


        return [
            'message' => __($status)
        ];
    }

    public function resetPassword($request): array
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);


        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, $password) {

                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );


        if ($status !== Password::PASSWORD_RESET) {
            throw new \Exception(__($status));
        }


        return [
            'message' => 'Password reset successfully'
        ];
    }
}
