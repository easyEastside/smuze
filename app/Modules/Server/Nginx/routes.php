<?php

use App\Modules\Server\Nginx\Controllers\NginxController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/nginx')->name('server.nginx.')->group(function (): void {
    Route::get('/', [NginxController::class, 'index'])->name('index');
    Route::post('install', [NginxController::class, 'install'])->name('install');
    Route::post('deinstall', [NginxController::class, 'deinstall'])->name('deinstall');
    Route::get('status', [NginxController::class, 'status'])->name('status');
    Route::post('configtest', [NginxController::class, 'configtest'])->name('configtest');
    Route::post('{action}', [NginxController::class, 'service'])->whereIn('action', ['start', 'stop', 'restart', 'reload'])->name('service');
    Route::get('sites', [NginxController::class, 'sites'])->name('sites');
    Route::post('sites/{site}/enable', [NginxController::class, 'enableSite'])->name('sites.enable');
    Route::post('sites/{site}/disable', [NginxController::class, 'disableSite'])->name('sites.disable');
    Route::post('sites/{site}/delete', [NginxController::class, 'deleteSite'])->name('sites.delete');
    Route::post('vhost', [NginxController::class, 'createVhost'])->name('vhost');
    Route::post('ssl/install-certbot', [NginxController::class, 'installCertbot'])->name('ssl.install-certbot');
    Route::post('ssl/obtain', [NginxController::class, 'obtainSsl'])->name('ssl.obtain');
});
