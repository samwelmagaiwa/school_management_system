<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\User;

use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LeaveService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new leave request
     */
    public function createLeaveRequest(array $data): LeaveRequest
    {
        DB::beginTransaction();
        
        try {
            $leaveRequest = LeaveRequest::create($data);

            // Handle document uploads
            if (isset($data['documents'])) {
                $this->handleDocumentUploads($leaveRequest, $data['documents']);
            }

            // Send notification to manager
            $this->notificationService->sendLeaveRequestNotification($leaveRequest);

            DB::commit();
            return $leaveRequest;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update a leave request
     */
    public function updateLeaveRequest(LeaveRequest $leaveRequest, array $data): LeaveRequest
    {
        DB::beginTransaction();
        
        try {
            $leaveRequest->update($data);

            // Handle new document uploads
            if (isset($data['documents'])) {
                $this->handleDocumentUploads($leaveRequest, $data['documents']);
            }

            DB::commit();
            return $leaveRequest;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Approve a leave request
     */
    public function approveLeaveRequest(LeaveRequest $leaveRequest, int $approverId, string $comments = null): array
    {
        // Check if employee has sufficient leave balance
        $leaveBalance = $this->getLeaveBalance($leaveRequest->employee_id, date('Y'));
        $leaveTypeCode = $leaveRequest->leaveType->code ?? 'annual';
        
        if (!$this->hassufficientBalance($leaveBalance, $leaveTypeCode, $leaveRequest->days_requested)) {
            return [
                'success' => false,
                'message' => 'Insufficient leave balance for this request.'
            ];
        }

        DB::beginTransaction();
        
        try {
            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_APPROVED,
                'approver_id' => $approverId,
                'approved_at' => now(),
                'approver_comments' => $comments,
            ]);

            // Update leave balance
            $this->updateLeaveBalance($leaveRequest);

            // Send approval notification
            $this->notificationService->sendLeaveApprovalNotification($leaveRequest);

            DB::commit();
            return ['success' => true, 'message' => 'Leave request approved successfully.'];

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Reject a leave request
     */
    public function rejectLeaveRequest(LeaveRequest $leaveRequest, int $approverId, string $comments): array
    {
        DB::beginTransaction();
        
        try {
            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_REJECTED,
                'approver_id' => $approverId,
                'approved_at' => now(),
                'approver_comments' => $comments,
            ]);

            // Send rejection notification
            $this->notificationService->sendLeaveRejectionNotification($leaveRequest);

            DB::commit();
            return ['success' => true, 'message' => 'Leave request rejected.'];

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get leave balance for an employee
     */
    public function getLeaveBalance(int $employeeId, int $year): array
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return [];
        }

        $leaveTypes = LeaveType::where('school_id', $employee->school_id)
            ->where('is_active', true)
            ->get();

        $balance = [];

        foreach ($leaveTypes as $leaveType) {
            $allocated = $leaveType->days_per_year;
            $used = LeaveRequest::where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveType->id)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->whereYear('start_date', $year)
                ->sum('days_requested');

            $balance[$leaveType->code] = [
                'type_name' => $leaveType->name,
                'allocated' => $allocated,
                'used' => $used,
                'remaining' => max(0, $allocated - $used),
                'carry_forward' => $this->getCarryForwardDays($employeeId, $leaveType->id, $year),
            ];
        }

        return $balance;
    }

    /**
     * Get leave calendar for a period
     */
    public function getLeaveCalendar(int $year, int $month, int $departmentId = null): array
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $query = LeaveRequest::with(['employee.user', 'leaveType'])
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->dateRange($startDate, $endDate);

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $leaveRequests = $query->get();

        $calendar = [];
        
        foreach ($leaveRequests as $leave) {
            $current = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);

            while ($current <= $end) {
                if ($current->year == $year && $current->month == $month) {
                    $dateKey = $current->format('Y-m-d');
                    
                    if (!isset($calendar[$dateKey])) {
                        $calendar[$dateKey] = [];
                    }

                    $calendar[$dateKey][] = [
                        'employee_name' => $leave->employee->full_name,
                        'leave_type' => $leave->leaveType->name,
                        'leave_type_color' => $leave->leaveType->color ?? '#007bff',
                        'is_half_day' => $leave->is_half_day,
                        'half_day_period' => $leave->half_day_period,
                    ];
                }
                $current->addDay();
            }
        }

        return $calendar;
    }

    /**
     * Get leave statistics for reporting
     */
    public function getLeaveStatistics(int $schoolId, int $year, int $departmentId = null): array
    {
        $query = LeaveRequest::whereHas('employee', function ($q) use ($schoolId, $departmentId) {
            $q->where('school_id', $schoolId);
            if ($departmentId) {
                $q->where('department_id', $departmentId);
            }
        })->whereYear('start_date', $year);

        return [
            'total_requests' => $query->count(),
            'approved_requests' => $query->where('status', LeaveRequest::STATUS_APPROVED)->count(),
            'pending_requests' => $query->where('status', LeaveRequest::STATUS_PENDING)->count(),
            'rejected_requests' => $query->where('status', LeaveRequest::STATUS_REJECTED)->count(),
            'total_days_taken' => $query->where('status', LeaveRequest::STATUS_APPROVED)->sum('days_requested'),
            'by_leave_type' => $this->getLeaveByType($schoolId, $year, $departmentId),
            'by_month' => $this->getLeaveByMonth($schoolId, $year, $departmentId),
            'by_department' => $departmentId ? [] : $this->getLeaveByDepartment($schoolId, $year),
        ];
    }

    /**
     * Handle document uploads for leave request
     */
    private function handleDocumentUploads(LeaveRequest $leaveRequest, array $documents): void
    {
        foreach ($documents as $document) {
            $fileName = time() . '_' . $document->getClientOriginalName();
            $filePath = $document->storeAs('leave_documents', $fileName, 'public');

            $leaveRequest->documents()->create([
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $document->getClientMimeType(),
                'file_size' => $document->getSize(),
                'uploaded_by' => $leaveRequest->employee_id,
            ]);
        }
    }

    /**
     * Check if employee has sufficient leave balance
     */
    private function hassufficientBalance(array $leaveBalance, string $leaveTypeCode, float $requestedDays): bool
    {
        if (!isset($leaveBalance[$leaveTypeCode])) {
            return false;
        }

        $available = $leaveBalance[$leaveTypeCode]['remaining'] + $leaveBalance[$leaveTypeCode]['carry_forward'];
        return $available >= $requestedDays;
    }

    /**
     * Update leave balance after approval
     */
    private function updateLeaveBalance(LeaveRequest $leaveRequest): void
    {
        // This would update the leave balance tracking
        // For now, we rely on real-time calculation
        // In a production system, you might want to maintain a separate leave_balances table
    }

    /**
     * Get carry forward days for a leave type
     */
    private function getCarryForwardDays(int $employeeId, int $leaveTypeId, int $year): float
    {
        $leaveType = LeaveType::find($leaveTypeId);
        
        if (!$leaveType || !$leaveType->carry_forward_allowed) {
            return 0;
        }

        $previousYear = $year - 1;
        $allocated = $leaveType->days_per_year;
        $used = LeaveRequest::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $previousYear)
            ->sum('days_requested');

        $unused = max(0, $allocated - $used);
        return min($unused, $leaveType->max_carry_forward_days ?? 0);
    }

    /**
     * Get leave statistics by type
     */
    private function getLeaveByType(int $schoolId, int $year, int $departmentId = null): array
    {
        $query = LeaveRequest::with('leaveType')
            ->whereHas('employee', function ($q) use ($schoolId, $departmentId) {
                $q->where('school_id', $schoolId);
                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }
            })
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year);

        return $query->selectRaw('leave_type_id, sum(days_requested) as total_days, count(*) as total_requests')
            ->groupBy('leave_type_id')
            ->with('leaveType:id,name')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->leaveType->name => [
                    'total_days' => $item->total_days,
                    'total_requests' => $item->total_requests,
                ]];
            })
            ->toArray();
    }

    /**
     * Get leave statistics by month
     */
    private function getLeaveByMonth(int $schoolId, int $year, int $departmentId = null): array
    {
        $query = LeaveRequest::whereHas('employee', function ($q) use ($schoolId, $departmentId) {
            $q->where('school_id', $schoolId);
            if ($departmentId) {
                $q->where('department_id', $departmentId);
            }
        })
        ->where('status', LeaveRequest::STATUS_APPROVED)
        ->whereYear('start_date', $year);

        return $query->selectRaw('MONTH(start_date) as month, sum(days_requested) as total_days, count(*) as total_requests')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                return [Carbon::create()->month($item->month)->format('F') => [
                    'total_days' => $item->total_days,
                    'total_requests' => $item->total_requests,
                ]];
            })
            ->toArray();
    }

    /**
     * Get leave statistics by department
     */
    private function getLeaveByDepartment(int $schoolId, int $year): array
    {
        return LeaveRequest::with('employee.department')
            ->whereHas('employee', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->get()
            ->groupBy('employee.department.name')
            ->map(function ($leaves) {
                return [
                    'total_days' => $leaves->sum('days_requested'),
                    'total_requests' => $leaves->count(),
                ];
            })
            ->toArray();
    }
}