<?php

use App\Modules\Achievements\Controllers\AchievementsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('achievements')->name('achievements.')->group(function (): void {
    Route::get('/', [AchievementsController::class, 'index'])->name('index');
});
