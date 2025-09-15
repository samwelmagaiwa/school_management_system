<?php

use App\Models\School;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get("/test-schools-api", function () {
    try {
        // Check which column exists for school status
        $statusColumn = Schema::hasColumn("schools", "is_active") ? "is_active" : "status";
        
        $query = School::with(["users" => function($q) {
            $q->select("id", "school_id", "first_name", "last_name", "email", "role", "status");
        }]);
        
        $schools = $query->paginate(25);
        
        // Transform the data for frontend
        $schools->getCollection()->transform(function ($school) use ($statusColumn) {
            $totalUsers = $school->users->count();
            
            return [
                "id" => $school->id,
                "name" => $school->name,
                "code" => $school->code,
                "email" => $school->email,
                "phone" => $school->phone,
                "address" => $school->address,
                "website" => $school->website,
                "established_year" => $school->established_year,
                "principal_name" => $school->principal_name,
                "school_type" => $school->school_type,
                "is_active" => $school->{$statusColumn},
                "status" => $school->{$statusColumn},
                "created_at_formatted" => $school->created_at->format("M j, Y g:i A"),
                "total_users" => $totalUsers,
                "status_label" => $school->{$statusColumn} ? "Active" : "Inactive",
            ];
        });
        
        return response()->json([
            "success" => true,
            "message" => "Schools data retrieved successfully",
            "data" => $schools->items(),
            "meta" => [
                "current_page" => $schools->currentPage(),
                "last_page" => $schools->lastPage(),
                "per_page" => $schools->perPage(),
                "total" => $schools->total(),
                "from" => $schools->firstItem(),
                "to" => $schools->lastItem(),
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            "success" => false,
            "message" => "Failed to fetch schools",
            "error" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine()
        ], 500);
    }
});