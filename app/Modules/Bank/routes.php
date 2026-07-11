<?php

use App\Modules\Bank\Controllers\BankController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('bank', [BankController::class, 'index'])->name('bank.index');
    Route::post('bank/invest', [BankController::class, 'store'])->name('bank.store');
    Route::post('bank/investments/{bankInvestment}/claim', [BankController::class, 'claim'])->name('bank.claim');
});
