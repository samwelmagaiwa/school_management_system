<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'department_id',
        'title',
        'code',
        'description',
        'level',
        'min_salary',
        'max_salary',
        'required_qualifications',
        'required_skills',
        'responsibilities',
        'reports_to_position_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'required_qualifications' => 'array',
        'required_skills' => 'array',
        'responsibilities' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Position levels
     */
    const LEVEL_ENTRY = 'entry';
    const LEVEL_JUNIOR = 'junior';
    const LEVEL_SENIOR = 'senior';
    const LEVEL_LEAD = 'lead';
    const LEVEL_MANAGER = 'manager';
    const LEVEL_DIRECTOR = 'director';
    const LEVEL_EXECUTIVE = 'executive';

    /**
     * Get the department that the position belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position this position reports to
     */
    public function reportsTo()
    {
        return $this->belongsTo(Position::class, 'reports_to_position_id');
    }

    /**
     * Get the positions that report to this position
     */
    public function subordinates()
    {
        return $this->hasMany(Position::class, 'reports_to_position_id');
    }

    /**
     * Get all employees in this position
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the full position title with department
     */
    public function getFullTitleAttribute(): string
    {
        return $this->title . ' - ' . $this->department->name;
    }

    /**
     * Check if position is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope to get only active positions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to filter by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}