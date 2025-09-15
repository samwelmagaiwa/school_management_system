<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\SchoolController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\ClassController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\FeeController;
use App\Http\Controllers\Api\V1\LibraryController;
use App\Http\Controllers\Api\V1\TransportController;
use App\Http\Controllers\Api\V1\IdCardController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\SuperAdminController;

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

// Health check routes (no authentication required)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'status' => 'healthy',
        'database' => 'connected',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});

// Named login route (required by Laravel Auth middleware)
Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Unauthenticated. Please login.',
        'login_url' => '/api/auth/login',
        'login_v1_url' => '/api/v1/auth/login'
    ], 401);
})->name('login');

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working correctly',
        'timestamp' => now(),
        'routes' => [
            'auth' => [
                'login' => '/api/auth/login',
                'register' => '/api/auth/register',
                'logout' => '/api/auth/logout',
                'user' => '/api/auth/user'
            ],
            'v1' => [
                'auth' => '/api/v1/auth/*',
                'students' => '/api/v1/students',
                'teachers' => '/api/v1/teachers',
                'schools' => '/api/v1/schools',
                'superadmin' => '/api/v1/superadmin/*'
            ],
            'note' => 'Legacy routes have been moved to V1. Use /api/v1/ endpoints.'
        ]
    ]);
});

// Legacy Authentication routes (for backward compatibility)
Route::prefix('auth')->name('auth.')->group(function () {
    // Public authentication routes
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');
    
    // Protected authentication routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('user', [AuthController::class, 'user'])->name('user');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
    });
});

// Legacy routes redirect to V1 (for backward compatibility)
Route::prefix('superadmin')->middleware(['auth:sanctum'])->group(function () {
    Route::get('{any}', function ($path) {
        return response()->json([
            'message' => 'This endpoint has been moved. Please use the V1 API.',
            'redirect_to' => "/api/v1/superadmin/{$path}",
            'deprecated' => true
        ], 301);
    })->where('any', '.*');
});

