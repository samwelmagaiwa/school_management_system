<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Dashboard\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Dashboard Module API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Dashboard module. All routes are protected
| by auth:sanctum middleware and use the DashboardPolicy for authorization.
|
*/

Route::middleware(['auth:sanctum'])->prefix('dashboard')->group(function () {
    // Main dashboard data
    Route::get('/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
    
    // User-specific data
    Route::get('/menu', [DashboardController::class, 'getMenuItems'])->name('dashboard.menu');
    Route::get('/permissions', [DashboardController::class, 'getPermissions'])->name('dashboard.permissions');
    
    // Dashboard widgets
    Route::get('/quick-stats', [DashboardController::class, 'getQuickStats'])->name('dashboard.quick-stats');
    Route::get('/recent-activities', [DashboardController::class, 'getRecentActivities'])->name('dashboard.recent-activities');
    Route::get('/notifications', [DashboardController::class, 'getNotifications'])->name('dashboard.notifications');
    Route::get('/charts-data', [DashboardController::class, 'getChartsData'])->name('dashboard.charts-data');
});