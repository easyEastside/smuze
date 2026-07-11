<?php

use App\Modules\Messages\Controllers\MessagesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('messages', [MessagesController::class, 'index'])->name('messages.index');
    Route::post('messages/threads', [MessagesController::class, 'store'])->name('messages.store');
    Route::get('messages/threads/{messageThread}', [MessagesController::class, 'show'])->name('messages.show');
    Route::post('messages/threads/{messageThread}', [MessagesController::class, 'reply'])->name('messages.reply');
});
