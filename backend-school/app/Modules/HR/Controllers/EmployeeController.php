<?php

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\Employee;
use App\Modules\HR\Requests\EmployeeRequest;
use App\Modules\HR\Services\HRService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    protected HRService $hrService;

    public function __construct(HRService $hrService)
    {
        $this->hrService = $hrService;
    }

    /**
     * Display a listing of employees
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'school_id', 'department_id', 'position_id', 
                'employment_type', 'employment_status', 'is_active'
            ]);
            $perPage = $request->get('per_page', 15);
            
            $employees = $this->hrService->getEmployees($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $employees
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Employees List Error', 'HR', [
                'error' => $e->getMessage(),
                'filters' => $request->only(['search', 'school_id', 'department_id'])
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employees',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created employee
     */
    public function store(EmployeeRequest $request): JsonResponse
    {
        try {
            $employee = $this->hrService->createEmployee($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully',
                'data' => $employee
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create employee',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified employee
     */
    public function show(Employee $employee): JsonResponse
    {
        try {
            $employeeData = $employee->load([
                'user', 
                'department', 
                'position', 
                'manager.user',
                'directReports.user',
                'leaveRequests' => function($query) {
                    $query->latest()->limit(5);
                },
                'payrolls' => function($query) {
                    $query->latest()->limit(3);
                }
            ]);

            ActivityLogger::log('Employee Details Viewed', 'HR', [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_id
            ]);

            return response()->json([
                'success' => true,
                'data' => $employeeData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employee details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified employee
     */
    public function update(EmployeeRequest $request, Employee $employee): JsonResponse
    {
        try {
            $updatedEmployee = $this->hrService->updateEmployee($employee, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully',
                'data' => $updatedEmployee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee): JsonResponse
    {
        try {
            $this->hrService->deleteEmployee($employee);

            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Cannot delete employee'
            ], 400);
        }
    }

    /**
     * Get employee profile with detailed information
     */
    public function profile(Employee $employee): JsonResponse
    {
        try {
            $profile = $employee->load([
                'user',
                'department',
                'position',
                'manager.user',
                'directReports.user',
                'leaveRequests.leaveType',
                'payrolls',
                'attendances' => function($query) {
                    $query->whereMonth('date', now()->month)
                          ->whereYear('date', now()->year);
                }
            ]);

            // Add computed data
            $profile->leave_balance = $employee->getLeaveBalance();
            $profile->years_of_service = $employee->years_of_service;
            $profile->is_manager = $employee->isManager();

            return response()->json([
                'success' => true,
                'data' => $profile
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employee profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update employee status
     */
    public function updateStatus(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'employment_status' => 'required|in:active,inactive,terminated,on_leave,suspended',
            'status_change_reason' => 'nullable|string|max:500',
            'termination_date' => 'nullable|date|required_if:employment_status,terminated'
        ]);

        try {
            $updateData = [
                'employment_status' => $request->employment_status,
                'status_change_reason' => $request->status_change_reason,
                'status_changed_at' => now(),
                'is_active' => $request->employment_status === 'active'
            ];

            if ($request->employment_status === 'terminated') {
                $updateData['termination_date'] = $request->termination_date;
            }

            $updatedEmployee = $this->hrService->updateEmployee($employee, $updateData);

            return response()->json([
                'success' => true,
                'message' => 'Employee status updated successfully',
                'data' => $updatedEmployee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get employee hierarchy
     */
    public function hierarchy(Employee $employee): JsonResponse
    {
        try {
            $hierarchy = [
                'employee' => $employee->load(['user', 'position', 'department']),
                'manager' => $employee->manager?->load(['user', 'position']),
                'direct_reports' => $employee->directReports()->with(['user', 'position'])->get(),
                'peers' => Employee::where('manager_id', $employee->manager_id)
                    ->where('id', '!=', $employee->id)
                    ->with(['user', 'position'])
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $hierarchy
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employee hierarchy',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk import employees
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
            'school_id' => 'required|exists:schools,id'
        ]);

        try {
            // This would handle CSV/Excel import
            // For now, return a placeholder response
            
            ActivityLogger::log('Employee Bulk Import Initiated', 'HR', [
                'school_id' => $request->school_id,
                'file_name' => $request->file('file')->getClientOriginalName()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bulk import initiated successfully',
                'data' => [
                    'total_processed' => 0,
                    'successful' => 0,
                    'failed' => 0,
                    'errors' => []
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk import',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}