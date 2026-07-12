<?php

use App\Modules\Leaderboard\Controllers\LeaderboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
});
