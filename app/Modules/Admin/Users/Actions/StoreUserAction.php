<?php

namespace App\Modules\Admin\Users\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class StoreUserAction
{
    /** @param array<string, mixed> $validated */
    public function handle(array $validated, ?UploadedFile $avatar = null): User
    {
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($validated['role']);

        if ($avatar) {
            $user->update([
                'avatar_path' => $avatar->store('avatars', 'public'),
            ]);
        }

        return $user;
    }
}
