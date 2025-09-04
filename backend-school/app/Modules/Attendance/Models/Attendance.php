<?php

namespace App\Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Teacher\Models\Teacher;
use App\Modules\Class\Models\SchoolClass;
use App\Modules\Subject\Models\Subject;
use App\Modules\User\Models\User;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id', 'class_id', 'student_id', 'subject_id', 'teacher_id', 'academic_year_id',
        'attendance_date', 'period_number', 'period_start_time', 'period_end_time',
        'status', 'check_in_time', 'check_out_time', 'late_minutes',
        'remarks', 'absence_reason', 'is_excused', 'excuse_reason', 'excused_by',
        'marked_by', 'marked_at', 'entry_method',
        'is_verified', 'verified_by', 'verified_at',
        'is_modified', 'modification_history', 'last_modified_by'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'period_start_time' => 'datetime:H:i',
        'period_end_time' => 'datetime:H:i',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'marked_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_excused' => 'boolean',
        'is_verified' => 'boolean',
        'is_modified' => 'boolean',
        'modification_history' => 'array',
        'late_minutes' => 'integer',
        'period_number' => 'integer'
    ];

    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT = 'absent';
    const STATUS_LATE = 'late';
    const STATUS_HALF_DAY = 'half_day';
    const STATUS_SICK = 'sick';
    const STATUS_EXCUSED = 'excused';

    const ENTRY_METHOD_MANUAL = 'manual';
    const ENTRY_METHOD_BIOMETRIC = 'biometric';
    const ENTRY_METHOD_RFID = 'rfid';
    const ENTRY_METHOD_MOBILE_APP = 'mobile_app';
    const ENTRY_METHOD_BULK_IMPORT = 'bulk_import';

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function excusedBy()
    {
        return $this->belongsTo(User::class, 'excused_by');
    }

    public function lastModifiedBy()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('status', self::STATUS_PRESENT);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    public function scopeLate($query)
    {
        return $query->where('status', self::STATUS_LATE);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    // Helper methods
    public function isPresent()
    {
        return $this->status === self::STATUS_PRESENT;
    }

    public function isAbsent()
    {
        return $this->status === self::STATUS_ABSENT;
    }

    public function isLate()
    {
        return $this->status === self::STATUS_LATE;
    }

    public function isExcused()
    {
        return $this->is_excused;
    }

    public function getAttendanceStatusAttribute()
    {
        $statuses = [
            self::STATUS_PRESENT => 'Present',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_LATE => 'Late',
            self::STATUS_HALF_DAY => 'Half Day',
            self::STATUS_SICK => 'Sick',
            self::STATUS_EXCUSED => 'Excused'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    public function getEntryMethodLabelAttribute()
    {
        $methods = [
            self::ENTRY_METHOD_MANUAL => 'Manual Entry',
            self::ENTRY_METHOD_BIOMETRIC => 'Biometric',
            self::ENTRY_METHOD_RFID => 'RFID Card',
            self::ENTRY_METHOD_MOBILE_APP => 'Mobile App',
            self::ENTRY_METHOD_BULK_IMPORT => 'Bulk Import'
        ];

        return $methods[$this->entry_method] ?? 'Unknown';
    }

    public function markAsModified($userId, $changes = [])
    {
        $history = $this->modification_history ?? [];
        $history[] = [
            'modified_at' => now(),
            'modified_by' => $userId,
            'changes' => $changes
        ];

        $this->update([
            'is_modified' => true,
            'modification_history' => $history,
            'last_modified_by' => $userId
        ]);
    }

    public function verify($userId)
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now()
        ]);
    }

    public function excuse($userId, $reason = null)
    {
        $this->update([
            'is_excused' => true,
            'excused_by' => $userId,
            'excuse_reason' => $reason
        ]);
    }
}