<?php

namespace App\Modules\Subject\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Subject\Models\Subject;
use App\Modules\Subject\Requests\StoreSubjectRequest;
use App\Modules\Subject\Requests\UpdateSubjectRequest;
use App\Modules\Subject\Resources\SubjectResource;
use App\Modules\Subject\Resources\SubjectCollection;
use App\Modules\Subject\Services\SubjectService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubjectController extends Controller
{
    protected SubjectService $subjectService;

    public function __construct(SubjectService $subjectService)
    {
        $this->middleware('auth:sanctum');
        $this->subjectService = $subjectService;
    }

    /**
     * Display a listing of subjects with filters and search
     */
    public function index(Request $request): JsonResponse
    {
        // Check authorization
        if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin', 'Teacher'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        ActivityLogger::log('View Subjects List', 'Subjects', [
            'filters' => $request->only(['class_id', 'school_id', 'type', 'search', 'status', 'sort_by', 'sort_order'])
        ]);

        $query = Subject::with(['school', 'teacher', 'class']);

        // Apply school scope for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->bySchool(auth()->user()->school_id);
        }

        // Class filter
        if ($request->filled('class_id')) {
            $query->byClass($request->class_id);
        }

        // School filter (only for SuperAdmin)
        if ($request->filled('school_id') && auth()->user()->isSuperAdmin()) {
            $query->bySchool($request->school_id);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

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

        $subjects = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => new SubjectCollection($subjects),
            'meta' => [
                'current_page' => $subjects->currentPage(),
                'last_page' => $subjects->lastPage(),
                'per_page' => $subjects->perPage(),
                'total' => $subjects->total(),
            ],
            'filters' => [
                'class_id' => $request->class_id,
                'school_id' => $request->school_id,
                'type' => $request->type,
                'search' => $request->search,
                'status' => $request->status,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]
        ]);
    }

    /**
     * Store a newly created subject
     */
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        // Check authorization
        if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $subjectData = $request->validated();

        // Set school_id for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $subjectData['school_id'] = auth()->user()->school_id;
        }

        $subject = $this->subjectService->createSubject($subjectData);

        ActivityLogger::log('Subject Created', 'Subjects', [
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'subject_code' => $subject->code,
            'school_id' => $subject->school_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => new SubjectResource($subject->load(['school', 'teacher', 'class']))
        ], 201);
    }

    /**
     * Display the specified subject
     */
    public function show(Subject $subject): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $subject->school_id) &&
            !($currentUser->isTeacher() && $currentUser->school_id === $subject->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        ActivityLogger::log('View Subject Details', 'Subjects', [
            'subject_id' => $subject->id,
            'subject_name' => $subject->name
        ]);

        $subject->load(['school', 'teacher', 'class', 'exams', 'fees']);

        return response()->json([
            'success' => true,
            'data' => new SubjectResource($subject)
        ]);
    }

    /**
     * Update the specified subject
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $subject->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $subjectData = $request->validated();
        $originalData = $subject->toArray();
        
        $subject = $this->subjectService->updateSubject($subject, $subjectData);

        ActivityLogger::log('Subject Updated', 'Subjects', [
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'changes' => array_diff_assoc($subjectData, $originalData)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
            'data' => new SubjectResource($subject->fresh(['school', 'teacher', 'class']))
        ]);
    }

    /**
     * Remove the specified subject
     */
    public function destroy(Subject $subject): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $subject->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Check if subject has associated exams or fees
        if ($subject->exams()->exists() || $subject->fees()->exists()) {
            ActivityLogger::log('Subject Deletion Failed', 'Subjects', [
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'reason' => 'Has associated exams or fees'
            ], 'warning');

            return response()->json([
                'success' => false,
                'message' => 'Cannot delete subject with associated exams or fees'
            ], 422);
        }

        ActivityLogger::log('Subject Deleted', 'Subjects', [
            'subject_id' => $subject->id,
            'subject_name' => $subject->name
        ]);

        $this->subjectService->deleteSubject($subject);

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully'
        ]);
    }

    /**
     * Get available subject types
     */
    public function getTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Subject::TYPES
        ]);
    }

    /**
     * Get subjects by class
     */
    public function getByClass(int $classId): JsonResponse
    {
        $query = Subject::byClass($classId)->active();

        // Apply school scope for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->bySchool(auth()->user()->school_id);
        }

        $subjects = $query->get();

        return response()->json([
            'success' => true,
            'data' => SubjectResource::collection($subjects)
        ]);
    }

    /**
     * Get subject statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->subjectService->getSubjectStatistics(
            auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
        );

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    /**
     * Export subjects data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $filters = $request->only(['class_id', 'school_id', 'type', 'status', 'search']);
        
        if (!auth()->user()->isSuperAdmin()) {
            $filters['school_id'] = auth()->user()->school_id;
        }
        
        try {
            return $this->subjectService->exportSubjects($filters, $format);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting subjects: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate subject code uniqueness
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:10',
            'school_id' => 'nullable|exists:schools,id',
            'exclude_id' => 'nullable|exists:subjects,id'
        ]);
        
        $schoolId = $request->school_id ?? auth()->user()->school_id;
        
        $exists = Subject::where('code', $request->code)
                         ->where('school_id', $schoolId)
                         ->when($request->exclude_id, function ($query, $excludeId) {
                             return $query->where('id', '!=', $excludeId);
                         })
                         ->exists();
        
        return response()->json([
            'success' => true,
            'data' => [
                'code' => $request->code,
                'available' => !$exists,
                'message' => $exists ? 'Subject code already exists' : 'Subject code is available'
            ]
        ]);
    }
    
    /**
     * Assign teachers to subject
     */
    public function assignTeachers(Request $request, Subject $subject): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $subject->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $request->validate([
            'teacher_ids' => 'required|array',
            'teacher_ids.*' => 'exists:teachers,id'
        ]);
        
        $result = $this->subjectService->assignTeachers($subject, $request->teacher_ids);
        
        ActivityLogger::log('Teachers Assigned to Subject', 'Subjects', [
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'teacher_ids' => $request->teacher_ids,
            'assigned_count' => $result['assigned']
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Teachers assigned successfully',
            'data' => $result
        ]);
    }
    
    /**
     * Get subject prerequisites
     */
    public function getPrerequisites(Subject $subject): JsonResponse
    {
        $prerequisites = $this->subjectService->getSubjectPrerequisites($subject);
        
        return response()->json([
            'success' => true,
            'data' => [
                'subject' => new SubjectResource($subject),
                'prerequisites' => $prerequisites
            ]
        ]);
    }
}
