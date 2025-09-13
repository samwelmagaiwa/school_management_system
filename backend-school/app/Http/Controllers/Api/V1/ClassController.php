<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Http\Resources\ClassResource;
use App\Services\ClassService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClassController extends Controller
{
    protected ClassService $classService;

    public function __construct(ClassService $classService)
    {
        $this->middleware('auth:sanctum');
        $this->classService = $classService;
    }

    /**
     * Display a listing of classes
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SchoolClass::class);

        $query = SchoolClass::with(['school', 'classTeacher', 'students']);

        // Apply school scope for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->bySchool(auth()->user()->school_id);
        }

        // Apply filters
        if ($request->filled('school_id') && auth()->user()->isSuperAdmin()) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $classes = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => ClassResource::collection($classes),
            'meta' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'per_page' => $classes->perPage(),
                'total' => $classes->total(),
            ]
        ]);
    }

    /**
     * Store a newly created class
     */
    public function store(StoreClassRequest $request): JsonResponse
    {
        $this->authorize('create', SchoolClass::class);

        $classData = $request->validated();

        if (!auth()->user()->isSuperAdmin()) {
            $classData['school_id'] = auth()->user()->school_id;
        }

        $class = $this->classService->createClass($classData);

        return response()->json([
            'success' => true,
            'message' => 'Class created successfully',
            'data' => new ClassResource($class->load(['school', 'classTeacher']))
        ], 201);
    }

    /**
     * Display the specified class
     */
    public function show(SchoolClass $class): JsonResponse
    {
        $this->authorize('view', $class);

        $class->load(['school', 'classTeacher', 'students', 'subjects']);

        return response()->json([
            'success' => true,
            'data' => new ClassResource($class)
        ]);
    }

    /**
     * Update the specified class
     */
    public function update(UpdateClassRequest $request, SchoolClass $class): JsonResponse
    {
        $this->authorize('update', $class);

        $class = $this->classService->updateClass($class, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully',
            'data' => new ClassResource($class->fresh(['school', 'classTeacher']))
        ]);
    }

    /**
     * Remove the specified class
     */
    public function destroy(SchoolClass $class): JsonResponse
    {
        $this->authorize('delete', $class);

        $this->classService->deleteClass($class);

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully'
        ]);
    }
}
