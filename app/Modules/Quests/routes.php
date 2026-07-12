<?php

use App\Modules\Quests\Controllers\QuestsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('quests', [QuestsController::class, 'index'])->name('quests.index');
    Route::post('quests/{questKey}/claim', [QuestsController::class, 'claim'])->name('quests.claim');
});
