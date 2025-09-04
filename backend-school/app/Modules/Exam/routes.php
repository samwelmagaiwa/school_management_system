<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Exam\Controllers\ExamController;

/*
|--------------------------------------------------------------------------
| Exam Module Routes
|--------------------------------------------------------------------------
|
| Here are the routes for exam management functionality including
| exam creation, result management, and reporting.
|
*/

Route::middleware('auth:sanctum')->prefix('exams')->group(function () {
    // Basic CRUD operations
    Route::get('/', [ExamController::class, 'index'])->name('exams.index');
    Route::post('/', [ExamController::class, 'store'])->name('exams.store');
    Route::get('/{exam}', [ExamController::class, 'show'])->name('exams.show');
    Route::put('/{exam}', [ExamController::class, 'update'])->name('exams.update');
    Route::delete('/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy');
    
    // Statistics and reports
    Route::get('/statistics/overview', [ExamController::class, 'getStatistics'])->name('exams.statistics');
    
    // Exam results management
    Route::post('/{exam}/results', [ExamController::class, 'storeResults'])->name('exams.store-results');
    Route::get('/{exam}/results', [ExamController::class, 'getResults'])->name('exams.get-results');
    Route::get('/{exam}/report', [ExamController::class, 'generateReport'])->name('exams.generate-report');
    
    // Student-specific exam results
    Route::get('/student/{studentId}/results', [ExamController::class, 'getStudentResults'])->name('exams.student-results');
});