<?php

use App\Modules\Server\Files\Controllers\FileManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/files')->name('server.files.')->group(function (): void {
    Route::get('/', [FileManagerController::class, 'index'])->name('index');
    Route::get('list', [FileManagerController::class, 'list'])->name('list');
    Route::get('read', [FileManagerController::class, 'read'])->name('read');
    Route::get('download', [FileManagerController::class, 'download'])->name('download');
    Route::post('write', [FileManagerController::class, 'write'])->name('write');
    Route::post('directories', [FileManagerController::class, 'createDirectory'])->name('directories.store');
    Route::post('files', [FileManagerController::class, 'createFile'])->name('files.store');
    Route::post('rename', [FileManagerController::class, 'rename'])->name('rename');
    Route::post('chmod', [FileManagerController::class, 'chmod'])->name('chmod');
    Route::post('upload', [FileManagerController::class, 'upload'])->name('upload');
    Route::delete('delete', [FileManagerController::class, 'delete'])->name('delete');
});
