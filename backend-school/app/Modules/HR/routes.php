<?php

use Illuminate\Support\Facades\Route;
use App\Modules\HR\Controllers\EmployeeController;
use App\Modules\HR\Controllers\DepartmentController;
use App\Modules\HR\Controllers\LeaveController;
use App\Modules\HR\Controllers\PayrollController;

/*
|--------------------------------------------------------------------------
| HR Module Routes
|--------------------------------------------------------------------------
|
| Here are the routes for Human Resources management functionality including
| employee management, departments, leave management, and payroll.
|
*/

Route::middleware('auth:sanctum')->prefix('hr')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Employee Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/{employee}', [EmployeeController::class, 'show']);
        Route::put('/{employee}', [EmployeeController::class, 'update']);
        Route::delete('/{employee}', [EmployeeController::class, 'destroy']);
        Route::get('/{employee}/profile', [EmployeeController::class, 'profile']);
        Route::patch('/{employee}/status', [EmployeeController::class, 'updateStatus']);
        Route::get('/{employee}/hierarchy', [EmployeeController::class, 'hierarchy']);
        Route::post('/bulk-import', [EmployeeController::class, 'bulkImport']);
    });

    /*
    |--------------------------------------------------------------------------
    | Department Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('departments')->group(function () {
        Route::get('/', [DepartmentController::class, 'index']);
        Route::post('/', [DepartmentController::class, 'store']);
        Route::get('/{department}', [DepartmentController::class, 'show']);
        Route::put('/{department}', [DepartmentController::class, 'update']);
        Route::delete('/{department}', [DepartmentController::class, 'destroy']);
        Route::get('/{department}/statistics', [DepartmentController::class, 'statistics']);
        Route::post('/{department}/assign-head', [DepartmentController::class, 'assignHead']);
    });

    /*
    |--------------------------------------------------------------------------
    | Leave Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('leave')->group(function () {
        // Leave Requests
        Route::get('/requests', [LeaveController::class, 'index']);
        Route::post('/requests', [LeaveController::class, 'store']);
        Route::get('/requests/{leaveRequest}', [LeaveController::class, 'show']);
        Route::put('/requests/{leaveRequest}', [LeaveController::class, 'update']);
        Route::delete('/requests/{leaveRequest}', [LeaveController::class, 'destroy']);
        
        // Leave Approvals
        Route::post('/requests/{leaveRequest}/approve', [LeaveController::class, 'approve']);
        Route::post('/requests/{leaveRequest}/reject', [LeaveController::class, 'reject']);
        
        // Leave Information
        Route::get('/balance', [LeaveController::class, 'balance']);
        Route::get('/types', [LeaveController::class, 'leaveTypes']);
        Route::get('/calendar', [LeaveController::class, 'calendar']);
    });

    /*
    |--------------------------------------------------------------------------
    | Payroll Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('payroll')->group(function () {
        Route::get('/', [PayrollController::class, 'index']);
        Route::post('/', [PayrollController::class, 'store']);
        Route::get('/{payroll}', [PayrollController::class, 'show']);
        Route::put('/{payroll}', [PayrollController::class, 'update']);
        Route::delete('/{payroll}', [PayrollController::class, 'destroy']);
        
        // Payroll Processing
        Route::post('/generate-bulk', [PayrollController::class, 'generateBulk']);
        Route::post('/{payroll}/approve', [PayrollController::class, 'approve']);
        Route::post('/{payroll}/process-payment', [PayrollController::class, 'processPayment']);
        Route::get('/{payroll}/payslip', [PayrollController::class, 'generatePayslip']);
        
        // Payroll Reports
        Route::get('/summary/period', [PayrollController::class, 'summary']);
        Route::get('/employee/{employeeId}/history', [PayrollController::class, 'employeeHistory']);
    });

    /*
    |--------------------------------------------------------------------------
    | HR Dashboard & Reports Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', function () {
        $hrService = app(\App\Modules\HR\Services\HRService::class);
        $schoolId = request()->school_id ?? auth()->user()->school_id;
        
        return response()->json([
            'dashboard' => $hrService->getHRDashboard($schoolId)
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Position Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('positions')->group(function () {
        Route::get('/', function () {
            $positions = \App\Modules\HR\Models\Position::with(['department'])
                ->when(request()->department_id, function ($query, $departmentId) {
                    return $query->where('department_id', $departmentId);
                })
                ->when(request()->search, function ($query, $search) {
                    return $query->where('title', 'like', "%{$search}%")
                               ->orWhere('code', 'like', "%{$search}%");
                })
                ->paginate(request()->per_page ?? 15);

            return response()->json($positions);
        });

        Route::post('/', function () {
            request()->validate([
                'department_id' => 'required|exists:departments,id',
                'title' => 'required|string|max:255',
                'code' => 'required|string|max:50',
                'description' => 'nullable|string|max:1000',
                'level' => 'required|in:entry,junior,senior,lead,manager,director,executive',
                'min_salary' => 'nullable|numeric|min:0',
                'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
                'required_qualifications' => 'nullable|array',
                'required_skills' => 'nullable|array',
                'responsibilities' => 'nullable|array',
                'reports_to_position_id' => 'nullable|exists:positions,id',
                'is_active' => 'boolean',
            ]);

            $position = \App\Modules\HR\Models\Position::create(request()->all());

            return response()->json([
                'message' => 'Position created successfully',
                'position' => $position->load('department')
            ], 201);
        });

        Route::get('/{position}', function (\App\Modules\HR\Models\Position $position) {
            return response()->json([
                'position' => $position->load(['department', 'reportsTo', 'subordinates', 'employees.user'])
            ]);
        });

        Route::put('/{position}', function (\App\Modules\HR\Models\Position $position) {
            request()->validate([
                'department_id' => 'required|exists:departments,id',
                'title' => 'required|string|max:255',
                'code' => 'required|string|max:50',
                'description' => 'nullable|string|max:1000',
                'level' => 'required|in:entry,junior,senior,lead,manager,director,executive',
                'min_salary' => 'nullable|numeric|min:0',
                'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
                'required_qualifications' => 'nullable|array',
                'required_skills' => 'nullable|array',
                'responsibilities' => 'nullable|array',
                'reports_to_position_id' => 'nullable|exists:positions,id',
                'is_active' => 'boolean',
            ]);

            $position->update(request()->all());

            return response()->json([
                'message' => 'Position updated successfully',
                'position' => $position->load('department')
            ]);
        });

        Route::delete('/{position}', function (\App\Modules\HR\Models\Position $position) {
            if ($position->employees()->count() > 0) {
                return response()->json([
                    'message' => 'Cannot delete position with active employees'
                ], 422);
            }

            $position->delete();

            return response()->json([
                'message' => 'Position deleted successfully'
            ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Leave Types Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('leave-types')->group(function () {
        Route::get('/', function () {
            $leaveTypes = \App\Modules\HR\Models\LeaveType::where('school_id', request()->school_id)
                ->when(request()->search, function ($query, $search) {
                    return $query->where('name', 'like', "%{$search}%")
                               ->orWhere('code', 'like', "%{$search}%");
                })
                ->paginate(request()->per_page ?? 15);

            return response()->json($leaveTypes);
        });

        Route::post('/', function () {
            request()->validate([
                'school_id' => 'required|exists:schools,id',
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:20',
                'description' => 'nullable|string|max:1000',
                'days_per_year' => 'required|integer|min:0',
                'max_consecutive_days' => 'nullable|integer|min:1',
                'requires_approval' => 'boolean',
                'requires_documentation' => 'boolean',
                'is_paid' => 'boolean',
                'carry_forward_allowed' => 'boolean',
                'max_carry_forward_days' => 'nullable|integer|min:0',
                'color' => 'nullable|string|max:7',
                'is_active' => 'boolean',
            ]);

            $leaveType = \App\Modules\HR\Models\LeaveType::create(request()->all());

            return response()->json([
                'message' => 'Leave type created successfully',
                'leave_type' => $leaveType
            ], 201);
        });

        Route::put('/{leaveType}', function (\App\Modules\HR\Models\LeaveType $leaveType) {
            request()->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:20',
                'description' => 'nullable|string|max:1000',
                'days_per_year' => 'required|integer|min:0',
                'max_consecutive_days' => 'nullable|integer|min:1',
                'requires_approval' => 'boolean',
                'requires_documentation' => 'boolean',
                'is_paid' => 'boolean',
                'carry_forward_allowed' => 'boolean',
                'max_carry_forward_days' => 'nullable|integer|min:0',
                'color' => 'nullable|string|max:7',
                'is_active' => 'boolean',
            ]);

            $leaveType->update(request()->all());

            return response()->json([
                'message' => 'Leave type updated successfully',
                'leave_type' => $leaveType
            ]);
        });

        Route::delete('/{leaveType}', function (\App\Modules\HR\Models\LeaveType $leaveType) {
            $leaveType->delete();

            return response()->json([
                'message' => 'Leave type deleted successfully'
            ]);
        });
    });
});