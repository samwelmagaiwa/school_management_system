<?php

namespace App\Modules\Class\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Class\Models\SchoolClass;
use App\Modules\Class\Requests\ClassRequest;
use App\Modules\Class\Services\ClassService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClassController extends Controller
{
    protected ClassService $classService;

    public function __construct(ClassService $classService)
    {
        $this->classService = $classService;
    }
    /**
     * Display a listing of classes
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'school_id', 'grade', 'academic_year', 'is_active']);
            $perPage = $request->get('per_page', 15);
            
            $classes = $this->classService->getClasses($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Classes List Error', 'Classes', [
                'error' => $e->getMessage(),
                'filters' => $request->only(['search', 'school_id', 'grade'])
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch classes',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created class
     */
    public function store(ClassRequest $request): JsonResponse
    {
        try {
            $class = $this->classService->createClass($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Class created successfully',
                'data' => $class
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create class',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified class
     */
    public function show(SchoolClass $class): JsonResponse
    {
        try {
            $classData = $class->load([
                'school', 
                'classTeacher.user', 
                'students.user', 
                'subjects', 
                'timetable.subject',
                'timetable.teacher.user'
            ]);

            // Add computed attributes
            $classData->current_strength = $class->students()->where('is_active', true)->count();
            $classData->capacity_percentage = $class->capacity > 0 ? 
                round(($classData->current_strength / $class->capacity) * 100, 1) : 0;

            ActivityLogger::log('Class Details Viewed', 'Classes', [
                'class_id' => $class->id,
                'class_name' => $class->name
            ]);

            return response()->json([
                'success' => true,
                'data' => $classData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch class details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified class
     */
    public function update(ClassRequest $request, SchoolClass $class): JsonResponse
    {
        try {
            $updatedClass = $this->classService->updateClass($class, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Class updated successfully',
                'data' => $updatedClass
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update class',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified class
     */
    public function destroy(SchoolClass $class): JsonResponse
    {
        try {
            $this->classService->deleteClass($class);

            return response()->json([
                'success' => true,
                'message' => 'Class deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Cannot delete class'
            ], 400);
        }
    }

    /**
     * Get class statistics
     */
    public function statistics(SchoolClass $class = null): JsonResponse
    {
        try {
            if ($class) {
                // Individual class statistics
                $stats = [
                    'total_students' => $class->students()->count(),
                    'male_students' => $class->students()->whereHas('user', function($q) {
                        $q->where('gender', 'male');
                    })->count(),
                    'female_students' => $class->students()->whereHas('user', function($q) {
                        $q->where('gender', 'female');
                    })->count(),
                    'average_attendance' => $class->getAverageAttendance(),
                    'subjects_count' => $class->subjects()->count(),
                    'capacity_utilization' => $class->capacity > 0 ? 
                        round(($class->students()->count() / $class->capacity) * 100, 1) : 0
                ];

                return response()->json([
                    'success' => true,
                    'data' => [
                        'class' => $class->load(['school', 'classTeacher.user']),
                        'statistics' => $stats
                    ]
                ]);
            } else {
                // Overall class statistics
                $filters = request()->only(['school_id', 'academic_year']);
                $stats = $this->classService->getClassStatistics($filters);

                return response()->json([
                    'success' => true,
                    'data' => $stats
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get class timetable
     */
    public function timetable(SchoolClass $class): JsonResponse
    {
        $timetable = $class->timetable()
            ->with(['subject', 'teacher'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return response()->json([
            'class' => $class->load(['school']),
            'timetable' => $timetable
        ]);
    }

    /**
     * Assign students to class
     */
    public function assignStudents(Request $request, SchoolClass $class): JsonResponse
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        try {
            $result = $this->classService->assignStudents($class, $request->student_ids);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['class']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to assign students'
            ], 400);
        }
    }

    /**
     * Assign subjects to class
     */
    public function assignSubjects(Request $request, SchoolClass $class): JsonResponse
    {
        $request->validate([
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        try {
            $result = $this->classService->assignSubjects($class, $request->subject_ids);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['class']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to assign subjects'
            ], 400);
        }
    }

    /**
     * Get available grades
     */
    public function getGrades(): JsonResponse
    {
        try {
            $grades = $this->classService->getAvailableGrades();

            return response()->json([
                'success' => true,
                'data' => $grades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch grades',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available academic years
     */
    public function getAcademicYears(): JsonResponse
    {
        try {
            $years = $this->classService->getAvailableAcademicYears();

            return response()->json([
                'success' => true,
                'data' => $years
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch academic years',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}