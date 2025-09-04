<?php

namespace App\Modules\Student\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Requests\StoreStudentRequest;
use App\Modules\Student\Requests\UpdateStudentRequest;
use App\Modules\Student\Resources\StudentResource;
use App\Modules\Student\Resources\StudentCollection;
use App\Modules\Student\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ActivityLogger;

class StudentController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->middleware('auth:sanctum');
        $this->studentService = $studentService;
    }

    /**
     * Display a listing of students with filters and search
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
        
        ActivityLogger::logStudent('View Students List', [
            'filters' => $request->only(['class_id', 'school_id', 'section', 'search', 'status', 'sort_by', 'sort_order'])
        ]);
        
        $query = Student::with(['user', 'school']);

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

        // Section filter
        if ($request->filled('section')) {
            $query->where('section', $request->section);
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
        $sortBy = $request->get('sort_by', 'admission_number');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'name') {
            $query->join('users', 'students.user_id', '=', 'users.id')
                  ->orderBy('users.first_name', $sortOrder)
                  ->select('students.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $students = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'data' => new StudentCollection($students),
                'links' => [
                    'first' => $students->url(1),
                    'last' => $students->url($students->lastPage()),
                    'prev' => $students->previousPageUrl(),
                    'next' => $students->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                    'from' => $students->firstItem(),
                    'to' => $students->lastItem(),
                ],
            ],
            'filters' => [
                'class_id' => $request->class_id,
                'school_id' => $request->school_id,
                'section' => $request->section,
                'search' => $request->search,
                'status' => $request->status,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]
        ]);
    }

    /**
     * Store a newly created student
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        // Check authorization
        if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $studentData = $request->validated();
        
        // Set school_id for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $studentData['school_id'] = auth()->user()->school_id;
        }

        $student = $this->studentService->createStudent($studentData);

        ActivityLogger::logStudent('Student Created', [
            'student_id' => $student->id,
            'admission_number' => $student->admission_number,
            'user_id' => $student->user_id,
            'school_id' => $student->school_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully',
            'data' => new StudentResource($student->load(['user', 'school']))
        ], 201);
    }

    /**
     * Display the specified student
     */
    public function show(Student $student): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $student->school_id) &&
            !($currentUser->isTeacher() && $currentUser->school_id === $student->school_id) &&
            !($currentUser->isStudent() && $currentUser->student && $currentUser->student->id === $student->id) &&
            !($currentUser->isParent() && $currentUser->student && $currentUser->student->id === $student->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        ActivityLogger::logStudent('View Student Details', [
            'student_id' => $student->id,
            'admission_number' => $student->admission_number
        ]);
        
        $student->load(['user', 'school']);

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student)
        ]);
    }

    /**
     * Update the specified student
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $student->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $studentData = $request->validated();
        
        $originalData = $student->toArray();
        $student = $this->studentService->updateStudent($student, $studentData);

        ActivityLogger::logStudent('Student Updated', [
            'student_id' => $student->id,
            'admission_number' => $student->admission_number,
            'changes' => array_diff_assoc($studentData, $originalData)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully',
            'data' => new StudentResource($student->fresh(['user', 'school']))
        ]);
    }

    /**
     * Remove the specified student
     */
    public function destroy(Student $student): JsonResponse
    {
        // Check authorization
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin() && 
            !($currentUser->isAdmin() && $currentUser->school_id === $student->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        ActivityLogger::logStudent('Student Deleted', [
            'student_id' => $student->id,
            'admission_number' => $student->admission_number,
            'user_id' => $student->user_id
        ]);
        
        $this->studentService->deleteStudent($student);

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Get student performance data
     */
    public function performance(Student $student): JsonResponse
    {
        $performance = $this->studentService->getStudentPerformance($student);

        return response()->json([
            'success' => true,
            'data' => $performance
        ]);
    }

    /**
     * Promote student to next class
     */
    public function promote(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'new_class_id' => 'required|exists:classes,id',
            'new_section' => 'nullable|string|max:10',
            'new_roll_number' => 'nullable|integer|min:1',
        ]);

        $promoted = $this->studentService->promoteStudent(
            $student,
            $request->new_class_id,
            $request->new_section,
            $request->new_roll_number
        );

        ActivityLogger::logStudent('Student Promoted', [
            'student_id' => $student->id,
            'admission_number' => $student->admission_number,
            'old_class_id' => $student->class_id,
            'new_class_id' => $request->new_class_id,
            'old_section' => $student->section,
            'new_section' => $request->new_section
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student promoted successfully',
            'data' => new StudentResource($promoted->fresh(['user', 'school']))
        ]);
    }

    /**
     * Get students statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->studentService->getStudentStatistics(
            auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
        );

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get students with low attendance
     */
    public function getLowAttendanceStudents(Request $request): JsonResponse
    {
        $threshold = $request->get('threshold', 75);
        $schoolId = auth()->user()->isSuperAdmin() ? $request->school_id : auth()->user()->school_id;
        
        $students = $this->studentService->getStudentsWithLowAttendance($threshold, $schoolId);
        
        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Get top performing students
     */
    public function getTopPerformers(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $schoolId = auth()->user()->isSuperAdmin() ? $request->school_id : auth()->user()->school_id;
        
        $students = $this->studentService->getTopPerformers($limit, $schoolId);
        
        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Bulk import students from CSV/Excel
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
            'school_id' => 'required_if:role,SuperAdmin|exists:schools,id'
        ]);
        
        $schoolId = auth()->user()->isSuperAdmin() ? $request->school_id : auth()->user()->school_id;
        
        try {
            $result = $this->studentService->bulkImportStudents($request->file('file'), $schoolId);
            
            ActivityLogger::logStudent('Students Bulk Import', [
                'file_name' => $request->file('file')->getClientOriginalName(),
                'imported_count' => $result['imported'],
                'failed_count' => $result['failed'],
                'school_id' => $schoolId
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Students imported successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export students to CSV/Excel
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $filters = $request->only(['class_id', 'school_id', 'section', 'status', 'search']);
        
        if (!auth()->user()->isSuperAdmin()) {
            $filters['school_id'] = auth()->user()->school_id;
        }
        
        try {
            return $this->studentService->exportStudents($filters, $format);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student profile with detailed information
     */
    public function getProfile(Student $student): JsonResponse
    {
        $profile = $this->studentService->getStudentProfile($student);
        
        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }

    /**
     * Bulk update student status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'status' => 'required|boolean'
        ]);
        
        $result = $this->studentService->bulkUpdateStatus(
            $request->student_ids,
            $request->input('status')
        );
        
        ActivityLogger::logStudent('Students Bulk Status Update', [
            'student_ids' => $request->student_ids,
            'status' => $request->boolean('status'),
            'updated_count' => $result['updated']
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Student statuses updated successfully',
            'data' => $result
        ]);
    }

    /**
     * Transfer student to another school
     */
    public function transfer(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'new_school_id' => 'required|exists:schools,id'
        ]);
        
        $transferred = $this->studentService->transferStudent(
            $student,
            $request->new_school_id
        );
        
        ActivityLogger::logStudent('Student Transferred', [
            'student_id' => $student->id,
            'admission_number' => $student->admission_number,
            'old_school_id' => $student->school_id,
            'new_school_id' => $request->new_school_id
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Student transferred successfully',
            'data' => new StudentResource($transferred->fresh(['user', 'school']))
        ]);
    }
}
