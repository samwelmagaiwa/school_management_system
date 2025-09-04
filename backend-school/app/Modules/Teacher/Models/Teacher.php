<?php

namespace App\Modules\Teacher\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Auth\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Subject\Models\Subject;
use App\Modules\Class\Models\SchoolClass;
use App\Modules\Attendance\Models\Attendance;

class Teacher extends Model
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
        'date_of_birth',
        'gender',
        'phone',
        'address',
        'qualification',
        'specialization',
        'experience_years',
        'joining_date',
        'salary',
        'emergency_contact',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'salary' => 'decimal:2',
        'experience_years' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user associated with the teacher
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the school that the teacher belongs to
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the subjects that the teacher teaches
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects');
    }

    /**
     * Get the classes that the teacher teaches
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'class_teacher_id');
    }

    /**
     * Get all attendance records for the teacher
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get total number of students taught by this teacher
     */
    public function getTotalStudents(): int
    {
        return $this->classes()->withCount('students')->get()->sum('students_count');
    }

    /**
     * Calculate teacher's attendance rate
     */
    public function getAttendanceRate(): float
    {
        $totalDays = $this->attendances()->count();
        if ($totalDays === 0) return 0;

        $presentDays = $this->attendances()->where('status', 'present')->count();
        return round(($presentDays / $totalDays) * 100, 2);
    }

    /**
     * Get teacher's age
     */
    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : 0;
    }

    /**
     * Get teacher's full name from user relationship
     */
    public function getNameAttribute(): string
    {
        return $this->user ? $this->user->name : '';
    }

    /**
     * Get teacher's email from user relationship
     */
    public function getEmailAttribute(): string
    {
        return $this->user ? $this->user->email : '';
    }

    /**
     * Scope to get only active teachers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by specialization
     */
    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', $specialization);
    }
}