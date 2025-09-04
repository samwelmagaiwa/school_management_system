<?php

namespace App\Modules\Subject\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\School\Models\School;
use App\Modules\User\Models\User;
use App\Modules\Exam\Models\Exam;
use App\Modules\Fee\Models\Fee;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'school_id',
        'class_id',
        'teacher_id',
        'credits',
        'type',
        'status',
        'syllabus',
        'books_required',
        'assessment_criteria'
    ];

    protected $casts = [
        'status' => 'boolean',
        'books_required' => 'array',
        'assessment_criteria' => 'array',
    ];

    protected $dates = ['deleted_at'];

    // Subject types
    const TYPES = [
        'Core',
        'Elective',
        'Optional',
        'Extra-curricular'
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(\App\Modules\Class\Models\SchoolClass::class, 'class_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
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

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->code . ' - ' . $this->name;
    }
}