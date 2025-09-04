<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\School\Models\School;

class Department extends Model
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
        'head_id',
        'budget',
        'location',
        'phone',
        'email',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budget' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the school that the department belongs to
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the head of the department
     */
    public function head()
    {
        return $this->belongsTo(Employee::class, 'head_id');
    }

    /**
     * Get all employees in the department
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all positions in the department
     */
    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Get active employees count
     */
    public function getActiveEmployeesCountAttribute(): int
    {
        return $this->employees()->active()->count();
    }

    /**
     * Get total budget allocated to employees
     */
    public function getTotalSalaryBudgetAttribute(): float
    {
        return $this->employees()->active()->sum('salary');
    }

    /**
     * Scope to get only active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

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
     * Get positions that report to this position
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
     * Get current employee count
     */
    public function getCurrentEmployeeCountAttribute(): int
    {
        return $this->employees()->active()->count();
    }

    /**
     * Check if position is managerial
     */
    public function isManagerial(): bool
    {
        return in_array($this->level, [
            self::LEVEL_MANAGER,
            self::LEVEL_DIRECTOR,
            self::LEVEL_EXECUTIVE
        ]);
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
}