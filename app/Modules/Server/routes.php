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
    Route::get('servers/{server}/terminal', [ServerController::class, 'terminal'])->name('server.terminal');

    Route::post('servers/{server}/agent/token', [ServerAgentController::class, 'rotateToken'])->name('server.agent.token');
    Route::post('servers/{server}/agent/install', [ServerAgentController::class, 'install'])->name('server.agent.install');
    Route::get('servers/{server}/agent/check-update', [ServerAgentController::class, 'checkUpdate'])->name('server.agent.check-update');
    Route::post('servers/{server}/agent/update', [ServerAgentController::class, 'updateAgent'])->name('server.agent.update');
    Route::delete('servers/{server}/agent', [ServerAgentController::class, 'disable'])->name('server.agent.disable');

    Route::get('servers/{server}/agent/health', [ServerAgentController::class, 'proxyHealth'])->name('server.agent.health');
    Route::get('servers/{server}/agent/metrics', [ServerAgentController::class, 'proxyMetrics'])->name('server.agent.metrics');
    Route::get('servers/{server}/agent/metrics/history', [ServerAgentController::class, 'proxyMetricsHistory'])->name('server.agent.metrics.history');
    Route::get('servers/{server}/agent/terminal-token', [ServerAgentController::class, 'terminalToken'])->name('server.agent.terminal-token');
    Route::post('servers/{server}/agent/action', [ServerAgentController::class, 'proxyAction'])->name('server.agent.action');
    Route::post('servers/{server}/agent/execute', [ServerAgentController::class, 'proxyExecute'])->name('server.agent.execute');
    Route::post('servers/{server}/agent/execute-stream', [ServerAgentController::class, 'proxyExecuteStream'])->name('server.agent.execute.stream');
});

Route::get('agent/download', [ServerAgentController::class, 'downloadBinary']);
Route::get('agent/version', [ServerAgentController::class, 'version']);

require __DIR__.'/Logs/routes.php';
require __DIR__.'/Firewall/routes.php';
require __DIR__.'/Apache/routes.php';
require __DIR__.'/Nginx/routes.php';
require __DIR__.'/Mysql/routes.php';
require __DIR__.'/Github/routes.php';
require __DIR__.'/Services/routes.php';
require __DIR__.'/Monitoring/routes.php';
require __DIR__.'/Cronjobs/routes.php';
require __DIR__.'/Files/routes.php';
require __DIR__.'/Backups/routes.php';
require __DIR__.'/Docker/routes.php';
