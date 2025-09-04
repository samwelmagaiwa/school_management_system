<?php

use Illuminate\Support\Facades\Route;
use App\Modules\User\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| User Module API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the User module. All routes are protected
| by auth:sanctum middleware and use the UserPolicy for authorization.
|
*/

Route::middleware(['auth:sanctum'])->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('users.index');
    Route::post('/', [UserController::class, 'store'])->name('users.store');
    Route::get('/statistics/overview', [UserController::class, 'getStatistics'])->name('users.statistics');
    Route::get('/roles/available', [UserController::class, 'getAvailableRoles'])->name('users.roles');
    Route::get('/role/{role}', [UserController::class, 'getUsersByRole'])->name('users.by-role');
    Route::post('/bulk-import', [UserController::class, 'bulkImport'])->name('users.bulk-import');
    Route::get('/export', [UserController::class, 'export'])->name('users.export');
    Route::get('/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::put('/{user}/change-password', [UserController::class, 'changePassword'])->name('users.change-password');
    Route::post('/{user}/profile-picture', [UserController::class, 'uploadProfilePicture'])->name('users.profile-picture');
});
