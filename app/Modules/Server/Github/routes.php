<?php

use App\Modules\Server\Github\Controllers\GithubController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/github')->name('server.github.')->group(function (): void {
    Route::get('/', [GithubController::class, 'index'])->name('index');
    Route::post('deploy', [GithubController::class, 'deploy'])->name('deploy');
});
