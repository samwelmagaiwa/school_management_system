<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\School\Models\School;

class Payroll extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'school_id',
        'payroll_number',
        'pay_period_start',
        'pay_period_end',
        'pay_date',
        'basic_salary',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'hours_worked',
        'overtime_hours',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'pay_date' => 'date',
        'approved_at' => 'datetime',
        'basic_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
    ];

    /**
     * Payroll statuses
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Payment methods
     */
    const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_CASH = 'cash';
    const PAYMENT_CHEQUE = 'cheque';

    /**
     * Get the employee this payroll belongs to
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the school this payroll belongs to
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Get all payroll items (earnings and deductions)
     */
    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * Get earnings items
     */
    public function earnings()
    {
        return $this->items()->where('type', PayrollItem::TYPE_EARNING);
    }

    /**
     * Get deduction items
     */
    public function deductions()
    {
        return $this->items()->where('type', PayrollItem::TYPE_DEDUCTION);
    }

    /**
     * Calculate total earnings
     */
    public function getTotalEarningsAttribute(): float
    {
        return $this->earnings()->sum('amount');
    }

    /**
     * Calculate total deductions
     */
    public function getTotalDeductionsAttribute(): float
    {
        return $this->deductions()->sum('amount');
    }

    /**
     * Check if payroll is editable
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL]);
    }

    /**
     * Check if payroll is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if payroll is paid
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Generate payroll number
     */
    public static function generatePayrollNumber(int $schoolId, string $payPeriodStart): string
    {
        $year = date('Y', strtotime($payPeriodStart));
        $month = date('m', strtotime($payPeriodStart));
        
        $lastPayroll = self::where('school_id', $schoolId)
            ->whereYear('pay_period_start', $year)
            ->whereMonth('pay_period_start', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastPayroll ? (int)substr($lastPayroll->payroll_number, -4) + 1 : 1;
        
        return "PAY{$year}{$month}" . str_pad($schoolId, 3, '0', STR_PAD_LEFT) . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by pay period
     */
    public function scopeByPayPeriod($query, $startDate, $endDate)
    {
        return $query->where('pay_period_start', '>=', $startDate)
                    ->where('pay_period_end', '<=', $endDate);
    }
}

class PayrollItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payroll_id',
        'type',
        'name',
        'code',
        'amount',
        'quantity',
        'rate',
        'is_taxable',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'is_taxable' => 'boolean',
    ];

    /**
     * Item types
     */
    const TYPE_EARNING = 'earning';
    const TYPE_DEDUCTION = 'deduction';

    /**
     * Common earning codes
     */
    const EARNING_BASIC_SALARY = 'basic_salary';
    const EARNING_OVERTIME = 'overtime';
    const EARNING_BONUS = 'bonus';
    const EARNING_ALLOWANCE = 'allowance';
    const EARNING_COMMISSION = 'commission';

    /**
     * Common deduction codes
     */
    const DEDUCTION_TAX = 'income_tax';
    const DEDUCTION_INSURANCE = 'insurance';
    const DEDUCTION_PENSION = 'pension';
    const DEDUCTION_LOAN = 'loan';
    const DEDUCTION_ADVANCE = 'advance';

    /**
     * Get the payroll this item belongs to
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Check if item is an earning
     */
    public function isEarning(): bool
    {
        return $this->type === self::TYPE_EARNING;
    }

    /**
     * Check if item is a deduction
     */
    public function isDeduction(): bool
    {
        return $this->type === self::TYPE_DEDUCTION;
    }

    /**
     * Scope to get earnings
     */
    public function scopeEarnings($query)
    {
        return $query->where('type', self::TYPE_EARNING);
    }

    /**
     * Scope to get deductions
     */
    public function scopeDeductions($query)
    {
        return $query->where('type', self::TYPE_DEDUCTION);
    }
}