<?php

namespace App\Services;

use App\Models\User;
use App\Models\School;
use App\Models\Teacher;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class HRService
{
    /**
     * Get paginated employees with filters
     */
    public function getEmployees(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::with(['user', 'department', 'position', 'manager.user'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('employee_id', 'like', "%{$search}%");
            })
            ->when($filters['school_id'] ?? null, function ($query, $schoolId) {
                return $query->where('school_id', $schoolId);
            })
            ->when($filters['department_id'] ?? null, function ($query, $departmentId) {
                return $query->where('department_id', $departmentId);
            })
            ->when($filters['position_id'] ?? null, function ($query, $positionId) {
                return $query->where('position_id', $positionId);
            })
            ->when($filters['employment_type'] ?? null, function ($query, $type) {
                return $query->where('employment_type', $type);
            })
            ->when($filters['employment_status'] ?? null, function ($query, $status) {
                return $query->where('employment_status', $status);
            })
            ->when($filters['is_active'] ?? null, function ($query, $isActive) {
                return $query->where('is_active', $isActive);
            })
            ->orderBy('created_at', 'desc');

        $employees = $query->paginate($perPage);

        ActivityLogger::log('Employees List Retrieved', 'HR', [
            'filters' => $filters,
            'total_results' => $employees->total(),
            'per_page' => $perPage
        ]);

        return $employees;
    }

    /**
     * Create a new employee
     */
    public function createEmployee(array $data): Employee
    {
        DB::beginTransaction();
        
        try {
            // Generate employee ID if not provided
            if (!isset($data['employee_id'])) {
                $data['employee_id'] = $this->generateEmployeeId($data['school_id']);
            }

            $employee = Employee::create($data);

            // Clear related caches
            $this->clearHRCaches();

            DB::commit();

            ActivityLogger::log('Employee Created', 'HR', [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_id,
                'user_id' => $employee->user_id,
                'department_id' => $employee->department_id,
                'position_id' => $employee->position_id
            ]);

            return $employee->load(['user', 'department', 'position', 'manager.user']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Employee Creation Failed', 'HR', [
                'error' => $e->getMessage(),
                'input_data' => $data
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Update an existing employee
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        DB::beginTransaction();
        
        try {
            $originalData = $employee->toArray();
            
            $employee->update($data);

            // Clear related caches
            $this->clearHRCaches();

            DB::commit();

            ActivityLogger::log('Employee Updated', 'HR', [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_id,
                'changes' => array_diff_assoc($data, $originalData)
            ]);

            return $employee->load(['user', 'department', 'position', 'manager.user']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Employee Update Failed', 'HR', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'input_data' => $data
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Delete an employee
     */
    public function deleteEmployee(Employee $employee): bool
    {
        DB::beginTransaction();
        
        try {
            // Check if employee has active leave requests or payrolls
            $activeLeaves = $employee->leaveRequests()->where('status', 'pending')->count();
            if ($activeLeaves > 0) {
                throw new \Exception("Cannot delete employee with {$activeLeaves} pending leave requests.");
            }

            $employeeData = [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_id,
                'user_id' => $employee->user_id
            ];

            // Soft delete the employee
            $employee->delete();

            // Clear related caches
            $this->clearHRCaches();

            DB::commit();

            ActivityLogger::log('Employee Deleted', 'HR', $employeeData);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Employee Deletion Failed', 'HR', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Get HR dashboard statistics
     */
    public function getHRDashboard(int $schoolId): array
    {
        $cacheKey = "hr_dashboard_{$schoolId}";
        
        return Cache::remember($cacheKey, 300, function () use ($schoolId) {
            $stats = [
                'total_employees' => Employee::where('school_id', $schoolId)->count(),
                'active_employees' => Employee::where('school_id', $schoolId)->active()->count(),
                'departments' => Department::where('school_id', $schoolId)->count(),
                'positions' => Position::whereHas('department', function($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                })->count(),
                'pending_leave_requests' => LeaveRequest::whereHas('employee', function($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                })->where('status', 'pending')->count(),
                'employees_on_leave' => $this->getEmployeesOnLeaveToday($schoolId),
                'upcoming_birthdays' => $this->getUpcomingBirthdays($schoolId),
                'new_hires_this_month' => $this->getNewHiresThisMonth($schoolId),
                'employment_type_distribution' => $this->getEmploymentTypeDistribution($schoolId),
                'department_distribution' => $this->getDepartmentDistribution($schoolId),
                'leave_statistics' => $this->getLeaveStatistics($schoolId),
                'payroll_summary' => $this->getPayrollSummary($schoolId)
            ];

            ActivityLogger::log('HR Dashboard Retrieved', 'HR', [
                'school_id' => $schoolId,
                'statistics' => $stats
            ]);

            return $stats;
        });
    }

    /**
     * Get departments with statistics
     */
    public function getDepartments(int $schoolId, array $filters = []): Collection
    {
        $query = Department::where('school_id', $schoolId)
            ->withCount(['employees', 'positions'])
            ->with(['head.user'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($filters['is_active'] ?? null, function ($query, $isActive) {
                return $query->where('is_active', $isActive);
            });

        return $query->get();
    }

    /**
     * Create a new department
     */
    public function createDepartment(array $data): Department
    {
        DB::beginTransaction();
        
        try {
            $department = Department::create($data);

            // Clear related caches
            $this->clearHRCaches();

            DB::commit();

            ActivityLogger::log('Department Created', 'HR', [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'school_id' => $department->school_id
            ]);

            return $department->load(['head.user']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Department Creation Failed', 'HR', [
                'error' => $e->getMessage(),
                'input_data' => $data
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Get leave requests with filters
     */
    public function getLeaveRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LeaveRequest::with(['employee.user', 'leaveType', 'approver.user'])
            ->when($filters['school_id'] ?? null, function ($query, $schoolId) {
                return $query->whereHas('employee', function($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                });
            })
            ->when($filters['employee_id'] ?? null, function ($query, $employeeId) {
                return $query->where('employee_id', $employeeId);
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($filters['leave_type_id'] ?? null, function ($query, $leaveTypeId) {
                return $query->where('leave_type_id', $leaveTypeId);
            })
            ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
                return $query->where('start_date', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
                return $query->where('end_date', '<=', $dateTo);
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Process leave request approval/rejection
     */
    public function processLeaveRequest(LeaveRequest $leaveRequest, string $action, array $data = []): LeaveRequest
    {
        DB::beginTransaction();
        
        try {
            $leaveRequest->update([
                'status' => $action,
                'approver_id' => auth()->id(),
                'approved_at' => $action === 'approved' ? now() : null,
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'approver_comments' => $data['comments'] ?? null
            ]);

            // Clear related caches
            $this->clearHRCaches();

            DB::commit();

            ActivityLogger::log('Leave Request Processed', 'HR', [
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $leaveRequest->employee_id,
                'action' => $action,
                'approver_id' => auth()->id()
            ]);

            return $leaveRequest->load(['employee.user', 'leaveType', 'approver.user']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Leave Request Processing Failed', 'HR', [
                'leave_request_id' => $leaveRequest->id,
                'action' => $action,
                'error' => $e->getMessage()
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Generate employee ID
     */
    private function generateEmployeeId(int $schoolId): string
    {
        $year = date('Y');
        $lastEmployee = Employee::where('school_id', $schoolId)
            ->where('employee_id', 'like', "EMP{$year}%")
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_id, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "EMP{$year}" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get employees on leave today
     */
    private function getEmployeesOnLeaveToday(int $schoolId): int
    {
        return LeaveRequest::whereHas('employee', function($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })
        ->where('status', 'approved')
        ->where('start_date', '<=', today())
        ->where('end_date', '>=', today())
        ->count();
    }

    /**
     * Get upcoming birthdays
     */
    private function getUpcomingBirthdays(int $schoolId): array
    {
        $nextWeek = now()->addWeek();
        
        return Employee::where('school_id', $schoolId)
            ->active()
            ->whereNotNull('date_of_birth')
            ->whereRaw('DATE_FORMAT(date_of_birth, "%m-%d") BETWEEN ? AND ?', [
                now()->format('m-d'),
                $nextWeek->format('m-d')
            ])
            ->with('user')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'date_of_birth' => $employee->date_of_birth,
                    'department' => $employee->department?->name
                ];
            })
            ->toArray();
    }

    /**
     * Get new hires this month
     */
    private function getNewHiresThisMonth(int $schoolId): int
    {
        return Employee::where('school_id', $schoolId)
            ->whereMonth('hire_date', now()->month)
            ->whereYear('hire_date', now()->year)
            ->count();
    }

    /**
     * Get employment type distribution
     */
    private function getEmploymentTypeDistribution(int $schoolId): array
    {
        return Employee::where('school_id', $schoolId)
            ->active()
            ->selectRaw('employment_type, COUNT(*) as count')
            ->groupBy('employment_type')
            ->pluck('count', 'employment_type')
            ->toArray();
    }

    /**
     * Get department distribution
     */
    private function getDepartmentDistribution(int $schoolId): array
    {
        return Employee::where('school_id', $schoolId)
            ->active()
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->selectRaw('departments.name, COUNT(*) as count')
            ->groupBy('departments.name')
            ->pluck('count', 'name')
            ->toArray();
    }

    /**
     * Get leave statistics
     */
    private function getLeaveStatistics(int $schoolId): array
    {
        $currentYear = now()->year;
        
        return [
            'total_requests' => LeaveRequest::whereHas('employee', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->whereYear('created_at', $currentYear)->count(),
            
            'approved_requests' => LeaveRequest::whereHas('employee', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('status', 'approved')->whereYear('created_at', $currentYear)->count(),
            
            'pending_requests' => LeaveRequest::whereHas('employee', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('status', 'pending')->count(),
            
            'rejected_requests' => LeaveRequest::whereHas('employee', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('status', 'rejected')->whereYear('created_at', $currentYear)->count()
        ];
    }

    /**
     * Get payroll summary
     */
    private function getPayrollSummary(int $schoolId): array
    {
        $currentMonth = now()->format('Y-m');
        
        return [
            'total_payrolls' => Payroll::whereHas('employee', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('pay_period', 'like', "{$currentMonth}%")->count(),
            
            'processed_payrolls' => Payroll::whereHas('employee', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('pay_period', 'like', "{$currentMonth}%")
            ->where('status', 'processed')->count(),
            
            'total_amount' => Payroll::whereHas('employee', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->where('pay_period', 'like', "{$currentMonth}%")
            ->sum('net_pay')
        ];
    }

    /**
     * Clear HR-related caches
     */
    private function clearHRCaches(): void
    {
        // Clear dashboard caches
        $cacheKeys = Cache::getRedis()->keys('*hr_dashboard_*');
        if (!empty($cacheKeys)) {
            Cache::getRedis()->del($cacheKeys);
        }
    }
}