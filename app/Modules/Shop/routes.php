<?php

use App\Modules\Shop\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('shop', [ShopController::class, 'index'])->name('shop.index');
    Route::get('shop/{shopItem}', [ShopController::class, 'show'])->name('shop.show');
    Route::post('shop/{shopItem}/buy', [ShopController::class, 'buy'])->name('shop.buy');
});
