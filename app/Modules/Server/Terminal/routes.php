<?php

use App\Modules\Server\Terminal\Controllers\TerminalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('servers/{server}/terminal', [TerminalController::class, 'index'])->name('server.terminal.index');
    Route::post('servers/{server}/terminal/session', [TerminalController::class, 'store'])->name('server.terminal.session');
    Route::post('servers/{server}/metrics/session', [TerminalController::class, 'metrics'])->name('server.metrics.session');
});

Route::get('internal/terminal/sessions/{token}', [TerminalController::class, 'resolve'])->name('server.terminal.resolve');
