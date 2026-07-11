<?php

use App\Modules\Surveys\Controllers\SurveysController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('surveys', [SurveysController::class, 'index'])->name('surveys.index');
    Route::get('surveys/{survey}', [SurveysController::class, 'show'])->name('surveys.show');
    Route::post('surveys/{survey}/responses', [SurveysController::class, 'store'])->name('surveys.responses.store');
});
