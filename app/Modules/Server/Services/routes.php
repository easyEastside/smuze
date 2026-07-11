<?php

use App\Modules\Server\Services\Controllers\ServicesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/services')->name('server.services.')->group(function (): void {
    Route::get('/', [ServicesController::class, 'index'])->name('index');
    Route::post('{service}/install', [ServicesController::class, 'install'])->whereIn('service', ['php', 'apache', 'mysql', 'node', 'nvm', 'npm', 'composer'])->name('install');
    Route::post('{service}/deinstall', [ServicesController::class, 'deinstall'])->whereIn('service', ['php', 'apache', 'mysql', 'node', 'nvm', 'npm', 'composer'])->name('deinstall');
});
