<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Services\SchoolService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class SchoolController extends Controller
{
    protected SchoolService $schoolService;

    public function __construct(SchoolService $schoolService)
    {
        $this->middleware('auth:sanctum');
        $this->schoolService = $schoolService;
    }

    /**
     * Display a listing of schools
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', School::class);

        $query = School::with(['owner']);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $statusColumn = Schema::hasColumn('schools', 'is_active') ? 'is_active' : 'status';
            $query->where($statusColumn, $request->boolean('status'));
        }

        if ($request->filled('subscription_status')) {
            $query->where('subscription_status', $request->subscription_status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $schools = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => SchoolResource::collection($schools),
            'meta' => [
                'current_page' => $schools->currentPage(),
                'last_page' => $schools->lastPage(),
                'per_page' => $schools->perPage(),
                'total' => $schools->total(),
            ]
        ]);
    }

    /**
     * Store a newly created school
     */
    public function store(StoreSchoolRequest $request): JsonResponse
    {
        $this->authorize('create', School::class);

        $school = $this->schoolService->createSchool($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'School created successfully',
            'data' => new SchoolResource($school->load(['owner']))
        ], 201);
    }

    /**
     * Display the specified school
     */
    public function show(School $school): JsonResponse
    {
        $this->authorize('view', $school);

        $school->load(['owner', 'students', 'teachers', 'classes']);

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
        $this->authorize('update', $school);

        $school = $this->schoolService->updateSchool($school, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'School updated successfully',
            'data' => new SchoolResource($school->fresh(['owner']))
        ]);
    }

    /**
     * Remove the specified school
     */
    public function destroy(School $school): JsonResponse
    {
        $this->authorize('delete', $school);

        $this->schoolService->deleteSchool($school);

        return response()->json([
            'success' => true,
            'message' => 'School deleted successfully'
        ]);
    }

    /**
     * Get school statistics
     */
    public function statistics(School $school): JsonResponse
    {
        $this->authorize('view', $school);

        $stats = $this->schoolService->getSchoolStatistics($school);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
