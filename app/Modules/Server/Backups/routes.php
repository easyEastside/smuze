<?php

use App\Modules\Server\Backups\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('servers/{server}/backups')->name('server.backups.')->group(function (): void {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::post('/', [BackupController::class, 'store'])->name('store');
    Route::patch('{backup}', [BackupController::class, 'update'])->name('update');
    Route::delete('{backup}', [BackupController::class, 'destroy'])->name('destroy');
    Route::post('{backup}/run', [BackupController::class, 'run'])->name('run');
    Route::post('{backup}/toggle', [BackupController::class, 'toggle'])->name('toggle');
    Route::get('{backup}/archives', [BackupController::class, 'archives'])->name('archives');
    Route::post('archives/{archive}/restore', [BackupController::class, 'restore'])->name('archives.restore');
    Route::delete('archives/{archive}', [BackupController::class, 'destroyArchive'])->name('archives.destroy');
    Route::get('archives/{archive}/download', [BackupController::class, 'download'])->name('archives.download');
});
