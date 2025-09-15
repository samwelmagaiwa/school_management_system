<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Http\Request;

// Debug route to analyze user data structure
Route::get('/debug/users', function (Request $request) {
    $users = User::with(['school:id,name,code'])->limit(5)->get();
    
    $debugData = [];
    
    foreach ($users as $index => $user) {
        $debugData["user_$index"] = [
            'is_null' => $user === null,
            'is_object' => is_object($user),
            'class' => $user ? get_class($user) : 'null',
            'id' => $user->id ?? 'missing',
            'first_name' => $user->first_name ?? 'missing',
            'last_name' => $user->last_name ?? 'missing',
            'role' => $user->role ?? 'missing',
            'status' => $user->status ?? 'missing',
            'school' => $user->school ? [
                'id' => $user->school->id,
                'name' => $user->school->name,
                'code' => $user->school->code
            ] : 'missing',
            'all_attributes' => $user ? array_keys($user->getAttributes()) : [],
            'relations_loaded' => $user ? array_keys($user->getRelations()) : []
        ];
    }
    
    return response()->json([
        'total_users' => $users->count(),
        'users_data' => $debugData,
        'collection_class' => get_class($users),
        'first_user_type' => $users->first() ? get_class($users->first()) : 'null'
    ]);
})->middleware('auth:sanctum');

// Debug endpoint to check schools API requests
Route::get('/debug/schools-requests', function (Request $request) {
    // Get recent log entries
    $logFile = storage_path('logs/laravel.log');
    $logs = [];
    
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        // Get last 20 lines
        $lines = array_slice(explode("\n", $content), -20);
        
        foreach ($lines as $line) {
            if (stripos($line, 'schools') !== false || stripos($line, 'GET') !== false) {
                $logs[] = $line;
            }
        }
    }
    
    return response()->json([
        'recent_school_logs' => $logs,
        'available_endpoints' => [
            '/api/v1/superadmin/schools' => 'SuperAdmin schools management (recommended)',
            '/api/v1/schools' => 'General schools API (role-based access)',
            '/api/superadmin/*' => 'Legacy routes (deprecated, redirects to V1)'
        ],
        'request_headers' => $request->headers->all(),
        'current_time' => now(),
    ]);
});

// Test endpoint to simulate frontend schools request
Route::get('/debug/test-schools-frontend', function (Request $request) {
    // Simulate what the frontend might be doing
    $user = $request->user();
    
    if (!$user) {
        return response()->json([
            'error' => 'No authenticated user',
            'suggestion' => 'Frontend might not be sending auth token'
        ], 401);
    }
    
    if (!$user->isSuperAdmin()) {
        return response()->json([
            'error' => 'User is not SuperAdmin',
            'user_role' => $user->role,
            'suggestion' => 'Frontend might be calling wrong endpoint for this role'
        ], 403);
    }
    
    // Try to get schools like the API does
    try {
        $schools = \App\Models\School::with(['users' => function($q) {
            $q->select('id', 'school_id', 'first_name', 'last_name', 'email', 'role', 'status');
        }])->paginate(15);
        
        return response()->json([
            'success' => true,
            'debug' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_superadmin' => $user->isSuperAdmin()
                ],
                'schools_count' => $schools->total(),
                'first_school' => $schools->first() ? [
                    'id' => $schools->first()->id,
                    'name' => $schools->first()->name,
                    'code' => $schools->first()->code,
                    'is_active' => $schools->first()->is_active
                ] : null
            ],
            'message' => 'Schools data retrieved successfully'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Database error',
            'message' => $e->getMessage(),
            'suggestion' => 'Check database connection or schema'
        ], 500);
    }
})->middleware('auth:sanctum');
