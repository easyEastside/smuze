<?php

namespace App\Modules\Admin\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DeleteUserAction
{
    public function handle(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->delete();
    }
}
