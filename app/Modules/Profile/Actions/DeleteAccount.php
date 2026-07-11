<?php

namespace App\Modules\Profile\Actions;

use App\Modules\Profile\Requests\DeleteAccountRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteAccount
{
    public function handle(DeleteAccountRequest $request): void
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
