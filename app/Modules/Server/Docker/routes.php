<?php

use App\Modules\Server\Docker\Controllers\DockerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/docker')->name('server.docker.')->group(function (): void {
    Route::get('/', [DockerController::class, 'index'])->name('index');
    Route::get('status', [DockerController::class, 'status'])->name('status');
    Route::get('info', [DockerController::class, 'info'])->name('info');
    Route::post('install', [DockerController::class, 'install'])->name('install');
    Route::post('deinstall', [DockerController::class, 'deinstall'])->name('deinstall');
    Route::post('{action}', [DockerController::class, 'service'])->whereIn('action', ['start', 'stop', 'restart'])->name('service');
    Route::get('ps', [DockerController::class, 'ps'])->name('ps');
    Route::get('stats', [DockerController::class, 'stats'])->name('stats');
    Route::post('system-prune', [DockerController::class, 'systemPrune'])->name('system-prune');
    Route::post('containers/create', [DockerController::class, 'containerCreate'])->name('containers.create');
    Route::post('containers/{container}/start', [DockerController::class, 'containerStart'])->name('containers.start');
    Route::post('containers/{container}/stop', [DockerController::class, 'containerStop'])->name('containers.stop');
    Route::post('containers/{container}/restart', [DockerController::class, 'containerRestart'])->name('containers.restart');
    Route::delete('containers/{container}', [DockerController::class, 'containerRemove'])->name('containers.remove');
    Route::get('containers/{container}/logs', [DockerController::class, 'containerLogs'])->name('containers.logs');
    Route::post('containers/{container}/exec', [DockerController::class, 'containerExec'])->name('containers.exec');
    Route::get('images', [DockerController::class, 'images'])->name('images');
    Route::post('images/pull', [DockerController::class, 'imagePull'])->name('images.pull');
    Route::delete('images/{image}', [DockerController::class, 'imageRemove'])->name('images.remove');
    Route::get('networks', [DockerController::class, 'networks'])->name('networks');
    Route::get('compose', [DockerController::class, 'composePs'])->name('compose.ps');
    Route::post('compose/up', [DockerController::class, 'composeUp'])->name('compose.up');
    Route::post('compose/down', [DockerController::class, 'composeDown'])->name('compose.down');
});
