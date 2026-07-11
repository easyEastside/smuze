<?php

use App\Modules\Inventory\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/gift', [InventoryController::class, 'gift'])->name('inventory.gift');
    Route::post('inventory/use', [InventoryController::class, 'use'])->name('inventory.use');
});
