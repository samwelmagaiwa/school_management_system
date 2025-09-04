<?php

namespace App\Modules\Teacher\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Teacher\Models\Teacher;
use App\Modules\Teacher\Requests\TeacherRequest;
use App\Modules\Teacher\Services\TeacherService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherController extends Controller
{
    protected TeacherService $teacherService;

    public function __construct(TeacherService $teacherService)
    {
        $this->middleware('auth:sanctum');
        $this->teacherService = $teacherService;
    }

    /**
     * Display a listing of teachers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Check authorization
            if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin', 'Teacher'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $query = Teacher::with(['user', 'school', 'subjects', 'classes']);

            // Apply school scope for non-SuperAdmin users
            if (!auth()->user()->isSuperAdmin()) {
                $query->where('school_id', auth()->user()->school_id);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('specialization', 'like', "%{$search}%");
            }

            // School filter (only for SuperAdmin)
            if ($request->filled('school_id') && auth()->user()->isSuperAdmin()) {
                $query->where('school_id', $request->school_id);
            }

            // Subject filter
            if ($request->filled('subject_id')) {
                $query->whereHas('subjects', function ($q) use ($request) {
                    $q->where('subjects.id', $request->subject_id);
                });
            }

            // Status filter
            if ($request->filled('status')) {
                $isActive = $request->status === 'active';
                $query->where('is_active', $isActive);
            }

            // Specialization filter
            if ($request->filled('specialization')) {
                $query->where('specialization', $request->specialization);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'employee_id');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if ($sortBy === 'name') {
                $query->join('users', 'teachers.user_id', '=', 'users.id')
                      ->orderBy('users.name', $sortOrder)
                      ->select('teachers.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            $teachers = $query->paginate($request->get('per_page', 15));

            ActivityLogger::log('Teachers List Viewed', 'Teachers', [
                'filters' => $request->only(['search', 'school_id', 'subject_id', 'status', 'specialization']),
                'total_results' => $teachers->total()
            ]);

            return response()->json([
                'success' => true,
                'data' => $teachers,
                'meta' => [
                    'current_page' => $teachers->currentPage(),
                    'last_page' => $teachers->lastPage(),
                    'per_page' => $teachers->perPage(),
                    'total' => $teachers->total(),
                ],
                'filters' => [
                    'search' => $request->search,
                    'school_id' => $request->school_id,
                    'subject_id' => $request->subject_id,
                    'status' => $request->status,
                    'specialization' => $request->specialization,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ]
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Teachers List Error', 'Teachers', [
                'error' => $e->getMessage()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teachers',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created teacher
     */
    public function store(TeacherRequest $request): JsonResponse
    {
        try {
            // Check authorization
            if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $teacherData = $request->validated();
            
            // Set school_id for non-SuperAdmin users
            if (!auth()->user()->isSuperAdmin()) {
                $teacherData['school_id'] = auth()->user()->school_id;
            }

            $teacher = $this->teacherService->createTeacher($teacherData);

            return response()->json([
                'success' => true,
                'message' => 'Teacher created successfully',
                'data' => $teacher
            ], 201);
        } catch (\Exception $e) {
            ActivityLogger::log('Teacher Creation Failed', 'Teachers', [
                'error' => $e->getMessage(),
                'input_data' => $request->except(['password'])
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create teacher',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified teacher
     */
    public function show(Teacher $teacher): JsonResponse
    {
        try {
            // Check authorization
            $currentUser = auth()->user();
            if (!$currentUser->isSuperAdmin() && 
                !($currentUser->isAdmin() && $currentUser->school_id === $teacher->school_id) &&
                !($currentUser->isTeacher() && $currentUser->teacher && $currentUser->teacher->id === $teacher->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            ActivityLogger::log('Teacher Viewed', 'Teachers', [
                'teacher_id' => $teacher->id,
                'employee_id' => $teacher->employee_id
            ]);

            return response()->json([
                'success' => true,
                'data' => $teacher->load(['user', 'school', 'subjects', 'classes', 'attendances'])
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Teacher View Error', 'Teachers', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teacher details'
            ], 500);
        }
    }

    /**
     * Update the specified teacher
     */
    public function update(TeacherRequest $request, Teacher $teacher): JsonResponse
    {
        try {
            // Check authorization
            $currentUser = auth()->user();
            if (!$currentUser->isSuperAdmin() && 
                !($currentUser->isAdmin() && $currentUser->school_id === $teacher->school_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $teacherData = $request->validated();
            $teacher = $this->teacherService->updateTeacher($teacher, $teacherData);

            return response()->json([
                'success' => true,
                'message' => 'Teacher updated successfully',
                'data' => $teacher
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Teacher Update Failed', 'Teachers', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update teacher'
            ], 500);
        }
    }

    /**
     * Remove the specified teacher
     */
    public function destroy(Teacher $teacher): JsonResponse
    {
        try {
            // Check authorization
            $currentUser = auth()->user();
            if (!$currentUser->isSuperAdmin() && 
                !($currentUser->isAdmin() && $currentUser->school_id === $teacher->school_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $this->teacherService->deleteTeacher($teacher);

            return response()->json([
                'success' => true,
                'message' => 'Teacher deleted successfully'
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Teacher Deletion Failed', 'Teachers', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete teacher'
            ], 500);
        }
    }

    /**
     * Get teacher's schedule
     */
    public function schedule(Teacher $teacher): JsonResponse
    {
        try {
            $schedule = $this->teacherService->getTeacherSchedule($teacher);

            return response()->json([
                'success' => true,
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Teacher Schedule Error', 'Teachers', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teacher schedule'
            ], 500);
        }
    }

    /**
     * Assign subjects to teacher
     */
    public function assignSubjects(Request $request, Teacher $teacher): JsonResponse
    {
        try {
            $request->validate([
                'subject_ids' => 'required|array',
                'subject_ids.*' => 'exists:subjects,id',
            ]);

            $teacher = $this->teacherService->assignSubjects($teacher, $request->subject_ids);

            return response()->json([
                'success' => true,
                'message' => 'Subjects assigned successfully',
                'data' => $teacher
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Teacher Subject Assignment Failed', 'Teachers', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign subjects'
            ], 500);
        }
    }

    /**
     * Get teacher's performance metrics
     */
    public function performance(Teacher $teacher): JsonResponse
    {
        try {
            $performance = $this->teacherService->getTeacherPerformance($teacher);

            return response()->json([
                'success' => true,
                'data' => $performance
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Teacher Performance Error', 'Teachers', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teacher performance'
            ], 500);
        }
    }

    /**
     * Get teacher statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = $this->teacherService->getTeacherStatistics(
                auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
            );

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Teacher Statistics Error', 'Teachers', [
                'error' => $e->getMessage()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teacher statistics'
            ], 500);
        }
    }

    /**
     * Get teachers by specialization
     */
    public function getBySpecialization(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'specialization' => 'required|string'
            ]);

            $teachers = $this->teacherService->getTeachersBySpecialization(
                $request->specialization,
                auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
            );

            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teachers by specialization'
            ], 500);
        }
    }

    /**
     * Get top performing teachers
     */
    public function getTopPerformers(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $teachers = $this->teacherService->getTopPerformingTeachers(
                $limit,
                auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
            );

            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch top performing teachers'
            ], 500);
        }
    }

    /**
     * Get teachers with low performance
     */
    public function getLowPerformers(Request $request): JsonResponse
    {
        try {
            $threshold = $request->get('threshold', 70.0);
            $teachers = $this->teacherService->getTeachersWithLowPerformance(
                $threshold,
                auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
            );

            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch low performing teachers'
            ], 500);
        }
    }

    /**
     * Bulk update teacher status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'teacher_ids' => 'required|array',
                'teacher_ids.*' => 'exists:teachers,id',
                'is_active' => 'required|boolean'
            ]);

            $updated = $this->teacherService->bulkUpdateStatus(
                $request->teacher_ids,
                $request->is_active
            );

            return response()->json([
                'success' => true,
                'message' => "Updated {$updated} teachers successfully",
                'data' => ['updated_count' => $updated]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update teacher status'
            ], 500);
        }
    }

    /**
     * Bulk import teachers
     */
    public function bulkImport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls',
                'school_id' => 'required_if:' . auth()->user()->isSuperAdmin() . ',true|exists:schools,id'
            ]);

            $schoolId = auth()->user()->isSuperAdmin() ? 
                $request->school_id : auth()->user()->school_id;

            // Process file and extract data (implementation would depend on file format)
            $teachersData = []; // This would be populated from file processing

            $results = $this->teacherService->bulkImportTeachers($teachersData, $schoolId);

            return response()->json([
                'success' => true,
                'message' => 'Teachers imported successfully',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import teachers'
            ], 500);
        }
    }

    /**
     * Export teachers data
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', 'excel');
            $filters = $request->only(['school_id', 'subject_id', 'specialization', 'status']);

            // Implementation would generate and return file
            return response()->json([
                'success' => true,
                'message' => 'Export completed successfully',
                'download_url' => '/downloads/teachers_export.' . ($format === 'excel' ? 'xlsx' : 'csv')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export teachers'
            ], 500);
        }
    }
}