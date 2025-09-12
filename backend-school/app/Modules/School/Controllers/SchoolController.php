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
            $query->where('is_active', $request->boolean('status'));
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

    /**
     * Export schools data
     */
    public function export(Request $request): mixed
    {
        // Check authorization - only SuperAdmin can export schools
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $query = School::query();

            // Apply filters from request
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->boolean('status'));
            }

            if ($request->filled('school_type')) {
                $query->where('school_type', $request->school_type);
            }

            // Get all matching schools
            $schools = $query->orderBy('name', 'asc')->get();

            // Prepare export data
            $exportData = $schools->map(function ($school) {
                return [
                    'ID' => $school->id,
                    'Name' => $school->name,
                    'Code' => $school->code,
                    'Email' => $school->email,
                    'Phone' => $school->phone,
                    'Website' => $school->website,
                    'Address' => $school->address,
                    'Principal Name' => $school->principal_name,
                    'Principal Email' => $school->principal_email,
                    'Principal Phone' => $school->principal_phone,
                    'School Type' => $school->school_type,
                    'Board Affiliation' => $school->board_affiliation,
                    'Established Year' => $school->established_year,
                    'Registration Number' => $school->registration_number,
                    'Tax ID' => $school->tax_id,
                    'Status' => $school->is_active ? 'Active' : 'Inactive',
                    'Created At' => $school->created_at?->format('Y-m-d H:i:s'),
                    'Updated At' => $school->updated_at?->format('Y-m-d H:i:s'),
                ];
            });

            ActivityLogger::logSchool('Schools Data Exported', [
                'total_records' => $schools->count(),
                'filters' => $request->only(['search', 'status', 'school_type'])
            ]);

            // Return CSV format
            $format = $request->get('format', 'csv');
            $filename = 'schools_export_' . now()->format('Y-m-d_H-i-s');

            if ($format === 'json') {
                return response()->json([
                    'success' => true,
                    'data' => $exportData,
                    'filename' => $filename . '.json',
                    'total_records' => $schools->count()
                ]);
            }

            // Generate CSV content
            $csvContent = $this->generateCsvContent($exportData);

            return response($csvContent, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
                'Content-Length' => strlen($csvContent)
            ]);
        } catch (Exception $e) {
            ActivityLogger::logSchool('Schools Export Failed', [
                'error' => $e->getMessage(),
                'filters' => $request->only(['search', 'status', 'school_type'])
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to export schools data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate CSV content from data array
     */
    private function generateCsvContent(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        // Add header row
        fputcsv($output, array_keys($data[0]));
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        return $csvContent;
    }
}
