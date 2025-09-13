<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Http\Requests\AttendanceRequest;
use App\Http\Requests\BulkAttendanceRequest;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->middleware('auth:sanctum');
        $this->attendanceService = $attendanceService;
    }

    /**
     * Display a listing of attendance records
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        $filters = $request->only([
            'date', 'start_date', 'end_date', 'class_id', 'student_id', 
            'subject_id', 'teacher_id', 'status', 'academic_year_id', 
            'school_id', 'verified', 'sort_by', 'sort_order', 'per_page'
        ]);

        $attendance = $this->attendanceService->getAttendanceRecords($filters);

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'Attendance records retrieved successfully'
        ]);
    }

    /**
     * Store a newly created attendance record
     */
    public function store(AttendanceRequest $request): JsonResponse
    {
        $this->authorize('create', Attendance::class);

        $attendance = $this->attendanceService->createAttendanceRecord(
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'Attendance recorded successfully'
        ], 201);
    }

    /**
     * Display the specified attendance record
     */
    public function show(Attendance $attendance): JsonResponse
    {
        $this->authorize('view', $attendance);

        $attendance->load([
            'student.user', 
            'teacher.user', 
            'class', 
            'subject', 
            'school'
        ]);

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => 'Attendance record retrieved successfully'
        ]);
    }

    /**
     * Update the specified attendance record
     */
    public function update(AttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        $this->authorize('update', $attendance);

        $updatedAttendance = $this->attendanceService->updateAttendanceRecord(
            $attendance,
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'data' => $updatedAttendance,
            'message' => 'Attendance record updated successfully'
        ]);
    }

    /**
     * Remove the specified attendance record
     */
    public function destroy(Attendance $attendance): JsonResponse
    {
        $this->authorize('delete', $attendance);

        $this->attendanceService->deleteAttendanceRecord($attendance, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Attendance record deleted successfully'
        ]);
    }

    /**
     * Bulk store attendance records
     */
    public function bulkStore(BulkAttendanceRequest $request): JsonResponse
    {
        $this->authorize('create', Attendance::class);

        $result = $this->attendanceService->createBulkAttendanceRecords(
            $request->validated(),
            auth()->id()
        );

        $statusCode = $result['error_count'] > 0 ? 207 : 201; // 207 Multi-Status if there are errors

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => "Bulk attendance processed. {$result['created_count']} records created, {$result['error_count']} errors."
        ], $statusCode);
    }

    /**
     * Get attendance statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

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
    }
}
