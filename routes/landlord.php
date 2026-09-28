<?php

use App\Http\Controllers\Landlord\AuthController;
use App\Http\Controllers\Landlord\DashboardController;
use App\Http\Controllers\Landlord\PlanController;
use App\Http\Controllers\Landlord\StatisticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landlord Routes (Super Admin Panel)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('landlord.')->group(function () {
    // Authentication
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Protected routes
    Route::middleware(['auth.super_admin'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Plans
        Route::resource('plans', PlanController::class)->except(['show']);

        // Statistics
        Route::get('statistics', [StatisticsController::class, 'index'])->name('statistics.index');
    });
});
