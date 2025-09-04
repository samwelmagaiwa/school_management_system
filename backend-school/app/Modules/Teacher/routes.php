<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Teacher\Controllers\TeacherController;

/*
|--------------------------------------------------------------------------
| Teacher Module API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Teacher module. All routes are protected
| by auth:sanctum middleware and use role-based authorization.
|
*/

Route::middleware(['auth:sanctum'])->prefix('teachers')->group(function () {
    // Basic CRUD operations
    Route::get('/', [TeacherController::class, 'index'])->name('teachers.index');
    Route::post('/', [TeacherController::class, 'store'])->name('teachers.store');
    Route::get('/statistics', [TeacherController::class, 'getStatistics'])->name('teachers.statistics');
    Route::get('/top-performers', [TeacherController::class, 'getTopPerformers'])->name('teachers.top-performers');
    Route::get('/low-performers', [TeacherController::class, 'getLowPerformers'])->name('teachers.low-performers');
    Route::get('/by-specialization', [TeacherController::class, 'getBySpecialization'])->name('teachers.by-specialization');
    Route::post('/bulk-update-status', [TeacherController::class, 'bulkUpdateStatus'])->name('teachers.bulk-update-status');
    Route::post('/bulk-import', [TeacherController::class, 'bulkImport'])->name('teachers.bulk-import');
    Route::get('/export', [TeacherController::class, 'export'])->name('teachers.export');
    
    // Individual teacher operations
    Route::get('/{teacher}', [TeacherController::class, 'show'])->name('teachers.show');
    Route::put('/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
    Route::delete('/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
    Route::get('/{teacher}/schedule', [TeacherController::class, 'schedule'])->name('teachers.schedule');
    Route::post('/{teacher}/assign-subjects', [TeacherController::class, 'assignSubjects'])->name('teachers.assign-subjects');
    Route::get('/{teacher}/performance', [TeacherController::class, 'performance'])->name('teachers.performance');
});