// API Version 1 Routes
Route::prefix('v1')->name('api.v1.')->group(function () {
    
    // Authentication routes (using Laravel Sanctum)
    Route::prefix('auth')->name('auth.')->group(function () {
        // Public authentication routes
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('register', [AuthController::class, 'register'])->name('register');
        
        // Protected authentication routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('user', [AuthController::class, 'user'])->name('user');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        });
    });

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Dashboard
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('index');
            Route::get('stats', [DashboardController::class, 'stats'])->name('stats');
        });
        
        // Schools - Specific routes first (before apiResource)
        Route::prefix('schools')->name('schools.')->group(function () {
            Route::get('statistics', [SchoolController::class, 'statistics'])->name('statistics');
            Route::post('bulk-status', [SchoolController::class, 'bulkUpdateStatus'])->name('bulk-status');
        });
        
        // Schools - Full CRUD with apiResource
        Route::apiResource('schools', SchoolController::class)->names([
            'index' => 'schools.index',
            'store' => 'schools.store',
            'show' => 'schools.show',
            'update' => 'schools.update',
            'destroy' => 'schools.destroy',
        ]);
        
        // Students - Full CRUD with apiResource
        Route::apiResource('students', StudentController::class)->names([
            'index' => 'students.index',
            'store' => 'students.store',
            'show' => 'students.show',
            'update' => 'students.update',
            'destroy' => 'students.destroy',
        ]);
        Route::prefix('students')->name('students.')->group(function () {
            Route::get('statistics', [StudentController::class, 'statistics'])->name('statistics');
            Route::post('{student}/promote', [StudentController::class, 'promote'])->name('promote');
            Route::post('{student}/transfer', [StudentController::class, 'transfer'])->name('transfer');
            Route::post('bulk-status', [StudentController::class, 'bulkUpdateStatus'])->name('bulk-status');
            Route::get('export', [StudentController::class, 'export'])->name('export');
        });
        
        // Teachers - Full CRUD with apiResource
        Route::apiResource('teachers', TeacherController::class)->names([
            'index' => 'teachers.index',
            'store' => 'teachers.store',
            'show' => 'teachers.show',
            'update' => 'teachers.update',
            'destroy' => 'teachers.destroy',
        ]);
        Route::prefix('teachers')->name('teachers.')->group(function () {
            Route::get('statistics', [TeacherController::class, 'statistics'])->name('statistics');
            Route::post('bulk-status', [TeacherController::class, 'bulkUpdateStatus'])->name('bulk-status');
            Route::get('export', [TeacherController::class, 'export'])->name('export');
        });
        
        // Subjects - Full CRUD with apiResource
        Route::apiResource('subjects', SubjectController::class)->names([
            'index' => 'subjects.index',
            'store' => 'subjects.store',
            'show' => 'subjects.show',
            'update' => 'subjects.update',
            'destroy' => 'subjects.destroy',
        ]);
        Route::prefix('subjects')->name('subjects.')->group(function () {
            Route::get('statistics', [SubjectController::class, 'statistics'])->name('statistics');
            Route::post('bulk-status', [SubjectController::class, 'bulkUpdateStatus'])->name('bulk-status');
        });
        
        // Classes - Full CRUD with apiResource
        Route::apiResource('classes', ClassController::class)->names([
            'index' => 'classes.index',
            'store' => 'classes.store',
            'show' => 'classes.show',
            'update' => 'classes.update',
            'destroy' => 'classes.destroy',
        ]);
        Route::prefix('classes')->name('classes.')->group(function () {
            Route::get('statistics', [ClassController::class, 'statistics'])->name('statistics');
            Route::post('{class}/assign-teacher', [ClassController::class, 'assignTeacher'])->name('assign-teacher');
            Route::post('{class}/assign-subjects', [ClassController::class, 'assignSubjects'])->name('assign-subjects');
            Route::get('{class}/students', [ClassController::class, 'students'])->name('students');
        });
        
        // Attendance - Full CRUD with apiResource
        Route::apiResource('attendance', AttendanceController::class)->names([
            'index' => 'attendance.index',
            'store' => 'attendance.store',
            'show' => 'attendance.show',
            'update' => 'attendance.update',
            'destroy' => 'attendance.destroy',
        ]);
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('statistics', [AttendanceController::class, 'statistics'])->name('statistics');
            Route::post('bulk-mark', [AttendanceController::class, 'bulkMark'])->name('bulk-mark');
            Route::get('report', [AttendanceController::class, 'report'])->name('report');
            Route::get('export', [AttendanceController::class, 'export'])->name('export');
        });
        
        // Exams - Full CRUD with apiResource
        Route::apiResource('exams', ExamController::class)->names([
            'index' => 'exams.index',
            'store' => 'exams.store',
            'show' => 'exams.show',
            'update' => 'exams.update',
            'destroy' => 'exams.destroy',
        ]);
        Route::prefix('exams')->name('exams.')->group(function () {
            Route::get('statistics', [ExamController::class, 'statistics'])->name('statistics');
            Route::post('{exam}/results', [ExamController::class, 'storeResults'])->name('store-results');
            Route::get('{exam}/results', [ExamController::class, 'getResults'])->name('results');
            Route::get('report', [ExamController::class, 'report'])->name('report');
        });
        
        // Fees - Full CRUD with apiResource
        Route::apiResource('fees', FeeController::class)->names([
            'index' => 'fees.index',
            'store' => 'fees.store',
            'show' => 'fees.show',
            'update' => 'fees.update',
            'destroy' => 'fees.destroy',
        ]);
        Route::prefix('fees')->name('fees.')->group(function () {
            Route::get('statistics', [FeeController::class, 'statistics'])->name('statistics');
            Route::post('payments', [FeeController::class, 'recordPayment'])->name('record-payment');
            Route::get('payments', [FeeController::class, 'getPayments'])->name('payments');
            Route::get('report', [FeeController::class, 'report'])->name('report');
        });
        
        // Library - Books management with apiResource
        Route::prefix('library')->name('library.')->group(function () {
            Route::apiResource('books', LibraryController::class)->names([
                'index' => 'books.index',
                'store' => 'books.store',
                'show' => 'books.show',
                'update' => 'books.update',
                'destroy' => 'books.destroy',
            ]);
            Route::get('statistics', [LibraryController::class, 'statistics'])->name('statistics');
            Route::post('issue', [LibraryController::class, 'issueBook'])->name('issue');
            Route::post('return', [LibraryController::class, 'returnBook'])->name('return');
            Route::get('issued-books', [LibraryController::class, 'issuedBooks'])->name('issued-books');
        });
        
        // Transport - Vehicles management with apiResource
        Route::prefix('transport')->name('transport.')->group(function () {
            Route::apiResource('vehicles', TransportController::class)->names([
                'index' => 'vehicles.index',
                'store' => 'vehicles.store',
                'show' => 'vehicles.show',
                'update' => 'vehicles.update',
                'destroy' => 'vehicles.destroy',
            ]);
            Route::get('statistics', [TransportController::class, 'statistics'])->name('statistics');
            Route::get('routes', [TransportController::class, 'routes'])->name('routes.index');
            Route::post('routes', [TransportController::class, 'createRoute'])->name('routes.store');
            Route::get('students', [TransportController::class, 'transportStudents'])->name('students');
        });
        
        // ID Cards - Generation and management
        Route::prefix('id-cards')->name('id-cards.')->group(function () {
            Route::get('/', [IdCardController::class, 'index'])->name('index');
            Route::post('generate', [IdCardController::class, 'generate'])->name('generate');
            Route::get('templates', [IdCardController::class, 'templates'])->name('templates');
            Route::post('bulk-generate', [IdCardController::class, 'bulkGenerate'])->name('bulk-generate');
            Route::get('{idCard}', [IdCardController::class, 'show'])->name('show');
            Route::get('{idCard}/download', [IdCardController::class, 'download'])->name('download');
            Route::delete('{idCard}', [IdCardController::class, 'destroy'])->name('destroy');
        });
        
        // Permissions & Roles Management
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('user', [\App\Http\Controllers\Api\V1\PermissionController::class, 'userPermissions'])->name('user');
            Route::post('check', [\App\Http\Controllers\Api\V1\PermissionController::class, 'checkPermission'])->name('check');
            Route::post('bulk-check', [\App\Http\Controllers\Api\V1\PermissionController::class, 'bulkCheckPermissions'])->name('bulk-check');
            Route::get('capabilities', [\App\Http\Controllers\Api\V1\PermissionController::class, 'userCapabilities'])->name('capabilities');
            Route::get('all', [\App\Http\Controllers\Api\V1\PermissionController::class, 'allPermissions'])->name('all');
            Route::get('module/{module}', [\App\Http\Controllers\Api\V1\PermissionController::class, 'modulePermissions'])->name('module');
        });
        
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\PermissionController::class, 'allRoles'])->name('index');
            Route::get('{role}/permissions', [\App\Http\Controllers\Api\V1\PermissionController::class, 'rolePermissions'])->name('permissions');
        });
        
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('stats', [\App\Http\Controllers\Api\V1\PermissionController::class, 'systemStats'])->name('stats');
        });
        
        // SuperAdmin - System-wide management (SuperAdmin only)
        Route::prefix('superadmin')->name('superadmin.')
            ->middleware(['superadmin', 'advanced.rate.limit:30,1'])
            ->group(function () {
            // Dashboard and Statistics
            Route::get('dashboard', [SuperAdminController::class, 'getDashboardStats'])->name('dashboard');
            Route::get('reports', [SuperAdminController::class, 'getSystemReports'])->name('reports');
            
            // Schools Management
            Route::get('schools', [SuperAdminController::class, 'getSchools'])->name('schools.index');
            Route::get('schools/statistics', [SuperAdminController::class, 'getSchoolStatistics'])->name('schools.statistics');
            Route::get('schools/export', [SuperAdminController::class, 'exportSchools'])->name('schools.export');
            Route::post('schools', [SuperAdminController::class, 'createSchool'])->name('schools.store');
            Route::put('schools/{id}', [SuperAdminController::class, 'updateSchool'])->name('schools.update');
            Route::delete('schools/{id}', [SuperAdminController::class, 'deleteSchool'])->name('schools.destroy');
            
            // Users Management
            Route::get('users', [SuperAdminController::class, 'getAllUsers'])->name('users.index');
            Route::post('users', [SuperAdminController::class, 'createUser'])->name('users.store');
            Route::put('users/{id}/status', [SuperAdminController::class, 'updateUserStatus'])->name('users.status');
            Route::get('users/statistics', [SuperAdminController::class, 'getUserStatistics'])->name('users.statistics');
            Route::get('users/schools', [SuperAdminController::class, 'getUserSchools'])->name('users.schools');
            Route::get('users/roles', [SuperAdminController::class, 'getUserRoles'])->name('users.roles');
        });
    });
});

// Legacy support - redirect old routes to new versioned routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Redirect old routes to v1
    $legacyRoutes = ['students', 'teachers', 'schools', 'subjects', 'classes', 'attendance', 'exams', 'fees'];
    
    foreach ($legacyRoutes as $route) {
        Route::any($route . '/{any?}', function ($route) {
            return response()->json([
                'message' => "Please use /api/v1/{$route} instead",
                'redirect' => "/api/v1/{$route}"
            ], 301);
        })->where('any', '.*');
    }
});

// Debug routes (only in development)
if (config('app.debug')) {
    require __DIR__ . '/debug.php';
}

// Test routes for schools data
require __DIR__ . '/test-schools.php';

// Rate limiting for API routes
Route::middleware('throttle:60,1')->group(function () {
    // Apply rate limiting to sensitive operations
    Route::post('v1/auth/login', [AuthController::class, 'login']);
    Route::post('v1/auth/register', [AuthController::class, 'register']);
});
