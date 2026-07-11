<?php

use App\Modules\Logout\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;

Route::post('logout', [LogoutController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
