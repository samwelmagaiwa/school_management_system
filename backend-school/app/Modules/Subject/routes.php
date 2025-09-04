<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Subject\Controllers\SubjectController;

/*
|--------------------------------------------------------------------------
| Subject Module API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Subject module. All routes are protected
| by auth:sanctum middleware and use role-based authorization.
|
*/

Route::middleware(['auth:sanctum'])->prefix('subjects')->group(function () {
    Route::get('/', [SubjectController::class, 'index'])->name('subjects.index');
    Route::post('/', [SubjectController::class, 'store'])->name('subjects.store');
    Route::get('/types', [SubjectController::class, 'getTypes'])->name('subjects.types');
    Route::get('/statistics', [SubjectController::class, 'getStatistics'])->name('subjects.statistics');
    Route::get('/class/{classId}', [SubjectController::class, 'getByClass'])->name('subjects.by-class');
    Route::get('/export', [SubjectController::class, 'export'])->name('subjects.export');
    Route::post('/validate-code', [SubjectController::class, 'validateCode'])->name('subjects.validate-code');
    Route::get('/{subject}', [SubjectController::class, 'show'])->name('subjects.show');
    Route::put('/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
    Route::delete('/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
    Route::get('/{subject}/statistics', [SubjectController::class, 'getSubjectStatistics'])->name('subjects.subject-statistics');
    Route::post('/{subject}/assign-teachers', [SubjectController::class, 'assignTeachers'])->name('subjects.assign-teachers');
    Route::get('/{subject}/prerequisites', [SubjectController::class, 'getPrerequisites'])->name('subjects.prerequisites');
    Route::post('/{subject}/prerequisites', [SubjectController::class, 'setPrerequisites'])->name('subjects.set-prerequisites');
});
