<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\SchoolController;

/*
|--------------------------------------------------------------------------
| School Module API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the School module. All routes are protected
| by auth:sanctum middleware and use the SchoolPolicy for authorization.
|
*/

Route::middleware(['auth:sanctum'])->prefix('schools')->group(function () {
    // Basic CRUD operations
    Route::get('/', [SchoolController::class, 'index'])->name('schools.index');
    Route::post('/', [SchoolController::class, 'store'])->name('schools.store');
    Route::get('/{school}', [SchoolController::class, 'show'])->name('schools.show');
    Route::put('/{school}', [SchoolController::class, 'update'])->name('schools.update');
    Route::delete('/{school}', [SchoolController::class, 'destroy'])->name('schools.destroy');
    
    // Statistics and dashboard
    Route::get('/statistics/overview', [SchoolController::class, 'systemStatistics'])->name('schools.statistics.overview');
    Route::get('/{school}/statistics', [SchoolController::class, 'schoolStatistics'])->name('schools.statistics.individual');
    Route::get('/{school}/dashboard', [SchoolController::class, 'dashboard'])->name('schools.dashboard');
    
    // Settings management
    Route::get('/{school}/settings', [SchoolController::class, 'getSettings'])->name('schools.settings.show');
    Route::put('/{school}/settings', [SchoolController::class, 'updateSettings'])->name('schools.settings.update');
});