<?php

namespace App\Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Student\Models\Student;
use App\Modules\Class\Models\SchoolClass;
use App\Modules\Subject\Models\Subject;
use App\Modules\User\Models\User;

class AttendanceSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'class_id', 'subject_id', 'academic_year_id',
        'month', 'year',
        'total_working_days', 'total_present_days', 'total_absent_days',
        'total_late_days', 'total_half_days', 'total_sick_days', 'total_excused_days',
        'attendance_percentage', 'punctuality_percentage',
        'consecutive_absent_days', 'total_late_minutes',
        'is_below_minimum', 'minimum_required_percentage',
        'alert_sent', 'alert_sent_date',
        'remarks', 'weekly_breakdown',
        'calculated_at', 'calculated_by'
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'total_working_days' => 'integer',
        'total_present_days' => 'integer',
        'total_absent_days' => 'integer',
        'total_late_days' => 'integer',
        'total_half_days' => 'integer',
        'total_sick_days' => 'integer',
        'total_excused_days' => 'integer',
        'attendance_percentage' => 'decimal:2',
        'punctuality_percentage' => 'decimal:2',
        'consecutive_absent_days' => 'integer',
        'total_late_minutes' => 'integer',
        'is_below_minimum' => 'boolean',
        'minimum_required_percentage' => 'decimal:2',
        'alert_sent' => 'boolean',
        'alert_sent_date' => 'date',
        'weekly_breakdown' => 'array',
        'calculated_at' => 'datetime'
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function calculatedBy()
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    // Scopes
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForMonth($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeBelowMinimum($query)
    {
        return $query->where('is_below_minimum', true);
    }

    public function scopeAlertNotSent($query)
    {
        return $query->where('alert_sent', false);
    }

    // Helper methods
    public function isBelowMinimum()
    {
        return $this->attendance_percentage < $this->minimum_required_percentage;
    }

    public function getAttendanceGradeAttribute()
    {
        $percentage = $this->attendance_percentage;
        
        if ($percentage >= 95) return 'Excellent';
        if ($percentage >= 85) return 'Good';
        if ($percentage >= 75) return 'Satisfactory';
        if ($percentage >= 65) return 'Below Average';
        return 'Poor';
    }

    public function getPunctualityGradeAttribute()
    {
        $percentage = $this->punctuality_percentage ?? 0;
        
        if ($percentage >= 95) return 'Excellent';
        if ($percentage >= 85) return 'Good';
        if ($percentage >= 75) return 'Satisfactory';
        if ($percentage >= 65) return 'Below Average';
        return 'Poor';
    }

    public function getMonthNameAttribute()
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return $months[$this->month] ?? 'Unknown';
    }

    public function markAlertSent()
    {
        $this->update([
            'alert_sent' => true,
            'alert_sent_date' => now()
        ]);
    }

    public static function calculateSummary($studentId, $classId, $subjectId, $academicYearId, $month, $year, $calculatedBy)
    {
        // Get attendance records for the month
        $attendanceRecords = Attendance::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->when($subjectId, function ($query, $subjectId) {
                return $query->where('subject_id', $subjectId);
            })
            ->where('academic_year_id', $academicYearId)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->get();

        $totalWorkingDays = $attendanceRecords->count();
        $presentDays = $attendanceRecords->where('status', Attendance::STATUS_PRESENT)->count();
        $absentDays = $attendanceRecords->where('status', Attendance::STATUS_ABSENT)->count();
        $lateDays = $attendanceRecords->where('status', Attendance::STATUS_LATE)->count();
        $halfDays = $attendanceRecords->where('status', Attendance::STATUS_HALF_DAY)->count();
        $sickDays = $attendanceRecords->where('status', Attendance::STATUS_SICK)->count();
        $excusedDays = $attendanceRecords->where('is_excused', true)->count();

        $attendancePercentage = $totalWorkingDays > 0 ? ($presentDays / $totalWorkingDays) * 100 : 0;
        $punctualityPercentage = $totalWorkingDays > 0 ? (($presentDays - $lateDays) / $totalWorkingDays) * 100 : 0;
        $totalLateMinutes = $attendanceRecords->sum('late_minutes');

        // Calculate consecutive absent days
        $consecutiveAbsentDays = 0;
        $currentStreak = 0;
        foreach ($attendanceRecords->sortBy('attendance_date') as $record) {
            if ($record->status === Attendance::STATUS_ABSENT) {
                $currentStreak++;
                $consecutiveAbsentDays = max($consecutiveAbsentDays, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        // Create or update summary
        return self::updateOrCreate(
            [
                'student_id' => $studentId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'academic_year_id' => $academicYearId,
                'month' => $month,
                'year' => $year
            ],
            [
                'total_working_days' => $totalWorkingDays,
                'total_present_days' => $presentDays,
                'total_absent_days' => $absentDays,
                'total_late_days' => $lateDays,
                'total_half_days' => $halfDays,
                'total_sick_days' => $sickDays,
                'total_excused_days' => $excusedDays,
                'attendance_percentage' => round($attendancePercentage, 2),
                'punctuality_percentage' => round($punctualityPercentage, 2),
                'consecutive_absent_days' => $consecutiveAbsentDays,
                'total_late_minutes' => $totalLateMinutes,
                'is_below_minimum' => $attendancePercentage < 75, // Default minimum 75%
                'calculated_at' => now(),
                'calculated_by' => $calculatedBy
            ]
        );
    }
}