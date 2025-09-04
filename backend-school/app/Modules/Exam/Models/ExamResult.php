<?php

namespace App\Modules\Exam\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Student\Models\Student;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'marks_obtained',
        'grade',
        'percentage',
        'status',
        'remarks',
        'is_absent'
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'percentage' => 'decimal:2',
        'is_absent' => 'boolean',
    ];

    // Grade constants
    const GRADES = [
        'A+' => ['min' => 90, 'max' => 100],
        'A' => ['min' => 80, 'max' => 89],
        'B+' => ['min' => 70, 'max' => 79],
        'B' => ['min' => 60, 'max' => 69],
        'C+' => ['min' => 50, 'max' => 59],
        'C' => ['min' => 40, 'max' => 49],
        'D' => ['min' => 33, 'max' => 39],
        'F' => ['min' => 0, 'max' => 32],
    ];

    // Result statuses
    const STATUSES = [
        'Pass',
        'Fail',
        'Absent',
        'Pending'
    ];

    // Relationships
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Accessors
    public function getIsPassAttribute()
    {
        return $this->marks_obtained >= $this->exam->passing_marks;
    }

    // Mutators
    public function setMarksObtainedAttribute($value)
    {
        $this->attributes['marks_obtained'] = $value;
        
        if ($this->exam) {
            $this->attributes['percentage'] = ($value / $this->exam->total_marks) * 100;
            $this->attributes['grade'] = $this->calculateGrade($this->attributes['percentage']);
            $this->attributes['status'] = $this->is_absent ? 'Absent' : 
                                         ($this->is_pass ? 'Pass' : 'Fail');
        }
    }

    /**
     * Calculate grade based on percentage
     */
    private function calculateGrade(float $percentage): string
    {
        foreach (self::GRADES as $grade => $range) {
            if ($percentage >= $range['min'] && $percentage <= $range['max']) {
                return $grade;
            }
        }
        return 'F';
    }
}