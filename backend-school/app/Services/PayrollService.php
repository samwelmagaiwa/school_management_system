<?php

namespace App\Services;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Salary;

use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new payroll
     */
    public function createPayroll(array $data): Payroll
    {
        DB::beginTransaction();
        
        try {
            // Generate payroll number
            $data['payroll_number'] = Payroll::generatePayrollNumber(
                $data['school_id'], 
                $data['pay_period_start']
            );

            // Set default status
            $data['status'] = Payroll::STATUS_DRAFT;

            $payroll = Payroll::create($data);

            // Create payroll items if provided
            if (isset($data['items'])) {
                $this->createPayrollItems($payroll, $data['items']);
            }

            DB::commit();
            return $payroll;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update a payroll
     */
    public function updatePayroll(Payroll $payroll, array $data): Payroll
    {
        DB::beginTransaction();
        
        try {
            $payroll->update($data);

            // Update payroll items if provided
            if (isset($data['items'])) {
                // Delete existing items and create new ones
                $payroll->items()->delete();
                $this->createPayrollItems($payroll, $data['items']);
            }

            DB::commit();
            return $payroll;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Generate bulk payroll for multiple employees
     */
    public function generateBulkPayroll(array $data): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => 0,
        ];

        $employees = $this->getEmployeesForBulkPayroll($data);
        $results['total'] = $employees->count();

        foreach ($employees as $employee) {
            try {
                $payrollData = $this->generateEmployeePayrollData($employee, $data);
                $payroll = $this->createPayroll($payrollData);
                
                $results['success'][] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'payroll_id' => $payroll->id,
                    'payroll_number' => $payroll->payroll_number,
                ];

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Approve a payroll
     */
    public function approvePayroll(Payroll $payroll, int $approverId, string $notes = null): array
    {
        if ($payroll->status !== Payroll::STATUS_PENDING_APPROVAL) {
            return [
                'success' => false,
                'message' => 'Payroll is not in pending approval status.'
            ];
        }

        DB::beginTransaction();
        
        try {
            $payroll->update([
                'status' => Payroll::STATUS_APPROVED,
                'approved_by' => $approverId,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            // Send approval notification
            $this->notificationService->sendPayrollApprovalNotification($payroll);

            DB::commit();
            return ['success' => true, 'message' => 'Payroll approved successfully.'];

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Process payroll payment
     */
    public function processPayment(Payroll $payroll): array
    {
        if ($payroll->status !== Payroll::STATUS_APPROVED) {
            return [
                'success' => false,
                'message' => 'Payroll must be approved before processing payment.'
            ];
        }

        DB::beginTransaction();
        
        try {
            $payroll->update([
                'status' => Payroll::STATUS_PAID,
                'pay_date' => now(),
                'payment_reference' => $this->generatePaymentReference($payroll),
            ]);

            // Send payment notification
            $this->notificationService->sendPayrollPaymentNotification($payroll);

            DB::commit();
            return ['success' => true, 'message' => 'Payment processed successfully.'];

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Generate payslip PDF
     */
    public function generatePayslipPDF(Payroll $payroll)
    {
        // This would integrate with a PDF generation library
        $fileName = "payslip_{$payroll->payroll_number}.pdf";
        
        // Generate PDF content
        $pdfContent = $this->generatePayslipContent($payroll);
        
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
    }

    /**
     * Get payroll summary for a period
     */
    public function getPayrollSummary(array $filters): array
    {
        $query = Payroll::where('school_id', $filters['school_id'])
            ->whereBetween('pay_period_start', [$filters['start_date'], $filters['end_date']]);

        if (isset($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        $payrolls = $query->get();

        return [
            'total_payrolls' => $payrolls->count(),
            'total_gross_salary' => $payrolls->sum('gross_salary'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_net_salary' => $payrolls->sum('net_salary'),
            'by_status' => $payrolls->groupBy('status')->map->count(),
            'by_department' => $this->getPayrollByDepartment($payrolls),
            'by_payment_method' => $payrolls->groupBy('payment_method')->map->count(),
            'average_salary' => $payrolls->avg('net_salary'),
        ];
    }

    /**
     * Get employee payroll history
     */
    public function getEmployeePayrollHistory(int $employeeId, int $year = null, int $limit = 12): array
    {
        $query = Payroll::where('employee_id', $employeeId)
            ->with(['items', 'approver.user'])
            ->orderBy('pay_period_start', 'desc');

        if ($year) {
            $query->whereYear('pay_period_start', $year);
        }

        $payrolls = $query->limit($limit)->get();

        return [
            'payrolls' => $payrolls->map(function ($payroll) {
                return [
                    'id' => $payroll->id,
                    'payroll_number' => $payroll->payroll_number,
                    'pay_period' => $payroll->pay_period_start->format('M Y'),
                    'gross_salary' => $payroll->gross_salary,
                    'total_deductions' => $payroll->total_deductions,
                    'net_salary' => $payroll->net_salary,
                    'status' => $payroll->status,
                    'pay_date' => $payroll->pay_date?->format('Y-m-d'),
                ];
            }),
            'summary' => [
                'total_gross' => $payrolls->sum('gross_salary'),
                'total_net' => $payrolls->sum('net_salary'),
                'average_gross' => $payrolls->avg('gross_salary'),
                'average_net' => $payrolls->avg('net_salary'),
            ],
        ];
    }

    /**
     * Calculate payroll for an employee
     */
    public function calculatePayroll(Employee $employee, Carbon $startDate, Carbon $endDate): array
    {
        $basicSalary = $employee->salary ?? 0;
        $hourlyRate = $employee->hourly_rate ?? 0;
        
        // Calculate based on employment type
        if ($employee->employment_type === Employee::TYPE_FULL_TIME) {
            $grossSalary = $basicSalary;
        } else {
            // For part-time, calculate based on hours worked
            $hoursWorked = $this->getHoursWorked($employee, $startDate, $endDate);
            $grossSalary = $hoursWorked * $hourlyRate;
        }

        // Calculate overtime
        $overtimeHours = $this->getOvertimeHours($employee, $startDate, $endDate);
        $overtimePay = $overtimeHours * ($hourlyRate * 1.5); // 1.5x rate for overtime

        // Calculate deductions
        $deductions = $this->calculateDeductions($employee, $grossSalary + $overtimePay);

        return [
            'basic_salary' => $basicSalary,
            'overtime_pay' => $overtimePay,
            'gross_salary' => $grossSalary + $overtimePay,
            'total_deductions' => array_sum($deductions),
            'net_salary' => ($grossSalary + $overtimePay) - array_sum($deductions),
            'deductions_breakdown' => $deductions,
        ];
    }

    /**
     * Create payroll items
     */
    private function createPayrollItems(Payroll $payroll, array $items): void
    {
        foreach ($items as $item) {
            PayrollItem::create(array_merge($item, [
                'payroll_id' => $payroll->id
            ]));
        }

        // Recalculate totals
        $this->recalculatePayrollTotals($payroll);
    }

    /**
     * Get employees for bulk payroll generation
     */
    private function getEmployeesForBulkPayroll(array $data): \Illuminate\Database\Eloquent\Collection
    {
        $query = Employee::where('school_id', $data['school_id'])
            ->where('employment_status', Employee::STATUS_ACTIVE);

        if (isset($data['department_ids'])) {
            $query->whereIn('department_id', $data['department_ids']);
        }

        if (isset($data['employee_ids'])) {
            $query->whereIn('id', $data['employee_ids']);
        }

        return $query->get();
    }

    /**
     * Generate payroll data for an employee
     */
    private function generateEmployeePayrollData(Employee $employee, array $baseData): array
    {
        $startDate = Carbon::parse($baseData['pay_period_start']);
        $endDate = Carbon::parse($baseData['pay_period_end']);
        
        $calculation = $this->calculatePayroll($employee, $startDate, $endDate);

        return [
            'employee_id' => $employee->id,
            'school_id' => $baseData['school_id'],
            'pay_period_start' => $baseData['pay_period_start'],
            'pay_period_end' => $baseData['pay_period_end'],
            'basic_salary' => $calculation['basic_salary'],
            'gross_salary' => $calculation['gross_salary'],
            'total_deductions' => $calculation['total_deductions'],
            'net_salary' => $calculation['net_salary'],
            'hours_worked' => $this->getHoursWorked($employee, $startDate, $endDate),
            'overtime_hours' => $this->getOvertimeHours($employee, $startDate, $endDate),
            'payment_method' => Payroll::PAYMENT_BANK_TRANSFER,
        ];
    }

    /**
     * Generate payment reference
     */
    private function generatePaymentReference(Payroll $payroll): string
    {
        return 'PAY-' . $payroll->payroll_number . '-' . now()->format('YmdHis');
    }

    /**
     * Generate payslip content
     */
    private function generatePayslipContent(Payroll $payroll): string
    {
        // This would generate actual PDF content
        return "Payslip content for {$payroll->payroll_number}";
    }

    /**
     * Get payroll breakdown by department
     */
    private function getPayrollByDepartment($payrolls): array
    {
        return $payrolls->load('employee.department')
            ->groupBy('employee.department.name')
            ->map(function ($deptPayrolls) {
                return [
                    'count' => $deptPayrolls->count(),
                    'total_gross' => $deptPayrolls->sum('gross_salary'),
                    'total_net' => $deptPayrolls->sum('net_salary'),
                ];
            })
            ->toArray();
    }

    /**
     * Get hours worked for an employee in a period
     */
    private function getHoursWorked(Employee $employee, Carbon $startDate, Carbon $endDate): float
    {
        // This would integrate with attendance system
        // For now, return a default value
        return 160; // Standard monthly hours
    }

    /**
     * Get overtime hours for an employee in a period
     */
    private function getOvertimeHours(Employee $employee, Carbon $startDate, Carbon $endDate): float
    {
        // This would calculate overtime from attendance records
        return 0;
    }

    /**
     * Calculate deductions for an employee
     */
    private function calculateDeductions(Employee $employee, float $grossSalary): array
    {
        $deductions = [];

        // Income tax (simplified calculation)
        if ($grossSalary > 5000) {
            $deductions['income_tax'] = $grossSalary * 0.15;
        }

        // Social security
        $deductions['social_security'] = $grossSalary * 0.05;

        // Health insurance
        $deductions['health_insurance'] = 200;

        return $deductions;
    }

    /**
     * Recalculate payroll totals based on items
     */
    private function recalculatePayrollTotals(Payroll $payroll): void
    {
        $earnings = $payroll->earnings()->sum('amount');
        $deductions = $payroll->deductions()->sum('amount');

        $payroll->update([
            'gross_salary' => $payroll->basic_salary + $earnings,
            'total_deductions' => $deductions,
            'net_salary' => ($payroll->basic_salary + $earnings) - $deductions,
        ]);
    }
}