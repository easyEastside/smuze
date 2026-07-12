<?php

use App\Modules\Server\Agent\Controllers\ServerAgentController;
use App\Modules\Server\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('servers', [ServerController::class, 'index'])->name('server.index');
    Route::get('servers/create', [ServerController::class, 'create'])->name('server.create');
    Route::post('servers', [ServerController::class, 'store'])->name('server.store');
    Route::get('servers/{server}/edit', [ServerController::class, 'edit'])->name('server.edit');
    Route::put('servers/{server}', [ServerController::class, 'update'])->name('server.update');
    Route::delete('servers/{server}', [ServerController::class, 'destroy'])->name('server.destroy');

    Route::get('servers/{server}/system', [ServerController::class, 'system'])->name('server.system');
    Route::get('servers/{server}/system/refresh', [ServerController::class, 'systemRefresh'])->name('server.system.refresh');
    Route::get('servers/{server}/system/test-connection', [ServerController::class, 'systemTestConnection'])->name('server.system.test-connection');

    Route::post('servers/{server}/update', [ServerController::class, 'updatePackages'])->name('server.update-packages');
    Route::post('servers/{server}/upgrade', [ServerController::class, 'upgradePackages'])->name('server.upgrade-packages');
    Route::post('servers/{server}/restart', [ServerController::class, 'restartServer'])->name('server.restart');
    Route::post('servers/{server}/stop', [ServerController::class, 'stopServer'])->name('server.stop');

    Route::post('servers/{server}/agent/token', [ServerAgentController::class, 'rotateToken'])->name('server.agent.token');
    Route::post('servers/{server}/agent/install', [ServerAgentController::class, 'install'])->name('server.agent.install');
    Route::delete('servers/{server}/agent', [ServerAgentController::class, 'disable'])->name('server.agent.disable');
});

Route::get('agent/download', [ServerAgentController::class, 'downloadBinary']);

require __DIR__.'/Firewall/routes.php';
require __DIR__.'/Apache/routes.php';
require __DIR__.'/Mysql/routes.php';
require __DIR__.'/Github/routes.php';
require __DIR__.'/Services/routes.php';
require __DIR__.'/Terminal/routes.php';
