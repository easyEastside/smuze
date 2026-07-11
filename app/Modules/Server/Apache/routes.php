<?php

use App\Modules\Server\Apache\Controllers\ApacheController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/apache')->name('server.apache.')->group(function (): void {
    Route::get('/', [ApacheController::class, 'index'])->name('index');
    Route::post('install', [ApacheController::class, 'install'])->name('install');
    Route::post('deinstall', [ApacheController::class, 'deinstall'])->name('deinstall');
    Route::get('status', [ApacheController::class, 'status'])->name('status');
    Route::post('configtest', [ApacheController::class, 'configtest'])->name('configtest');
    Route::post('{action}', [ApacheController::class, 'service'])->whereIn('action', ['start', 'stop', 'restart', 'reload'])->name('service');
    Route::get('sites', [ApacheController::class, 'sites'])->name('sites');
    Route::post('sites/{site}/enable', [ApacheController::class, 'enableSite'])->name('sites.enable');
    Route::post('sites/{site}/disable', [ApacheController::class, 'disableSite'])->name('sites.disable');
    Route::post('sites/{site}/delete', [ApacheController::class, 'deleteSite'])->name('sites.delete');
    Route::post('vhost', [ApacheController::class, 'createVhost'])->name('vhost');
    Route::get('modules', [ApacheController::class, 'modules'])->name('modules');
    Route::post('modules/{module}/enable', [ApacheController::class, 'enableModule'])->name('modules.enable');
    Route::post('modules/{module}/disable', [ApacheController::class, 'disableModule'])->name('modules.disable');
    Route::post('ssl/install-certbot', [ApacheController::class, 'installCertbot'])->name('ssl.install-certbot');
    Route::post('ssl/obtain', [ApacheController::class, 'obtainSsl'])->name('ssl.obtain');
});
