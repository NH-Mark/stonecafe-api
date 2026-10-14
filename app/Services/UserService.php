<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Assign multiple locations.
            $user->locations()->sync(
                $data['location_ids'] ?? []
            );

            // Assign role.
            if (!empty($data['role_id'])) {
                $role = Role::findOrFail($data['role_id']);

                $user->assignRole($role);
            }

            return $user->load([
                'roles',
                'locations',
            ]);
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            // Update password only when provided.
            if (!empty($data['password'])) {
                $user->update([
                    'password' => Hash::make($data['password']),
                ]);
            }

            // Update location assignments only when supplied.
            // An empty array removes all location assignments.
            if (array_key_exists('location_ids', $data)) {
                $user->locations()->sync(
                    $data['location_ids'] ?? []
                );
            }

            // Update role only when role_id is supplied.
            if (array_key_exists('role_id', $data)) {
                if ($data['role_id'] === null) {
                    $user->syncRoles([]);
                } else {
                    $role = Role::findOrFail($data['role_id']);

                    $user->syncRoles([$role]);
                }
            }

            return $user->load([
                'roles',
                'locations',
            ]);
        });
    }

    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Remove location assignments first.
            $user->locations()->detach();

            return $user->delete();
        });
    }
}
