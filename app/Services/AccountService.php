<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AccountService
{
    public function updateProfile(User $user, array $data): User
    {
        $user->update([

            'name' => $data['name'],

            'email' => $data['email'],

            'location_id' => $data['location_id'],

        ]);

        return $user->fresh('location');
    }

    public function changePassword(User $user, array $data): void
    {
        $user->update([

            'password' => Hash::make($data['password']),

        ]);
    }
}