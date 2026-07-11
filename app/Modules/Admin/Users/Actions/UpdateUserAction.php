<?php

namespace App\Modules\Admin\Users\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UpdateUserAction
{
    /** @param array<string, mixed> $validated */
    public function handle(
        User $user,
        array $validated,
        ?UploadedFile $avatar = null,
        bool $removeAvatar = false,
    ): User {
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (filled($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($removeAvatar && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);

            $data['avatar_path'] = null;
        }

        if ($avatar) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $data['avatar_path'] = $avatar->store('avatars', 'public');
        }

        $user->update($data);
        $user->syncRoles($validated['role']);

        return $user;
    }
}
