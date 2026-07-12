<?php

namespace App\Modules\Profile\Actions;

use App\Modules\Profile\Requests\UpdateAvatarRequest;
use Illuminate\Support\Facades\Storage;

class UpdateAvatar
{
    public function handle(UpdateAvatarRequest $request): void
    {
        $user = $request->user();
        $avatarPath = $request->file('avatar')->store('avatars', 'public');

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => $avatarPath])->save();
    }
}
