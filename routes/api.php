<?php

use App\Modules\Server\Agent\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::prefix('agent')->group(function (): void {
    Route::post('heartbeat', [AgentController::class, 'heartbeat']);
    Route::post('metrics', [AgentController::class, 'metrics']);
    Route::get('commands/pending', [AgentController::class, 'pendingCommands']);
    Route::post('commands/{serverCommand}/output', [AgentController::class, 'commandOutput']);
    Route::post('commands/{serverCommand}/complete', [AgentController::class, 'completeCommand']);
});
