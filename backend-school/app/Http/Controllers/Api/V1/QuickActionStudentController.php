<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Http\Requests\QuickAddStudentRequest;
use App\Http\Resources\StudentResource;
use App\Services\QuickActionStudentService;
use App\Models\SchoolClass;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ActivityLogger;

class QuickActionStudentController extends Controller
{
    protected QuickActionStudentService $studentService;

    public function __construct(QuickActionStudentService $studentService)
    {
        $this->middleware('auth:sanctum');
        $this->studentService = $studentService;
    }

    /**
     * Get quick add student form data
     */
    public function getFormData(): JsonResponse
    {
        // Check authorization
        if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $user = auth()->user();
        $data = [];

        // Get schools (SuperAdmin only)
        if ($user->isSuperAdmin()) {
            $data['schools'] = School::where('is_active', true)
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get();
        }

        // Get classes based on user role
        if ($user->isSuperAdmin()) {
            $data['classes'] = SchoolClass::with('school:id,name')
                ->where('status', true)
                ->select('id', 'name', 'grade', 'school_id', 'capacity')
                ->orderBy('grade')
                ->orderBy('name')
                ->get();
        } else {
            $data['classes'] = SchoolClass::where('school_id', $user->school_id)
                ->where('status', true)
                ->select('id', 'name', 'grade', 'capacity')
                ->orderBy('grade')
                ->orderBy('name')
                ->get();
        }

        // Get next admission number
        $schoolId = $user->isSuperAdmin() ? null : $user->school_id;
        $data['next_admission_number'] = $this->studentService->getNextAdmissionNumber($schoolId);

        // Get available sections
        $data['sections'] = ['A', 'B', 'C', 'D', 'E'];

        // Get blood groups
        $data['blood_groups'] = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        // Get genders
        $data['genders'] = [
            ['value' => 'male', 'label' => 'Male'],
            ['value' => 'female', 'label' => 'Female'],
            ['value' => 'other', 'label' => 'Other']
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Quick add student
     */
    public function quickAdd(QuickAddStudentRequest $request): JsonResponse
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

        try {
            $student = $this->studentService->quickCreateStudent($studentData);

            ActivityLogger::logStudent('Quick Add Student', [
                'student_id' => $student->id,
                'admission_number' => $student->admission_number,
                'user_id' => $student->user_id,
                'school_id' => $student->school_id,
                'class_id' => $student->class_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student added successfully',
                'data' => new StudentResource($student->load(['user', 'school', 'schoolClass']))
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding student: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get students list for quick actions
     */
    public function getStudentsList(Request $request): JsonResponse
    {
        // Check authorization
        if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin', 'Teacher'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $query = Student::with(['user:id,first_name,last_name,email', 'schoolClass:id,name,grade', 'school:id,name'])
            ->where('status', true);

        // Apply school scope for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->where('school_id', auth()->user()->school_id);
        }

        // Apply filters
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('admission_number', 'like', "%{$search}%")
              ->orWhere('roll_number', 'like', "%{$search}%");
        }

        $students = $query->orderBy('admission_number')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'students' => $students->items(),
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                    'from' => $students->firstItem(),
                    'to' => $students->lastItem(),
                ]
            ]
        ]);
    }

    /**
     * Validate admission number availability
     */
    public function validateAdmissionNumber(Request $request): JsonResponse
    {
        $request->validate([
            'admission_number' => 'required|string',
            'school_id' => 'required_if:role,SuperAdmin|exists:schools,id'
        ]);

        $schoolId = auth()->user()->isSuperAdmin() ? $request->school_id : auth()->user()->school_id;
        
        $exists = Student::where('admission_number', $request->admission_number)
            ->where('school_id', $schoolId)
            ->exists();

        return response()->json([
            'success' => true,
            'available' => !$exists,
            'message' => $exists ? 'Admission number already exists' : 'Admission number is available'
        ]);
    }

    /**
     * Get quick statistics
     */
    public function getStats(): JsonResponse
    {
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;
        $stats = $this->studentService->getQuickStats($schoolId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}