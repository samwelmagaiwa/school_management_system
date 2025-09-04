<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Attendance\Controllers\AttendanceController;

/*
|--------------------------------------------------------------------------
| Attendance Module Routes
|--------------------------------------------------------------------------
|
| Here are the routes for attendance management functionality including
| recording, tracking, and reporting attendance for students and staff.
|
*/

Route::middleware('auth:sanctum')->prefix('attendance')->group(function () {
    // Basic CRUD operations
    Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
    
    // Bulk operations
    Route::post('/bulk', [AttendanceController::class, 'bulkStore'])->name('attendance.bulk-store');
    
    // Statistics and reports
    Route::get('/statistics/overview', [AttendanceController::class, 'getStatistics'])->name('attendance.statistics');
    Route::get('/reports/generate', [AttendanceController::class, 'getReport'])->name('attendance.report');
    Route::get('/students/low-attendance', [AttendanceController::class, 'getLowAttendanceStudents'])->name('attendance.low-attendance');
    
    // Class-specific attendance
    Route::get('/class/{classId}/date/{date}', [AttendanceController::class, 'getClassAttendance'])->name('attendance.class');
    
    // Student-specific attendance
    Route::get('/student/{studentId}', [AttendanceController::class, 'getStudentAttendance'])->name('attendance.student');
    Route::get('/student/{studentId}/summary', [AttendanceController::class, 'getStudentSummary'])->name('attendance.student-summary');
    
    // Verification and excuse operations
    Route::post('/{attendance}/verify', [AttendanceController::class, 'verify'])->name('attendance.verify');
    Route::post('/{attendance}/excuse', [AttendanceController::class, 'excuse'])->name('attendance.excuse');
});