<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Transport\Controllers\TransportController;

/*
|--------------------------------------------------------------------------
| Transport Module API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Transport module. All routes are protected
| by auth:sanctum middleware and use role-based authorization.
|
*/

Route::middleware(['auth:sanctum'])->prefix('transport')->group(function () {
    
    // Statistics and Reports
    Route::get('/statistics', [TransportController::class, 'getStatistics'])->name('transport.statistics');
    Route::get('/maintenance-alerts', [TransportController::class, 'getMaintenanceAlerts'])->name('transport.maintenance-alerts');
    Route::get('/route-efficiency', [TransportController::class, 'getRouteEfficiency'])->name('transport.route-efficiency');
    
    // Reference Data
    Route::get('/vehicle-types', [TransportController::class, 'getVehicleTypes'])->name('transport.vehicle-types');
    Route::get('/vehicle-statuses', [TransportController::class, 'getVehicleStatuses'])->name('transport.vehicle-statuses');
    Route::get('/fuel-types', [TransportController::class, 'getFuelTypes'])->name('transport.fuel-types');
    
    // Vehicle Management
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [TransportController::class, 'indexVehicles'])->name('transport.vehicles.index');
        Route::post('/', [TransportController::class, 'storeVehicle'])->name('transport.vehicles.store');
        Route::get('/{vehicle}', [TransportController::class, 'showVehicle'])->name('transport.vehicles.show');
        Route::put('/{vehicle}', [TransportController::class, 'updateVehicle'])->name('transport.vehicles.update');
        Route::delete('/{vehicle}', [TransportController::class, 'destroyVehicle'])->name('transport.vehicles.destroy');
        
        // Vehicle Operations
        Route::post('/{vehicle}/assign-driver', [TransportController::class, 'assignDriver'])->name('transport.vehicles.assign-driver');
        Route::post('/{vehicle}/assign-students', [TransportController::class, 'assignStudents'])->name('transport.vehicles.assign-students');
        Route::post('/{vehicle}/maintenance', [TransportController::class, 'recordMaintenance'])->name('transport.vehicles.maintenance');
        Route::post('/{vehicle}/fuel', [TransportController::class, 'recordFuel'])->name('transport.vehicles.fuel');
    });
    
    // Route Management
    Route::prefix('routes')->group(function () {
        Route::get('/', [TransportController::class, 'indexRoutes'])->name('transport.routes.index');
        Route::post('/', [TransportController::class, 'storeRoute'])->name('transport.routes.store');
        Route::get('/{route}', [TransportController::class, 'showRoute'])->name('transport.routes.show');
        Route::put('/{route}', [TransportController::class, 'updateRoute'])->name('transport.routes.update');
        Route::delete('/{route}', [TransportController::class, 'destroyRoute'])->name('transport.routes.destroy');
    });
    
    // Driver Management
    Route::prefix('drivers')->group(function () {
        Route::get('/', [TransportController::class, 'indexDrivers'])->name('transport.drivers.index');
        Route::post('/', [TransportController::class, 'storeDriver'])->name('transport.drivers.store');
    });
});