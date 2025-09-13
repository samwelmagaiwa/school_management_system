<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'grade',
        'section',
        'capacity',
        'class_teacher_id',
        'room_number',
        'description',
        'academic_year',
        'status'
    ];

    protected $casts = [
        'grade' => 'integer',
        'capacity' => 'integer',
        'status' => 'boolean'
    ];

    protected $dates = ['deleted_at'];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function classTeacher()
    {
        return $this->belongsTo(Teacher::class, 'class_teacher_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByGrade($query, $grade)
    {
        return $query->where('grade', $grade);
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('grade', 'like', "%{$search}%")
              ->orWhere('section', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "Grade {$this->grade} - Section {$this->section}";
    }

    public function getCurrentStudentsCountAttribute()
    {
        return $this->students()->where('status', true)->count();
    }

    public function getAvailableSeatsAttribute()
    {
        return max(0, $this->capacity - $this->current_students_count);
    }
}