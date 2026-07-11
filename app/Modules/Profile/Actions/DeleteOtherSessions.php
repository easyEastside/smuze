<?php

namespace App\Modules\Profile\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeleteOtherSessions
{
    public function handle(Request $request): int
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
