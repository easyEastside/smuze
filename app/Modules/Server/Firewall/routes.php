<?php

use App\Modules\Server\Firewall\Controllers\FirewallController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/firewall')->name('server.firewall.')->group(function (): void {
    Route::get('/', [FirewallController::class, 'index'])->name('index');
    Route::get('status', [FirewallController::class, 'status'])->name('status');
    Route::get('rules', [FirewallController::class, 'rules'])->name('rules');
    Route::post('allow', [FirewallController::class, 'allow'])->name('allow');
    Route::post('allow-standard-ports', [FirewallController::class, 'allowStandardPorts'])->name('allow-standard-ports');
    Route::post('deny', [FirewallController::class, 'deny'])->name('deny');
    Route::delete('rules/{rule}', [FirewallController::class, 'destroy'])->name('destroy');
    Route::post('enable', [FirewallController::class, 'enable'])->name('enable');
    Route::post('disable', [FirewallController::class, 'disable'])->name('disable');
    Route::post('install', [FirewallController::class, 'install'])->name('install');
});
