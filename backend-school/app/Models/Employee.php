<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models$1;
use App\Models$1;
use App\Models$1;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'school_id',
        'employee_id',
        'department_id',
        'position_id',
        'manager_id',
        'hire_date',
        'termination_date',
        'employment_type',
        'employment_status',
        'work_schedule',
        'salary',
        'hourly_rate',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'date_of_birth',
        'gender',
        'marital_status',
        'national_id',
        'tax_id',
        'bank_account_number',
        'bank_name',
        'qualifications',
        'certifications',
        'skills',
        'notes',
        'status_change_reason',
        'status_changed_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'date_of_birth' => 'date',
        'status_changed_at' => 'datetime',
        'salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'qualifications' => 'array',
        'certifications' => 'array',
        'skills' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Employment types
     */
    const TYPE_FULL_TIME = 'full_time';
    const TYPE_PART_TIME = 'part_time';
    const TYPE_CONTRACT = 'contract';
    const TYPE_TEMPORARY = 'temporary';
    const TYPE_INTERN = 'intern';

    /**
     * Employment statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_ON_LEAVE = 'on_leave';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Get the user associated with the employee
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the school that the employee belongs to
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the department that the employee belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position of the employee
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the manager of the employee
     */
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get the direct reports of the employee
     */
    public function directReports()
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Get all attendance records for the employee
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get all leave requests for the employee
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get all payrolls for the employee
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Get all performance reviews for the employee
     */
    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class);
    }

    /**
     * Get all training records for the employee
     */
    public function trainings()
    {
        return $this->belongsToMany(Training::class, 'employee_trainings')
                    ->withPivot(['completion_date', 'score', 'status'])
                    ->withTimestamps();
    }

    /**
     * Get employee's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->user ? $this->user->name : '';
    }

    /**
     * Get employee's email
     */
    public function getEmailAttribute(): string
    {
        return $this->user ? $this->user->email : '';
    }

    /**
     * Get employee's age
     */
    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : 0;
    }

    /**
     * Get years of service
     */
    public function getYearsOfServiceAttribute(): float
    {
        if (!$this->hire_date) return 0;
        
        $endDate = $this->termination_date ?? now();
        return $this->hire_date->diffInYears($endDate);
    }

    /**
     * Check if employee is active
     */
    public function isActive(): bool
    {
        return $this->employment_status === self::STATUS_ACTIVE && $this->is_active;
    }

    /**
     * Check if employee is manager
     */
    public function isManager(): bool
    {
        return $this->directReports()->count() > 0;
    }

    /**
     * Get current leave balance
     */
    public function getLeaveBalance(int $year = null): array
    {
        $year = $year ?? date('Y');
        
        // This would calculate leave balance based on leave policies
        // For now, return a basic structure
        return [
            'annual_leave' => 20,
            'sick_leave' => 10,
            'personal_leave' => 5,
            'used_annual' => 0,
            'used_sick' => 0,
            'used_personal' => 0,
        ];
    }

    /**
     * Scope to get only active employees
     */
    public function scopeActive($query)
    {
        return $query->where('employment_status', self::STATUS_ACTIVE)
                    ->where('is_active', true);
    }

    /**
     * Scope to filter by employment type
     */
    public function scopeByEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    /**
     * Scope to filter by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to get managers
     */
    public function scopeManagers($query)
    {
        return $query->whereHas('directReports');
    }
}