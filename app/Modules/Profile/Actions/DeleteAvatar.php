<?php

namespace App\Modules\Profile\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeleteAvatar
{
    public function handle(Request $request): void
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => null])->save();
    }
}
