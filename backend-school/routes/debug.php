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
