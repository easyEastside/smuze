<?php

namespace App\Modules\Leaderboard\Controllers;

use App\Models\User;
use Illuminate\View\View;

class LeaderboardController
{
    public function index(): View
    {
        $users = User::orderBy('credits', 'desc')
            ->limit(100)
            ->get(['id', 'name', 'credits', 'avatar_path']);

        return view('modules.leaderboard.index', compact('users'));
    }
}
