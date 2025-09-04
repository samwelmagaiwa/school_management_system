<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status',
        'approver_id',
        'approved_at',
        'approver_comments',
        'emergency_contact',
        'handover_notes',
        'is_half_day',
        'half_day_period',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'days_requested' => 'decimal:1',
        'is_half_day' => 'boolean',
    ];

    /**
     * Leave request statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Half day periods
     */
    const HALF_DAY_MORNING = 'morning';
    const HALF_DAY_AFTERNOON = 'afternoon';

    /**
     * Get the employee who made the leave request
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the leave type
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    /**
     * Get leave request documents
     */
    public function documents()
    {
        return $this->hasMany(LeaveDocument::class);
    }

    /**
     * Calculate number of working days
     */
    public function getWorkingDaysAttribute(): float
    {
        if ($this->is_half_day) {
            return 0.5;
        }

        // Calculate working days between start and end date
        // This is a simplified calculation - you might want to consider holidays
        $start = $this->start_date;
        $end = $this->end_date;
        $workingDays = 0;

        while ($start <= $end) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($start->dayOfWeek !== 0 && $start->dayOfWeek !== 6) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays;
    }

    /**
     * Check if leave request is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if leave request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if leave request can be modified
     */
    public function canBeModified(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Scope to get pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }
}

class LeaveType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'days_per_year',
        'max_consecutive_days',
        'requires_approval',
        'requires_documentation',
        'is_paid',
        'carry_forward_allowed',
        'max_carry_forward_days',
        'color',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'days_per_year' => 'integer',
        'max_consecutive_days' => 'integer',
        'max_carry_forward_days' => 'integer',
        'requires_approval' => 'boolean',
        'requires_documentation' => 'boolean',
        'is_paid' => 'boolean',
        'carry_forward_allowed' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the school that the leave type belongs to
     */
    public function school()
    {
        return $this->belongsTo(\App\Modules\School\Models\School::class);
    }

    /**
     * Get all leave requests of this type
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Scope to get only active leave types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

class LeaveDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'leave_request_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    /**
     * Get the leave request this document belongs to
     */
    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * Get the user who uploaded the document
     */
    public function uploader()
    {
        return $this->belongsTo(Employee::class, 'uploaded_by');
    }

    /**
     * Get the file URL
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}