<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Services\TeacherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    protected TeacherService $teacherService;

    public function __construct(TeacherService $teacherService)
    {
        $this->middleware('auth:sanctum');
        $this->teacherService = $teacherService;
    }

    /**
     * Display a listing of teachers.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Teacher::class);
        
        $query = Teacher::with(['user', 'school', 'subjects']);

        // Apply school scope for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->bySchool(auth()->user()->school_id);
        }

        // Apply filters
        if ($request->filled('school_id') && auth()->user()->isSuperAdmin()) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('employment_type')) {
            $query->byEmploymentType($request->employment_type);
        }

        if ($request->filled('search')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->search($request->search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'employee_id');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'name') {
            $query->join('users', 'teachers.user_id', '=', 'users.id')
                  ->orderBy('users.first_name', $sortOrder)
                  ->select('teachers.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $teachers = $query->paginate($request->get('per_page', 15));

        return TeacherResource::collection($teachers);
    }

    /**
     * Store a newly created teacher.
     */
    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $this->authorize('create', Teacher::class);
        
        try {
            DB::beginTransaction();
            
            $validatedData = $request->validated();
            
            // Set school_id for non-SuperAdmin users
            if (!auth()->user()->isSuperAdmin()) {
                $validatedData['school_id'] = auth()->user()->school_id;
            }
            
            $teacher = $this->teacherService->createTeacher($validatedData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Teacher created successfully',
                'data' => new TeacherResource($teacher->load(['user', 'school', 'subjects']))
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified teacher.
     */
    public function show(Teacher $teacher): JsonResponse
    {
        $this->authorize('view', $teacher);
        
        $teacher->load(['user', 'school', 'subjects', 'classes']);
        
        return response()->json([
            'success' => true,
            'data' => new TeacherResource($teacher)
        ]);
    }

    /**
     * Update the specified teacher.
     */
    public function update(UpdateTeacherRequest $request, Teacher $teacher): JsonResponse
    {
        $this->authorize('update', $teacher);
        
        try {
            DB::beginTransaction();
            
            $validatedData = $request->validated();
            $teacher = $this->teacherService->updateTeacher($teacher, $validatedData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Teacher updated successfully',
                'data' => new TeacherResource($teacher->fresh(['user', 'school', 'subjects']))
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified teacher.
     */
    public function destroy(Teacher $teacher): JsonResponse
    {
        $this->authorize('delete', $teacher);
        
        try {
            DB::beginTransaction();
            
            $this->teacherService->deleteTeacher($teacher);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Teacher deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get teacher statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Teacher::class);
        
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;
        $stats = $this->teacherService->getTeacherStatistics($schoolId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Bulk update teacher status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Teacher::class);
        
        $request->validate([
            'teacher_ids' => 'required|array',
            'teacher_ids.*' => 'exists:teachers,id',
            'status' => 'required|boolean'
        ]);
        
        try {
            DB::beginTransaction();
            
            $result = $this->teacherService->bulkUpdateStatus(
                $request->teacher_ids,
                $request->boolean('status')
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Teacher statuses updated successfully',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update teacher statuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export teachers data.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Teacher::class);
        
        $format = $request->get('format', 'excel');
        $filters = $request->only(['school_id', 'employment_type', 'status', 'search']);
        
        if (!auth()->user()->isSuperAdmin()) {
            $filters['school_id'] = auth()->user()->school_id;
        }
        
        try {
            return $this->teacherService->exportTeachers($filters, $format);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export teachers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}