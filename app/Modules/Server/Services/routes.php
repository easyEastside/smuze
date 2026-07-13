<?php

use App\Modules\Server\Services\Controllers\ServicesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/services')->name('server.services.')->group(function (): void {
    Route::get('/', [ServicesController::class, 'index'])->name('index');
    Route::post('{service}/install', [ServicesController::class, 'install'])->whereIn('service', ['php', 'apache', 'nginx', 'mysql', 'node', 'nvm', 'npm', 'composer', 'python'])->name('install');
    Route::post('{service}/install/stream', [ServicesController::class, 'installStream'])->whereIn('service', ['php', 'apache', 'nginx', 'mysql', 'node', 'nvm', 'npm', 'composer', 'python'])->name('install.stream');
    Route::post('{service}/deinstall', [ServicesController::class, 'deinstall'])->whereIn('service', ['php', 'apache', 'nginx', 'mysql', 'node', 'nvm', 'npm', 'composer', 'python'])->name('deinstall');
    Route::post('{service}/deinstall/stream', [ServicesController::class, 'deinstallStream'])->whereIn('service', ['php', 'apache', 'nginx', 'mysql', 'node', 'nvm', 'npm', 'composer', 'python'])->name('deinstall.stream');
});
