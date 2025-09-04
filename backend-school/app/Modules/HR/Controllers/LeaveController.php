<?php

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\LeaveRequest;
use App\Modules\HR\Models\LeaveType;
use App\Modules\HR\Requests\LeaveRequestRequest;
use App\Modules\HR\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    /**
     * Display a listing of leave requests
     */
    public function index(Request $request): JsonResponse
    {
        $leaveRequests = LeaveRequest::with(['employee.user', 'leaveType', 'approver.user'])
            ->when($request->search, function ($query, $search) {
                return $query->whereHas('employee.user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->employee_id, function ($query, $employeeId) {
                return $query->where('employee_id', $employeeId);
            })
            ->when($request->leave_type_id, function ($query, $leaveTypeId) {
                return $query->where('leave_type_id', $leaveTypeId);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                return $query->where('start_date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                return $query->where('end_date', '<=', $dateTo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($leaveRequests);
    }

    /**
     * Store a newly created leave request
     */
    public function store(LeaveRequestRequest $request): JsonResponse
    {
        $leaveRequest = $this->leaveService->createLeaveRequest($request->validated());

        return response()->json([
            'message' => 'Leave request submitted successfully',
            'leave_request' => $leaveRequest->load(['employee.user', 'leaveType'])
        ], 201);
    }

    /**
     * Display the specified leave request
     */
    public function show(LeaveRequest $leaveRequest): JsonResponse
    {
        return response()->json([
            'leave_request' => $leaveRequest->load([
                'employee.user', 'leaveType', 'approver.user', 'documents'
            ])
        ]);
    }

    /**
     * Update the specified leave request
     */
    public function update(LeaveRequestRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        // Only allow updates if status is pending
        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot update leave request that is not pending'
            ], 422);
        }

        $leaveRequest = $this->leaveService->updateLeaveRequest($leaveRequest, $request->validated());

        return response()->json([
            'message' => 'Leave request updated successfully',
            'leave_request' => $leaveRequest->load(['employee.user', 'leaveType'])
        ]);
    }

    /**
     * Remove the specified leave request
     */
    public function destroy(LeaveRequest $leaveRequest): JsonResponse
    {
        // Only allow deletion if status is pending
        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot delete leave request that is not pending'
            ], 422);
        }

        $leaveRequest->delete();

        return response()->json([
            'message' => 'Leave request deleted successfully'
        ]);
    }

    /**
     * Approve leave request
     */
    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $request->validate([
            'approver_comments' => 'nullable|string|max:500',
        ]);

        $result = $this->leaveService->approveLeaveRequest(
            $leaveRequest, 
            auth()->user()->employee->id,
            $request->approver_comments
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'message' => 'Leave request approved successfully',
            'leave_request' => $leaveRequest->fresh()->load(['employee.user', 'approver.user'])
        ]);
    }

    /**
     * Reject leave request
     */
    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $request->validate([
            'approver_comments' => 'required|string|max:500',
        ]);

        $result = $this->leaveService->rejectLeaveRequest(
            $leaveRequest,
            auth()->user()->employee->id,
            $request->approver_comments
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'message' => 'Leave request rejected',
            'leave_request' => $leaveRequest->fresh()->load(['employee.user', 'approver.user'])
        ]);
    }

    /**
     * Get employee leave balance
     */
    public function balance(Request $request): JsonResponse
    {
        $employeeId = $request->employee_id ?? auth()->user()->employee->id;
        $year = $request->year ?? date('Y');

        $balance = $this->leaveService->getLeaveBalance($employeeId, $year);

        return response()->json([
            'employee_id' => $employeeId,
            'year' => $year,
            'balance' => $balance
        ]);
    }

    /**
     * Get leave types
     */
    public function leaveTypes(Request $request): JsonResponse
    {
        $leaveTypes = LeaveType::where('school_id', $request->school_id)
            ->where('is_active', true)
            ->get();

        return response()->json($leaveTypes);
    }

    /**
     * Get leave calendar
     */
    public function calendar(Request $request): JsonResponse
    {
        $year = $request->year ?? date('Y');
        $month = $request->month ?? date('m');
        $departmentId = $request->department_id;

        $calendar = $this->leaveService->getLeaveCalendar($year, $month, $departmentId);

        return response()->json([
            'year' => $year,
            'month' => $month,
            'calendar' => $calendar
        ]);
    }
}