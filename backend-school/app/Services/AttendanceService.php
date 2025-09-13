<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * Get attendance records with filters
     */
    public function getAttendanceRecords(array $filters = [])
    {
        $query = Attendance::with([
            'student.user', 
            'teacher.user', 
            'class', 
            'subject', 
            'school',
            'markedBy'
        ]);

        // Apply filters
        if (isset($filters['date'])) {
            $query->forDate($filters['date']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->forDateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['class_id'])) {
            $query->forClass($filters['class_id']);
        }

        if (isset($filters['student_id'])) {
            $query->forStudent($filters['student_id']);
        }

        if (isset($filters['subject_id'])) {
            $query->forSubject($filters['subject_id']);
        }

        if (isset($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->forAcademicYear($filters['academic_year_id']);
        }

        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }

        if (isset($filters['verified'])) {
            if ($filters['verified']) {
                $query->verified();
            } else {
                $query->unverified();
            }
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'attendance_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    /**
     * Create single attendance record
     */
    public function createAttendanceRecord(array $data, $userId)
    {
        try {
            DB::beginTransaction();

            // Check for duplicate attendance
            $existing = Attendance::where('student_id', $data['student_id'])
                ->where('class_id', $data['class_id'])
                ->where('attendance_date', $data['attendance_date'])
                ->when(isset($data['subject_id']), function ($query) use ($data) {
                    return $query->where('subject_id', $data['subject_id']);
                })
                ->when(isset($data['period_number']), function ($query) use ($data) {
                    return $query->where('period_number', $data['period_number']);
                })
                ->first();

            if ($existing) {
                throw new \Exception('Attendance already recorded for this student on this date/period.');
            }

            // Set marked by and marked at
            $data['marked_by'] = $userId;
            $data['marked_at'] = now();

            $attendance = Attendance::create($data);

            // Update attendance summary
            $this->updateAttendanceSummary(
                $attendance->student_id,
                $attendance->class_id,
                $attendance->subject_id,
                $attendance->academic_year_id,
                Carbon::parse($attendance->attendance_date)->month,
                Carbon::parse($attendance->attendance_date)->year,
                $userId
            );

            DB::commit();
            return $attendance->load(['student.user', 'teacher.user', 'class', 'subject', 'markedBy']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create attendance record: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create bulk attendance records
     */
    public function createBulkAttendanceRecords(array $data, $userId)
    {
        try {
            DB::beginTransaction();

            $createdRecords = [];
            $errors = [];

            foreach ($data['attendance_records'] as $index => $recordData) {
                try {
                    // Check for duplicate
                    $existing = Attendance::where('student_id', $recordData['student_id'])
                        ->where('class_id', $data['class_id'])
                        ->where('attendance_date', $data['attendance_date'])
                        ->when(isset($data['subject_id']), function ($query) use ($data) {
                            return $query->where('subject_id', $data['subject_id']);
                        })
                        ->when(isset($data['period_number']), function ($query) use ($data) {
                            return $query->where('period_number', $data['period_number']);
                        })
                        ->first();

                    if ($existing) {
                        $errors[] = "Student ID {$recordData['student_id']}: Attendance already recorded";
                        continue;
                    }

                    // Merge common data with individual record data
                    $attendanceData = array_merge([
                        'school_id' => $data['school_id'],
                        'class_id' => $data['class_id'],
                        'teacher_id' => $data['teacher_id'],
                        'academic_year_id' => $data['academic_year_id'],
                        'attendance_date' => $data['attendance_date'],
                        'subject_id' => $data['subject_id'] ?? null,
                        'period_number' => $data['period_number'] ?? null,
                        'period_start_time' => $data['period_start_time'] ?? null,
                        'period_end_time' => $data['period_end_time'] ?? null,
                        'entry_method' => $data['entry_method'] ?? Attendance::ENTRY_METHOD_BULK_IMPORT,
                        'marked_by' => $userId,
                        'marked_at' => now()
                    ], $recordData);

                    $attendance = Attendance::create($attendanceData);
                    $createdRecords[] = $attendance;

                    // Update attendance summary
                    $this->updateAttendanceSummary(
                        $attendance->student_id,
                        $attendance->class_id,
                        $attendance->subject_id,
                        $attendance->academic_year_id,
                        Carbon::parse($attendance->attendance_date)->month,
                        Carbon::parse($attendance->attendance_date)->year,
                        $userId
                    );

                } catch (\Exception $e) {
                    $errors[] = "Student ID {$recordData['student_id']}: " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'created_count' => count($createdRecords),
                'error_count' => count($errors),
                'records' => $createdRecords,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create bulk attendance records: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update attendance record
     */
    public function updateAttendanceRecord(Attendance $attendance, array $data, $userId)
    {
        try {
            DB::beginTransaction();

            $originalData = $attendance->toArray();
            
            // Track changes for modification history
            $changes = [];
            foreach ($data as $key => $value) {
                if (isset($originalData[$key]) && $originalData[$key] != $value) {
                    $changes[$key] = [
                        'from' => $originalData[$key],
                        'to' => $value
                    ];
                }
            }

            $attendance->update($data);

            // Mark as modified if there were changes
            if (!empty($changes)) {
                $attendance->markAsModified($userId, $changes);
            }

            // Update attendance summary
            $this->updateAttendanceSummary(
                $attendance->student_id,
                $attendance->class_id,
                $attendance->subject_id,
                $attendance->academic_year_id,
                Carbon::parse($attendance->attendance_date)->month,
                Carbon::parse($attendance->attendance_date)->year,
                $userId
            );

            DB::commit();
            return $attendance->load(['student.user', 'teacher.user', 'class', 'subject', 'markedBy']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update attendance record: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete attendance record
     */
    public function deleteAttendanceRecord(Attendance $attendance, $userId)
    {
        try {
            DB::beginTransaction();

            $studentId = $attendance->student_id;
            $classId = $attendance->class_id;
            $subjectId = $attendance->subject_id;
            $academicYearId = $attendance->academic_year_id;
            $month = Carbon::parse($attendance->attendance_date)->month;
            $year = Carbon::parse($attendance->attendance_date)->year;

            $attendance->delete();

            // Update attendance summary
            $this->updateAttendanceSummary($studentId, $classId, $subjectId, $academicYearId, $month, $year, $userId);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete attendance record: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get attendance statistics
     */
    public function getAttendanceStatistics(array $filters = [])
    {
        $query = Attendance::query();

        // Apply filters
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->forDateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['class_id'])) {
            $query->forClass($filters['class_id']);
        }

        if (isset($filters['student_id'])) {
            $query->forStudent($filters['student_id']);
        }

        if (isset($filters['subject_id'])) {
            $query->forSubject($filters['subject_id']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->forAcademicYear($filters['academic_year_id']);
        }

        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }

        $total = $query->count();
        $present = $query->clone()->present()->count();
        $absent = $query->clone()->absent()->count();
        $late = $query->clone()->late()->count();

        $attendancePercentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
        $punctualityPercentage = $total > 0 ? round((($present - $late) / $total) * 100, 2) : 0;

        return [
            'total_records' => $total,
            'present_count' => $present,
            'absent_count' => $absent,
            'late_count' => $late,
            'attendance_percentage' => $attendancePercentage,
            'punctuality_percentage' => $punctualityPercentage,
            'status_breakdown' => [
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'half_day' => $query->clone()->where('status', Attendance::STATUS_HALF_DAY)->count(),
                'sick' => $query->clone()->where('status', Attendance::STATUS_SICK)->count(),
                'excused' => $query->clone()->where('is_excused', true)->count()
            ]
        ];
    }

    /**
     * Get class attendance for a specific date
     */
    public function getClassAttendance($classId, $date, $subjectId = null)
    {
        $students = Student::where('class_id', $classId)
            ->with(['user', 'class'])
            ->get();

        $attendanceRecords = Attendance::where('class_id', $classId)
            ->forDate($date)
            ->when($subjectId, function ($query, $subjectId) {
                return $query->where('subject_id', $subjectId);
            })
            ->get()
            ->keyBy('student_id');

        $result = [];
        foreach ($students as $student) {
            $attendance = $attendanceRecords->get($student->id);
            
            $result[] = [
                'student' => $student,
                'attendance' => $attendance,
                'status' => $attendance ? $attendance->status : null,
                'is_marked' => $attendance !== null
            ];
        }

        return $result;
    }

    /**
     * Update attendance summary
     */
    private function updateAttendanceSummary($studentId, $classId, $subjectId, $academicYearId, $month, $year, $userId)
    {
        try {
            AttendanceSummary::calculateSummary(
                $studentId,
                $classId,
                $subjectId,
                $academicYearId,
                $month,
                $year,
                $userId
            );
        } catch (\Exception $e) {
            Log::error('Failed to update attendance summary: ' . $e->getMessage());
        }
    }

    /**
     * Verify attendance record
     */
    public function verifyAttendance(Attendance $attendance, $userId)
    {
        $attendance->verify($userId);
        return $attendance->load(['verifiedBy']);
    }

    /**
     * Excuse attendance record
     */
    public function excuseAttendance(Attendance $attendance, $userId, $reason = null)
    {
        $attendance->excuse($userId, $reason);
        return $attendance->load(['excusedBy']);
    }

    /**
     * Get attendance report data
     */
    public function getAttendanceReport(array $filters = [])
    {
        $query = AttendanceSummary::with(['student.user', 'class', 'subject']);

        // Apply filters
        if (isset($filters['class_id'])) {
            $query->forClass($filters['class_id']);
        }

        if (isset($filters['student_id'])) {
            $query->forStudent($filters['student_id']);
        }

        if (isset($filters['subject_id'])) {
            $query->forSubject($filters['subject_id']);
        }

        if (isset($filters['month']) && isset($filters['year'])) {
            $query->forMonth($filters['month'], $filters['year']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->forAcademicYear($filters['academic_year_id']);
        }

        if (isset($filters['below_minimum'])) {
            $query->belowMinimum();
        }

        return $query->orderBy('attendance_percentage', 'asc')->get();
    }

    /**
     * Get students with low attendance
     */
    public function getStudentsWithLowAttendance($minimumPercentage = 75, $academicYearId = null)
    {
        $query = AttendanceSummary::with(['student.user', 'class'])
            ->where('attendance_percentage', '<', $minimumPercentage)
            ->where('alert_sent', false);

        if ($academicYearId) {
            $query->forAcademicYear($academicYearId);
        }

        return $query->get();
    }
}