<?php

use App\Modules\Server\Terminal\Controllers\TerminalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('servers/{server}/terminal', [TerminalController::class, 'index'])->name('server.terminal.index');
    Route::post('servers/{server}/socket/session', [TerminalController::class, 'socket'])->name('server.socket.session');
});

Route::get('internal/terminal/sessions/{token}', [TerminalController::class, 'resolve'])->name('server.terminal.resolve');
