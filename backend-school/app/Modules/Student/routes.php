<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Student\Controllers\StudentController;

/*
|--------------------------------------------------------------------------
| Student Module API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Student module. All routes are protected
| by auth:sanctum middleware and use the StudentPolicy for authorization.
|
*/

Route::middleware(['auth:sanctum'])->prefix('students')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('students.index');
    Route::post('/', [StudentController::class, 'store'])->name('students.store');
    Route::get('/statistics/overview', [StudentController::class, 'getStatistics'])->name('students.statistics');
    Route::get('/low-attendance', [StudentController::class, 'getLowAttendanceStudents'])->name('students.low-attendance');
    Route::get('/top-performers', [StudentController::class, 'getTopPerformers'])->name('students.top-performers');
    Route::post('/bulk-import', [StudentController::class, 'bulkImport'])->name('students.bulk-import');
    Route::get('/export', [StudentController::class, 'export'])->name('students.export');
    Route::patch('/bulk-status', [StudentController::class, 'bulkUpdateStatus'])->name('students.bulk-status');
    Route::get('/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::put('/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::get('/{student}/profile', [StudentController::class, 'getProfile'])->name('students.profile');
    Route::get('/{student}/performance', [StudentController::class, 'performance'])->name('students.performance');
    Route::post('/{student}/promote', [StudentController::class, 'promote'])->name('students.promote');
    Route::post('/{student}/transfer', [StudentController::class, 'transfer'])->name('students.transfer');
});
