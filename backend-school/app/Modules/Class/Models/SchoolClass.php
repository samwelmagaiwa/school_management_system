<?php

namespace App\Modules\Class\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\School\Models\School;
use App\Modules\Teacher\Models\Teacher;
use App\Modules\Student\Models\Student;
use App\Modules\Subject\Models\Subject;

class SchoolClass extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'classes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'name',
        'section',
        'class_code',
        'grade_level',
        'grade', // For backward compatibility
        'class_teacher_id',
        'academic_year_id',
        'academic_year', // For backward compatibility
        'capacity',
        'current_strength',
        'room_number',
        'building',
        'floor',
        'start_time',
        'end_time',
        'working_days',
        'stream',
        'description',
        'subjects',
        'is_active',
        'start_date',
        'end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'integer',
        'current_strength' => 'integer',
        'grade_level' => 'integer',
        'is_active' => 'boolean',
        'working_days' => 'array',
        'subjects' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the school that the class belongs to
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the class teacher
     */
    public function classTeacher()
    {
        return $this->belongsTo(Teacher::class, 'class_teacher_id');
    }

    /**
     * Get all students in this class
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Get all subjects taught in this class
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects');
    }

    /**
     * Get the timetable for this class
     */
    public function timetable()
    {
        return $this->hasMany(Timetable::class, 'class_id');
    }

    /**
     * Get average attendance for the class
     */
    public function getAverageAttendance(): float
    {
        $students = $this->students;
        if ($students->isEmpty()) return 0;

        $totalAttendance = $students->sum(function ($student) {
            return $student->getAttendancePercentage();
        });

        return round($totalAttendance / $students->count(), 2);
    }

    /**
     * Get class full name (name + section)
     */
    public function getFullNameAttribute(): string
    {
        return $this->name . ($this->section ? ' - ' . $this->section : '');
    }

    /**
     * Get grade attribute (backward compatibility)
     */
    public function getGradeAttribute(): string
    {
        return $this->attributes['grade'] ?? $this->grade_level ?? '';
    }

    /**
     * Set grade attribute (backward compatibility)
     */
    public function setGradeAttribute($value): void
    {
        $this->attributes['grade'] = $value;
        $this->attributes['grade_level'] = is_numeric($value) ? (int)$value : null;
    }

    /**
     * Get academic year attribute (backward compatibility)
     */
    public function getAcademicYearAttribute(): string
    {
        return $this->attributes['academic_year'] ?? $this->academicYear?->name ?? '';
    }

    /**
     * Set academic year attribute (backward compatibility)
     */
    public function setAcademicYearAttribute($value): void
    {
        $this->attributes['academic_year'] = $value;
    }

    /**
     * Get current student count
     */
    public function getCurrentStrengthAttribute(): int
    {
        return $this->attributes['current_strength'] ?? $this->students()->where('is_active', true)->count();
    }

    /**
     * Check if class is at capacity
     */
    public function isAtCapacity(): bool
    {
        return $this->current_strength >= $this->capacity;
    }

    /**
     * Scope to get only active classes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by grade
     */
    public function scopeByGrade($query, $grade)
    {
        return $query->where('grade', $grade)->orWhere('grade_level', $grade);
    }

    /**
     * Scope to filter by academic year
     */
    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year)->orWhere('academic_year_id', $year);
    }

    /**
     * Get academic year relationship
     */
    public function academicYear()
    {
        return $this->belongsTo(\App\Modules\Academic\Models\AcademicYear::class, 'academic_year_id');
    }

    /**
     * Get class code
     */
    public function getClassCodeAttribute(): string
    {
        return $this->attributes['class_code'] ?? $this->generateClassCode();
    }

    /**
     * Generate class code
     */
    private function generateClassCode(): string
    {
        $code = $this->grade ?? $this->grade_level ?? '';
        if ($this->section) {
            $code .= strtoupper($this->section);
        }
        return $code;
    }
}

/**
 * Timetable model for class schedules
 */
class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room_number',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}