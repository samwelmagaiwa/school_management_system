<?php

namespace App\Modules\Fee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Subject\Models\Subject;

class Fee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'type',
        'frequency',
        'school_id',
        'class_id',
        'subject_id',
        'student_id',
        'due_date',
        'paid_date',
        'payment_method',
        'transaction_id',
        'status',
        'late_fee',
        'discount',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    protected $dates = ['deleted_at'];

    // Fee types
    const TYPES = [
        'Tuition',
        'Admission',
        'Examination',
        'Library',
        'Laboratory',
        'Sports',
        'Transport',
        'Hostel',
        'Miscellaneous'
    ];

    // Fee frequencies
    const FREQUENCIES = [
        'One-time',
        'Monthly',
        'Quarterly',
        'Half-yearly',
        'Yearly'
    ];

    // Fee statuses
    const STATUSES = [
        'Pending',
        'Paid',
        'Overdue',
        'Cancelled',
        'Refunded'
    ];

    // Payment methods
    const PAYMENT_METHODS = [
        'Cash',
        'Bank Transfer',
        'Credit Card',
        'Debit Card',
        'Online Payment',
        'Cheque'
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(\App\Modules\Class\Models\SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'Paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'Overdue')
                    ->orWhere(function ($q) {
                        $q->where('status', 'Pending')
                          ->where('due_date', '<', now());
                    });
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('transaction_id', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getNetAmountAttribute()
    {
        return $this->amount + $this->late_fee - $this->discount;
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'Pending' && $this->due_date < now();
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return now()->diffInDays($this->due_date);
    }
}