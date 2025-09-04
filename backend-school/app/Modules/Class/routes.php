<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Class\Controllers\ClassController;

/*
|--------------------------------------------------------------------------
| Class Module Routes
|--------------------------------------------------------------------------
|
| Here are the routes for class management functionality including
| creating, updating, viewing, and managing classes and sections.
|
*/

Route::middleware('auth:sanctum')->prefix('classes')->group(function () {
    // Class CRUD operations
    Route::get('/', [ClassController::class, 'index']);
    Route::post('/', [ClassController::class, 'store']);
    Route::get('/{class}', [ClassController::class, 'show']);
    Route::put('/{class}', [ClassController::class, 'update']);
    Route::delete('/{class}', [ClassController::class, 'destroy']);
    
    // Class statistics and data
    Route::get('/statistics/overview', [ClassController::class, 'statistics']); // Overall statistics
    Route::get('/{class}/statistics', [ClassController::class, 'statistics']); // Individual class statistics
    Route::get('/{class}/timetable', [ClassController::class, 'timetable']);
    
    // Class assignments
    Route::post('/{class}/assign-students', [ClassController::class, 'assignStudents']);
    Route::post('/{class}/assign-subjects', [ClassController::class, 'assignSubjects']);
    
    // Helper endpoints
    Route::get('/data/grades', [ClassController::class, 'getGrades']);
    Route::get('/data/academic-years', [ClassController::class, 'getAcademicYears']);
});