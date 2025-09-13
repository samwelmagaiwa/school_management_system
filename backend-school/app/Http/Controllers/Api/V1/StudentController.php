<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->middleware('auth:sanctum');
        $this->studentService = $studentService;
    }

    /**
     * Display a listing of students.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Student::class);
        
        $query = Student::with(['user', 'school', 'schoolClass']);

        // Apply school scope for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->bySchool(auth()->user()->school_id);
        }

        // Apply filters
        if ($request->filled('class_id')) {
            $query->byClass($request->class_id);
        }

        if ($request->filled('school_id') && auth()->user()->isSuperAdmin()) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('section')) {
            $query->bySection($request->section);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

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

        return StudentResource::collection($students);
    }

    /**
     * Store a newly created student.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $this->authorize('create', Student::class);
        
        try {
            DB::beginTransaction();
            
            $validatedData = $request->validated();
            
            // Set school_id for non-SuperAdmin users
            if (!auth()->user()->isSuperAdmin()) {
                $validatedData['school_id'] = auth()->user()->school_id;
            }
            
            $student = $this->studentService->createStudent($validatedData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student created successfully',
                'data' => new StudentResource($student->load(['user', 'school', 'schoolClass']))
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);
        
        $student->load(['user', 'school', 'schoolClass', 'parent']);
        
        return response()->json([
            'success' => true,
            'data' => new StudentResource($student)
        ]);
    }

    /**
     * Update the specified student.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);
        
        try {
            DB::beginTransaction();
            
            $validatedData = $request->validated();
            $student = $this->studentService->updateStudent($student, $validatedData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
                'data' => new StudentResource($student->fresh(['user', 'school', 'schoolClass']))
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Student $student): JsonResponse
    {
        $this->authorize('delete', $student);
        
        try {
            DB::beginTransaction();
            
            $this->studentService->deleteStudent($student);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Student::class);
        
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;
        $stats = $this->studentService->getStudentStatistics($schoolId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Promote student to next class.
     */
    public function promote(Request $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);
        
        $request->validate([
            'new_class_id' => 'required|exists:classes,id',
            'new_section' => 'nullable|string|max:10',
            'new_roll_number' => 'nullable|integer|min:1',
        ]);

        try {
            DB::beginTransaction();
            
            $promoted = $this->studentService->promoteStudent(
                $student,
                $request->new_class_id,
                $request->new_section,
                $request->new_roll_number
            );
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student promoted successfully',
                'data' => new StudentResource($promoted->fresh(['user', 'school', 'schoolClass']))
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to promote student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer student to another school.
     */
    public function transfer(Request $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);
        
        $request->validate([
            'new_school_id' => 'required|exists:schools,id'
        ]);
        
        try {
            DB::beginTransaction();
            
            $transferred = $this->studentService->transferStudent(
                $student,
                $request->new_school_id
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student transferred successfully',
                'data' => new StudentResource($transferred->fresh(['user', 'school', 'schoolClass']))
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update student status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);
        
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'status' => 'required|boolean'
        ]);
        
        try {
            DB::beginTransaction();
            
            $result = $this->studentService->bulkUpdateStatus(
                $request->student_ids,
                $request->boolean('status')
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student statuses updated successfully',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student statuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export students data.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Student::class);
        
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
                'message' => 'Failed to export students',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}