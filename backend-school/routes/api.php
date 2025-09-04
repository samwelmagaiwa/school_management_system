<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\DebugController;

use App\Modules\Dashboard\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// CSRF cookie route for SPA
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
    
    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
        Route::get('/menu', [DashboardController::class, 'getMenuItems'])->name('dashboard.menu');
        Route::get('/permissions', [DashboardController::class, 'getPermissions'])->name('dashboard.permissions');
        Route::get('/quick-stats', [DashboardController::class, 'getQuickStats'])->name('dashboard.quick-stats');
    });

    // Log management routes (Admin/SuperAdmin only)
    Route::prefix('logs')->group(function () {
        Route::get('/stats', [LogController::class, 'getStats'])->name('logs.stats');
        Route::get('/recent', [LogController::class, 'getRecentLogs'])->name('logs.recent');
        Route::get('/channels', [LogController::class, 'getChannels'])->name('logs.channels');
        Route::post('/search', [LogController::class, 'searchLogs'])->name('logs.search');
    });
    
    // Load module routes (Laravel automatically adds /api prefix)
    require base_path('app/Modules/Dashboard/routes.php');
    require base_path('app/Modules/User/routes.php');
    require base_path('app/Modules/Student/routes.php');
    require base_path('app/Modules/Subject/routes.php');
    require base_path('app/Modules/Fee/routes.php');
    require base_path('app/Modules/Exam/routes.php');
    require base_path('app/Modules/Class/routes.php');
    require base_path('app/Modules/Teacher/routes.php');
    require base_path('app/Modules/Attendance/routes.php');
    require base_path('app/Modules/Transport/routes.php');
    require base_path('app/Modules/HR/routes.php');
    require base_path('app/Modules/IDCard/routes.php');
    
    // Load remaining module routes
    require base_path('app/Modules/School/routes.php');
    require base_path('app/Modules/Library/routes.php');
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now()->toISOString(),
    ]);
});

// Test endpoint for frontend connectivity
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'Backend is accessible',
        'cors_enabled' => true,
        'timestamp' => now()->toISOString(),
    ]);
});

// Test authenticated endpoint
Route::middleware('auth:sanctum')->get('/test-auth', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Authentication is working',
        'user' => $request->user()->only(['id', 'email', 'role']),
        'timestamp' => now()->toISOString(),
    ]);
});

// Debug endpoints (only in development)
if (config('app.debug')) {
    Route::get('/debug/auth', [DebugController::class, 'authStatus']);
    Route::get('/debug/cors', [DebugController::class, 'corsInfo']);
    Route::get('/debug/database', [DebugController::class, 'databaseStatus']);
    Route::post('/debug/create-test-user', [DebugController::class, 'createTestUser']);
    Route::middleware('auth:sanctum')->get('/debug/auth-protected', [DebugController::class, 'authStatus']);
}

// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found'
    ], 404);
});