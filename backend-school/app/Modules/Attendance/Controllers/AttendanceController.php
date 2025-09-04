<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Models\Attendance;
use App\Modules\Attendance\Models\AttendanceSummary;
use App\Modules\Attendance\Requests\AttendanceRequest;
use App\Modules\Attendance\Requests\BulkAttendanceRequest;
use App\Modules\Attendance\Services\AttendanceService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Get attendance records with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date', 'start_date', 'end_date', 'class_id', 'student_id', 
                'subject_id', 'teacher_id', 'status', 'academic_year_id', 
                'school_id', 'verified', 'sort_by', 'sort_order', 'per_page'
            ]);

            $attendance = $this->attendanceService->getAttendanceRecords($filters);

            ActivityLogger::log('Attendance Records Viewed', 'Attendance', [
                'filters' => $filters,
                'total_results' => $attendance->total()
            ]);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Attendance records retrieved successfully'
            ]);

        } catch (\Exception $e) {
            ActivityLogger::log('Attendance Records View Error', 'Attendance', [
                'error' => $e->getMessage(),
                'filters' => $filters ?? []
            ], 'error');
            
            Log::error('Failed to retrieve attendance records: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance records',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a new attendance record
     */
    public function store(AttendanceRequest $request): JsonResponse
    {
        try {
            $attendance = $this->attendanceService->createAttendanceRecord(
                $request->validated(),
                Auth::id()
            );

            ActivityLogger::log('Attendance Record Created', 'Attendance', [
                'attendance_id' => $attendance->id,
                'student_id' => $attendance->student_id,
                'class_id' => $attendance->class_id,
                'status' => $attendance->status,
                'attendance_date' => $attendance->attendance_date,
                'marked_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Attendance recorded successfully'
            ], 201);

        } catch (\Exception $e) {
            ActivityLogger::log('Attendance Record Creation Failed', 'Attendance', [
                'error' => $e->getMessage(),
                'input_data' => $request->validated()
            ], 'error');
            
            Log::error('Failed to create attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to record attendance'
            ], 400);
        }
    }

    /**
     * Show a specific attendance record
     */
    public function show(Attendance $attendance): JsonResponse
    {
        try {
            $attendance->load([
                'student.user', 
                'teacher.user', 
                'class', 
                'subject', 
                'school',
                'markedBy',
                'verifiedBy',
                'excusedBy',
                'lastModifiedBy'
            ]);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Attendance record retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance record',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update an attendance record
     */
    public function update(AttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        try {
            $updatedAttendance = $this->attendanceService->updateAttendanceRecord(
                $attendance,
                $request->validated(),
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'data' => $updatedAttendance,
                'message' => 'Attendance record updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to update attendance'
            ], 400);
        }
    }

    /**
     * Delete an attendance record
     */
    public function destroy(Attendance $attendance): JsonResponse
    {
        try {
            $this->attendanceService->deleteAttendanceRecord($attendance, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Attendance record deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attendance record',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk store attendance records
     */
    public function bulkStore(BulkAttendanceRequest $request): JsonResponse
    {
        try {
            $result = $this->attendanceService->createBulkAttendanceRecords(
                $request->validated(),
                Auth::id()
            );

            $statusCode = $result['error_count'] > 0 ? 207 : 201; // 207 Multi-Status if there are errors

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Bulk attendance processed. {$result['created_count']} records created, {$result['error_count']} errors."
            ], $statusCode);

        } catch (\Exception $e) {
            Log::error('Failed to create bulk attendance records: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk attendance',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get attendance statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date', 'end_date', 'class_id', 'student_id', 
                'subject_id', 'academic_year_id', 'school_id'
            ]);

            $statistics = $this->attendanceService->getAttendanceStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Attendance statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve attendance statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get class attendance for a specific date
     */
    public function getClassAttendance(Request $request, $classId): JsonResponse
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'subject_id' => 'nullable|exists:subjects,id'
            ]);

            $attendance = $this->attendanceService->getClassAttendance(
                $classId,
                $request->date,
                $request->subject_id
            );

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Class attendance retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve class attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class attendance',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get student attendance records
     */
    public function getStudentAttendance(Request $request, $studentId): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date', 'end_date', 'class_id', 'subject_id', 
                'academic_year_id', 'status', 'per_page'
            ]);
            
            $filters['student_id'] = $studentId;

            $attendance = $this->attendanceService->getAttendanceRecords($filters);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Student attendance retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve student attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student attendance',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Verify attendance record
     */
    public function verify(Attendance $attendance): JsonResponse
    {
        try {
            $verifiedAttendance = $this->attendanceService->verifyAttendance($attendance, Auth::id());

            return response()->json([
                'success' => true,
                'data' => $verifiedAttendance,
                'message' => 'Attendance record verified successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify attendance record',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Excuse attendance record
     */
    public function excuse(Request $request, Attendance $attendance): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'nullable|string|max:500'
            ]);

            $excusedAttendance = $this->attendanceService->excuseAttendance(
                $attendance,
                Auth::id(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'data' => $excusedAttendance,
                'message' => 'Attendance record excused successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to excuse attendance record: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to excuse attendance record',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get attendance report
     */
    public function getReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'class_id', 'student_id', 'subject_id', 'month', 'year',
                'academic_year_id', 'below_minimum'
            ]);

            $report = $this->attendanceService->getAttendanceReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Attendance report generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate attendance report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate attendance report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get students with low attendance
     */
    public function getLowAttendanceStudents(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'minimum_percentage' => 'nullable|numeric|min:0|max:100',
                'academic_year_id' => 'nullable|integer'
            ]);

            $students = $this->attendanceService->getStudentsWithLowAttendance(
                $request->minimum_percentage ?? 75,
                $request->academic_year_id
            );

            return response()->json([
                'success' => true,
                'data' => $students,
                'message' => 'Students with low attendance retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve students with low attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve students with low attendance',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get attendance summary for a student
     */
    public function getStudentSummary(Request $request, $studentId): JsonResponse
    {
        try {
            $filters = $request->only(['class_id', 'subject_id', 'month', 'year', 'academic_year_id']);
            $filters['student_id'] = $studentId;

            $summaries = AttendanceSummary::with(['class', 'subject'])
                ->forStudent($studentId)
                ->when(isset($filters['class_id']), function ($query) use ($filters) {
                    return $query->forClass($filters['class_id']);
                })
                ->when(isset($filters['subject_id']), function ($query) use ($filters) {
                    return $query->forSubject($filters['subject_id']);
                })
                ->when(isset($filters['month']) && isset($filters['year']), function ($query) use ($filters) {
                    return $query->forMonth($filters['month'], $filters['year']);
                })
                ->when(isset($filters['academic_year_id']), function ($query) use ($filters) {
                    return $query->forAcademicYear($filters['academic_year_id']);
                })
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $summaries,
                'message' => 'Student attendance summary retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve student attendance summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student attendance summary',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}