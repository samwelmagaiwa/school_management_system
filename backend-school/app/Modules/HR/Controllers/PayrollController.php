<?php

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\Payroll;
use App\Modules\HR\Models\PayrollItem;
use App\Modules\HR\Requests\PayrollRequest;
use App\Modules\HR\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Display a listing of payrolls
     */
    public function index(Request $request): JsonResponse
    {
        $payrolls = Payroll::with(['employee.user', 'school'])
            ->when($request->search, function ($query, $search) {
                return $query->whereHas('employee.user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhere('payroll_number', 'like', "%{$search}%");
            })
            ->when($request->employee_id, function ($query, $employeeId) {
                return $query->where('employee_id', $employeeId);
            })
            ->when($request->pay_period_start, function ($query, $start) {
                return $query->where('pay_period_start', '>=', $start);
            })
            ->when($request->pay_period_end, function ($query, $end) {
                return $query->where('pay_period_end', '<=', $end);
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('pay_period_start', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($payrolls);
    }

    /**
     * Store a newly created payroll
     */
    public function store(PayrollRequest $request): JsonResponse
    {
        $payroll = $this->payrollService->createPayroll($request->validated());

        return response()->json([
            'message' => 'Payroll created successfully',
            'payroll' => $payroll->load(['employee.user', 'items'])
        ], 201);
    }

    /**
     * Display the specified payroll
     */
    public function show(Payroll $payroll): JsonResponse
    {
        return response()->json([
            'payroll' => $payroll->load([
                'employee.user', 'employee.position', 'employee.department',
                'items', 'school'
            ])
        ]);
    }

    /**
     * Update the specified payroll
     */
    public function update(PayrollRequest $request, Payroll $payroll): JsonResponse
    {
        // Only allow updates if status is draft
        if ($payroll->status !== 'draft') {
            return response()->json([
                'message' => 'Cannot update payroll that is not in draft status'
            ], 422);
        }

        $payroll = $this->payrollService->updatePayroll($payroll, $request->validated());

        return response()->json([
            'message' => 'Payroll updated successfully',
            'payroll' => $payroll->load(['employee.user', 'items'])
        ]);
    }

    /**
     * Remove the specified payroll
     */
    public function destroy(Payroll $payroll): JsonResponse
    {
        // Only allow deletion if status is draft
        if ($payroll->status !== 'draft') {
            return response()->json([
                'message' => 'Cannot delete payroll that is not in draft status'
            ], 422);
        }

        $payroll->delete();

        return response()->json([
            'message' => 'Payroll deleted successfully'
        ]);
    }

    /**
     * Generate bulk payroll for a period
     */
    public function generateBulk(Request $request): JsonResponse
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date|after:pay_period_start',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $results = $this->payrollService->generateBulkPayroll($request->all());

        return response()->json([
            'message' => 'Bulk payroll generation completed',
            'results' => $results
        ]);
    }

    /**
     * Approve payroll
     */
    public function approve(Request $request, Payroll $payroll): JsonResponse
    {
        $request->validate([
            'approved_by' => 'required|exists:employees,id',
            'approval_notes' => 'nullable|string|max:500',
        ]);

        $result = $this->payrollService->approvePayroll(
            $payroll,
            $request->approved_by,
            $request->approval_notes
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'message' => 'Payroll approved successfully',
            'payroll' => $payroll->fresh()
        ]);
    }

    /**
     * Process payroll payment
     */
    public function processPayment(Payroll $payroll): JsonResponse
    {
        $result = $this->payrollService->processPayment($payroll);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'message' => 'Payroll payment processed successfully',
            'payroll' => $payroll->fresh()
        ]);
    }

    /**
     * Generate payslip PDF
     */
    public function generatePayslip(Payroll $payroll)
    {
        return $this->payrollService->generatePayslipPDF($payroll);
    }

    /**
     * Get payroll summary for a period
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $summary = $this->payrollService->getPayrollSummary($request->all());

        return response()->json([
            'period' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'summary' => $summary
        ]);
    }

    /**
     * Get employee payroll history
     */
    public function employeeHistory(Request $request, $employeeId): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $history = $this->payrollService->getEmployeePayrollHistory(
            $employeeId,
            $request->year,
            $request->limit ?? 12
        );

        return response()->json([
            'employee_id' => $employeeId,
            'year' => $request->year ?? date('Y'),
            'history' => $history
        ]);
    }
}