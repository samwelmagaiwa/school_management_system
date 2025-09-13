<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models$1;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

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
        return $this->belongsTo(School::class);
    }

    /**
     * Get all leave requests of this type
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Check if leave type is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope to get only active leave types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by school
     */
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}