<?php

namespace App\Modules\School\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\School\Models\School;
use App\Modules\School\Requests\StoreSchoolRequest;
use App\Modules\School\Requests\UpdateSchoolRequest;
use App\Modules\School\Resources\SchoolResource;
use App\Modules\School\Resources\SchoolCollection;
use App\Modules\School\Services\SchoolService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ActivityLogger;
use Exception;

class SchoolController extends Controller
{
    protected SchoolService $schoolService;

    public function __construct(SchoolService $schoolService)
    {
        $this->middleware('auth:sanctum');
        $this->schoolService = $schoolService;
    }

    /**
     * Display a listing of schools with pagination and search
     */
    public function index(Request $request): JsonResponse
    {
        // Check authorization
        if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        ActivityLogger::logSchool('View Schools List', [
            'filters' => $request->only(['search', 'status', 'sort_by', 'sort_order'])
        ]);
        
        $query = School::query();

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $schools = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => new SchoolCollection($schools),
            'meta' => [
                'current_page' => $schools->currentPage(),
                'last_page' => $schools->lastPage(),
                'per_page' => $schools->perPage(),
                'total' => $schools->total(),
            ],
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]
        ]);
    }

    /**
     * Store a newly created school
     */
    public function store(StoreSchoolRequest $request): JsonResponse
    {
        // Check authorization - only SuperAdmin can create schools
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $school = School::create($request->validated());

        ActivityLogger::logSchool('School Created', [
            'school_id' => $school->id,
            'school_name' => $school->name,
            'school_code' => $school->code
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School created successfully',
            'data' => new SchoolResource($school)
        ], 201);
    }

    /**
     * Display the specified school
     */
    public function show(School $school): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $school->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        ActivityLogger::logSchool('View School Details', [
            'school_id' => $school->id,
            'school_name' => $school->name
        ]);
        
        $school->load(['users', 'students']);

        return response()->json([
            'success' => true,
            'data' => new SchoolResource($school)
        ]);
    }

    /**
     * Update the specified school
     */
    public function update(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $school->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $originalData = $school->toArray();
        $school->update($request->validated());

        ActivityLogger::logSchool('School Updated', [
            'school_id' => $school->id,
            'school_name' => $school->name,
            'changes' => array_diff_assoc($request->validated(), $originalData)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School updated successfully',
            'data' => new SchoolResource($school->fresh())
        ]);
    }

    /**
     * Remove the specified school
     */
    public function destroy(School $school): JsonResponse
    {
        // Check authorization - only SuperAdmin can delete schools
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        // Check if school has associated users or students
        if ($school->users()->exists() || $school->students()->exists()) {
            ActivityLogger::logSchool('School Deletion Failed', [
                'school_id' => $school->id,
                'school_name' => $school->name,
                'reason' => 'Has associated users or students'
            ], 'warning');
            
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete school with associated users or students'
            ], 422);
        }

        ActivityLogger::logSchool('School Deleted', [
            'school_id' => $school->id,
            'school_name' => $school->name
        ]);
        
        $school->delete();

        return response()->json([
            'success' => true,
            'message' => 'School deleted successfully'
        ]);
    }

    /**
     * Get system-wide statistics
     */
    public function systemStatistics(Request $request): JsonResponse
    {
        // Check authorization - only SuperAdmin can view system statistics
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $stats = $this->schoolService->getSchoolStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get individual school statistics
     */
    public function schoolStatistics(School $school): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $school->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $stats = $this->schoolService->getSchoolStatistics($school);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve school statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get school dashboard data
     */
    public function dashboard(School $school): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $school->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $dashboard = $this->schoolService->getSchoolDashboard($school);

            return response()->json([
                'success' => true,
                'data' => $dashboard
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get school settings
     */
    public function getSettings(School $school): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $school->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $settings = $this->schoolService->getSchoolSettings($school);

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve school settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update school settings
     */
    public function updateSettings(Request $request, School $school): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $school->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $updatedSchool = $this->schoolService->updateSchoolSettings($school, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'School settings updated successfully',
                'data' => new SchoolResource($updatedSchool)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update school settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}