<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exam\Models\Exam;
use App\Modules\Exam\Models\ExamResult;
use App\Modules\Exam\Requests\ExamRequest;
use App\Modules\Exam\Requests\ExamResultRequest;
use App\Modules\Exam\Services\ExamService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    protected $examService;

    public function __construct(ExamService $examService)
    {
        $this->middleware('auth:sanctum');
        $this->examService = $examService;
    }

    /**
     * Get exams with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $filters = $request->only([
                'search', 'school_id', 'class_id', 'subject_id', 'academic_year',
                'exam_date_from', 'exam_date_to', 'sort_by', 'sort_order', 'per_page'
            ]);

            // Apply role-based filtering
            if (!$user->isSuperAdmin()) {
                $filters['school_id'] = $user->school_id;
            }

            $exams = $this->examService->getExams($filters);

            ActivityLogger::log('Exams List Viewed', 'Exams', [
                'filters' => $filters,
                'total_results' => $exams->total()
            ]);

            return response()->json([
                'success' => true,
                'data' => $exams,
                'message' => 'Exams retrieved successfully'
            ]);

        } catch (\Exception $e) {
            ActivityLogger::log('Exams List View Error', 'Exams', [
                'error' => $e->getMessage(),
                'filters' => $filters ?? []
            ], 'error');
            
            Log::error('Failed to retrieve exams: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exams',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a new exam
     */
    public function store(ExamRequest $request): JsonResponse
    {
        try {
            $examData = $request->validated();
            
            // Set school_id for non-SuperAdmin users
            if (!Auth::user()->isSuperAdmin()) {
                $examData['school_id'] = Auth::user()->school_id;
            }

            $exam = $this->examService->createExam($examData);

            ActivityLogger::log('Exam Created', 'Exams', [
                'exam_id' => $exam->id,
                'exam_name' => $exam->name,
                'class_id' => $exam->class_id,
                'subject_id' => $exam->subject_id,
                'exam_date' => $exam->exam_date,
                'total_marks' => $exam->total_marks
            ]);

            return response()->json([
                'success' => true,
                'data' => $exam->load(['school', 'class', 'subject']),
                'message' => 'Exam created successfully'
            ], 201);

        } catch (\Exception $e) {
            ActivityLogger::log('Exam Creation Failed', 'Exams', [
                'error' => $e->getMessage(),
                'input_data' => $request->validated()
            ], 'error');
            
            Log::error('Failed to create exam: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create exam',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Show a specific exam
     */
    public function show(Exam $exam): JsonResponse
    {
        try {
            // Check authorization
            $user = Auth::user();
            if (!$user->isSuperAdmin() && $user->school_id !== $exam->school_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $exam->load(['school', 'class', 'subject', 'results.student.user']);

            ActivityLogger::log('Exam Viewed', 'Exams', [
                'exam_id' => $exam->id,
                'exam_name' => $exam->name
            ]);

            return response()->json([
                'success' => true,
                'data' => $exam,
                'message' => 'Exam retrieved successfully'
            ]);

        } catch (\Exception $e) {
            ActivityLogger::log('Exam View Error', 'Exams', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage()
            ], 'error');
            
            Log::error('Failed to retrieve exam: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exam',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update an exam
     */
    public function update(ExamRequest $request, Exam $exam): JsonResponse
    {
        try {
            // Check authorization
            $user = Auth::user();
            if (!$user->isSuperAdmin() && $user->school_id !== $exam->school_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $originalData = $exam->toArray();
            $examData = $request->validated();
            
            $exam = $this->examService->updateExam($exam, $examData);

            ActivityLogger::log('Exam Updated', 'Exams', [
                'exam_id' => $exam->id,
                'exam_name' => $exam->name,
                'changes' => array_diff_assoc($examData, $originalData)
            ]);

            return response()->json([
                'success' => true,
                'data' => $exam->fresh(['school', 'class', 'subject']),
                'message' => 'Exam updated successfully'
            ]);

        } catch (\Exception $e) {
            ActivityLogger::log('Exam Update Failed', 'Exams', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage(),
                'input_data' => $request->validated()
            ], 'error');
            
            Log::error('Failed to update exam: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update exam',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete an exam
     */
    public function destroy(Exam $exam): JsonResponse
    {
        try {
            // Check authorization
            $user = Auth::user();
            if (!$user->isSuperAdmin() && $user->school_id !== $exam->school_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check if exam has results
            if ($exam->results()->count() > 0) {
                ActivityLogger::log('Exam Deletion Failed', 'Exams', [
                    'exam_id' => $exam->id,
                    'exam_name' => $exam->name,
                    'reason' => 'Cannot delete exam with existing results'
                ], 'warning');

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete exam with existing results'
                ], 422);
            }

            ActivityLogger::log('Exam Deleted', 'Exams', [
                'exam_id' => $exam->id,
                'exam_name' => $exam->name
            ]);

            $this->examService->deleteExam($exam);

            return response()->json([
                'success' => true,
                'message' => 'Exam deleted successfully'
            ]);

        } catch (\Exception $e) {
            ActivityLogger::log('Exam Deletion Error', 'Exams', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage()
            ], 'error');
            
            Log::error('Failed to delete exam: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exam',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get exam statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $schoolId = $user->isSuperAdmin() ? $request->school_id : $user->school_id;
            
            $statistics = $this->examService->getExamStatistics($schoolId);

            ActivityLogger::log('Exam Statistics Viewed', 'Exams', [
                'school_id' => $schoolId
            ]);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Exam statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            ActivityLogger::log('Exam Statistics Error', 'Exams', [
                'error' => $e->getMessage()
            ], 'error');
            
            Log::error('Failed to retrieve exam statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exam statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store exam results
     */
    public function storeResults(ExamResultRequest $request, Exam $exam): JsonResponse
    {
        try {
            // Check authorization
            $user = Auth::user();
            if (!$user->isSuperAdmin() && $user->school_id !== $exam->school_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $results = $this->examService->storeExamResults($exam, $request->validated());

            ActivityLogger::log('Exam Results Added', 'Exams', [
                'exam_id' => $exam->id,
                'exam_name' => $exam->name,
                'results_count' => count($results)
            ]);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Exam results stored successfully'
            ], 201);

        } catch (\Exception $e) {
            ActivityLogger::log('Exam Results Storage Failed', 'Exams', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage()
            ], 'error');
            
            Log::error('Failed to store exam results: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to store exam results',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get exam results
     */
    public function getResults(Exam $exam): JsonResponse
    {
        try {
            // Check authorization
            $user = Auth::user();
            if (!$user->isSuperAdmin() && $user->school_id !== $exam->school_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $results = $this->examService->getExamResults($exam);

            ActivityLogger::log('Exam Results Viewed', 'Exams', [
                'exam_id' => $exam->id,
                'exam_name' => $exam->name
            ]);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Exam results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            ActivityLogger::log('Exam Results View Error', 'Exams', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage()
            ], 'error');
            
            Log::error('Failed to retrieve exam results: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve exam results',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Generate exam report
     */
    public function generateReport(Exam $exam): JsonResponse
    {
        try {
            // Check authorization
            $user = Auth::user();
            if (!$user->isSuperAdmin() && $user->school_id !== $exam->school_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $report = $this->examService->generateExamReport($exam);

            ActivityLogger::log('Exam Report Generated', 'Exams', [
                'exam_id' => $exam->id,
                'exam_name' => $exam->name
            ]);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Exam report generated successfully'
            ]);

        } catch (\Exception $e) {
            ActivityLogger::log('Exam Report Generation Failed', 'Exams', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage()
            ], 'error');
            
            Log::error('Failed to generate exam report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate exam report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get student exam results
     */
    public function getStudentResults(Request $request, int $studentId): JsonResponse
    {
        try {
            // Check authorization
            $user = Auth::user();
            $student = \App\Modules\Student\Models\Student::findOrFail($studentId);
            
            if (!$user->isSuperAdmin() && 
                !($user->isAdmin() && $user->school_id === $student->school_id) &&
                !($user->isTeacher() && $user->school_id === $student->school_id) &&
                !($user->isStudent() && $user->student && $user->student->id === $studentId) &&
                !($user->isParent() && $user->student && $user->student->id === $studentId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $results = $this->examService->getStudentExamResults($studentId, $request->all());

            ActivityLogger::log('Student Exam Results Viewed', 'Exams', [
                'student_id' => $studentId,
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Student exam results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            ActivityLogger::log('Student Exam Results Error', 'Exams', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ], 'error');
            
            Log::error('Failed to retrieve student exam results: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student exam results',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
