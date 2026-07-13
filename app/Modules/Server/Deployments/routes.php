<?php

use App\Modules\Server\Deployments\Controllers\DeploymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/deployments')->name('server.deployments.')->group(function (): void {
    Route::get('/', [DeploymentController::class, 'index'])->name('index');
    Route::post('/', [DeploymentController::class, 'store'])->name('store');
    Route::patch('{deployment}', [DeploymentController::class, 'update'])->name('update');
    Route::delete('{deployment}', [DeploymentController::class, 'destroy'])->name('destroy');
    Route::post('{deployment}/run', [DeploymentController::class, 'run'])->name('run');
});
