<?php

use App\Modules\GuestProfile\Controllers\GuestProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('users/{user}', [GuestProfileController::class, 'show'])->name('guest-profile.show');
});
