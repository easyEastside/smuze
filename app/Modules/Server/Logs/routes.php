<?php

use App\Modules\Server\Logs\Controllers\LogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/logs')->name('server.logs.')->group(function (): void {
    Route::get('/', [LogController::class, 'index'])->name('index');
    Route::post('fetch', [LogController::class, 'fetch'])->name('fetch');
    Route::post('stream', [LogController::class, 'stream'])->name('stream');
});
