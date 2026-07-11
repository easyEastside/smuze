<?php

use App\Modules\Server\Terminal\Controllers\TerminalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::post('servers/{server}/terminal/session', [TerminalController::class, 'store'])->name('server.terminal.session');
});

Route::get('internal/terminal/sessions/{token}', [TerminalController::class, 'resolve'])->name('server.terminal.resolve');
