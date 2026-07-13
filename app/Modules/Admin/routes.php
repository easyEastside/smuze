<?php

use App\Modules\Admin\Achievements\Controllers\AdminAchievementsController;
use App\Modules\Admin\Agent\Controllers\AdminAgentController;
use App\Modules\Admin\Dashboard\Controllers\AdminDashboardController;
use App\Modules\Admin\Inventory\Controllers\AdminInventoryController;
use App\Modules\Admin\Permissions\Controllers\AdminPermissionsController;
use App\Modules\Admin\Roles\Controllers\AdminRolesController;
use App\Modules\Admin\Server\Controllers\AdminServerController;
use App\Modules\Admin\Settings\Controllers\AdminSettingsController;
use App\Modules\Admin\ShopItems\Controllers\AdminShopItemsController;
use App\Modules\Admin\Surveys\Controllers\AdminSurveysController;
use App\Modules\Admin\Users\Controllers\AdminUserCreditsController;
use App\Modules\Admin\Users\Controllers\AdminUsersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:access-admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('dashboard', [AdminDashboardController::class, 'show'])->name('dashboard');

    Route::resource('users', AdminUsersController::class);
    Route::post('users/{user}/credits', [AdminUserCreditsController::class, 'adjust'])->name('users.credits.adjust');

    Route::resource('roles', AdminRolesController::class)->except('show');

    Route::resource('permissions', AdminPermissionsController::class)->except('show');

    Route::resource('achievements', AdminAchievementsController::class)->except('show');

    Route::resource('shop-items', AdminShopItemsController::class)->except('show');

    Route::resource('surveys', AdminSurveysController::class);

    Route::get('inventory/gift', [AdminInventoryController::class, 'create'])->name('inventory.create');
    Route::post('inventory/gift', [AdminInventoryController::class, 'store'])->name('inventory.store');

    Route::resource('servers', AdminServerController::class)->except('show');

    Route::get('settings', [AdminSettingsController::class, 'show'])->name('settings');
    Route::post('settings/bank', [AdminSettingsController::class, 'updateBank'])->name('settings.bank.update');

    Route::get('agent', [AdminAgentController::class, 'index'])->name('agent');
    Route::post('agent/build', [AdminAgentController::class, 'build'])->name('agent.build');
});
