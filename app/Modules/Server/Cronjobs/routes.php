<?php

use App\Modules\Server\Cronjobs\Controllers\CronjobController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/cronjobs')->name('server.cronjobs.')->group(function (): void {
    Route::get('/', [CronjobController::class, 'index'])->name('index');
    Route::get('remote', [CronjobController::class, 'remote'])->name('remote');
    Route::post('/', [CronjobController::class, 'store'])->name('store');
    Route::post('sync', [CronjobController::class, 'sync'])->name('sync');
    Route::patch('{cronjob}', [CronjobController::class, 'update'])->name('update');
    Route::delete('{cronjob}', [CronjobController::class, 'destroy'])->name('destroy');
    Route::post('{cronjob}/toggle', [CronjobController::class, 'toggle'])->name('toggle');
    Route::post('{cronjob}/run', [CronjobController::class, 'run'])->name('run');
});
