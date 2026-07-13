<?php

use App\Modules\Server\Monitoring\Controllers\MonitoringController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/monitoring')->name('server.monitoring.')->group(function (): void {
    Route::get('/', [MonitoringController::class, 'index'])->name('index');
    Route::get('processes', [MonitoringController::class, 'processes'])->name('processes');
    Route::post('processes/kill', [MonitoringController::class, 'killProcess'])->name('processes.kill');
    Route::get('services', [MonitoringController::class, 'services'])->name('services');
    Route::post('services/action', [MonitoringController::class, 'serviceAction'])->name('services.action');
});
