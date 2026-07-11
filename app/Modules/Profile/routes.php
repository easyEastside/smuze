<?php

use App\Modules\Profile\Controllers\ProfileAccountController;
use App\Modules\Profile\Controllers\ProfileAvatarController;
use App\Modules\Profile\Controllers\ProfileController;
use App\Modules\Profile\Controllers\ProfileCreditsController;
use App\Modules\Profile\Controllers\ProfilePasswordController;
use App\Modules\Profile\Controllers\ProfileSessionsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profile/password', [ProfilePasswordController::class, 'update'])->name('profile.password.update');
    Route::patch('profile/avatar', [ProfileAvatarController::class, 'update'])->name('profile.avatar.update');
    Route::delete('profile/avatar', [ProfileAvatarController::class, 'destroy'])->name('profile.avatar.destroy');
    Route::delete('profile/sessions', [ProfileSessionsController::class, 'destroyOther'])->name('profile.sessions.destroy-other');
    Route::delete('profile', [ProfileAccountController::class, 'destroy'])->name('profile.destroy');
    Route::get('profile/credits', [ProfileCreditsController::class, 'index'])->name('profile.credits');
});
