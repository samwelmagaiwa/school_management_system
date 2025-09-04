<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Auth Module Routes
|--------------------------------------------------------------------------
|
| Here are the routes for authentication functionality including login,
| logout, registration, and user profile management.
|
*/

Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});