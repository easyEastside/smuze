<?php

use App\Modules\Server\Mysql\Controllers\MysqlController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/mysql')->name('server.mysql.')->group(function (): void {
    Route::get('/', [MysqlController::class, 'index'])->name('index');
    Route::get('status', [MysqlController::class, 'status'])->name('status');
    Route::post('install', [MysqlController::class, 'install'])->name('install');
    Route::post('deinstall', [MysqlController::class, 'deinstall'])->name('deinstall');
    Route::post('{action}', [MysqlController::class, 'service'])->whereIn('action', ['start', 'stop', 'restart'])->name('service');
    Route::get('databases', [MysqlController::class, 'databases'])->name('databases');
    Route::post('databases/create', [MysqlController::class, 'createDatabase'])->name('databases.create');
    Route::delete('databases/{database}', [MysqlController::class, 'dropDatabase'])->name('databases.destroy');
    Route::get('databases/{database}/tables', [MysqlController::class, 'tables'])->name('databases.tables');
    Route::post('databases/{database}/tables/create', [MysqlController::class, 'createTable'])->name('databases.tables.create');
    Route::delete('databases/{database}/tables/{table}', [MysqlController::class, 'dropTable'])->name('databases.tables.destroy');
    Route::get('users', [MysqlController::class, 'users'])->name('users');
    Route::post('users/create', [MysqlController::class, 'createUser'])->name('users.create');
    Route::delete('users/{username}/{host}', [MysqlController::class, 'dropUser'])->name('users.destroy');
    Route::post('users/{username}/{host}/password', [MysqlController::class, 'setPassword'])->name('users.password');
    Route::post('users/{username}/{host}/grant', [MysqlController::class, 'grantAll'])->name('users.grant');
});
