<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models$1;
use App\Models$1;
use App\Models$1;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'school_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'exam_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'total_marks',
        'passing_marks',
        'instructions',
        'status',
        'room_number',
        'is_published'
    ];

    protected $casts = [
        'exam_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'total_marks' => 'integer',
        'passing_marks' => 'integer',
        'duration_minutes' => 'integer',
        'status' => 'boolean',
        'is_published' => 'boolean',
    ];

    protected $dates = ['deleted_at'];

    // Exam types
    const TYPES = [
        'Unit Test',
        'Mid Term',
        'Final Term',
        'Annual',
        'Practical',
        'Oral',
        'Assignment',
        'Project'
    ];

    // Exam statuses
    const STATUSES = [
        'Scheduled',
        'In Progress',
        'Completed',
        'Cancelled',
        'Postponed'
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function class()
    {
        return $this->belongsTo(\App\Modules\Class\Models\SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function results()
    {
        return $this->hasMany(ExamResult::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('exam_date', '>=', now()->toDateString());
    }

    public function scopePast($query)
    {
        return $query->where('exam_date', '<', now()->toDateString());
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('room_number', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getIsUpcomingAttribute()
    {
        return $this->exam_date >= now()->toDateString();
    }

    public function getIsPastAttribute()
    {
        return $this->exam_date < now()->toDateString();
    }

    public function getIsInProgressAttribute()
    {
        $now = now();
        return $this->exam_date == $now->toDateString() &&
               $this->start_time <= $now &&
               $this->end_time >= $now;
    }

    public function getDurationFormattedAttribute()
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        return $minutes . 'm';
    }
}