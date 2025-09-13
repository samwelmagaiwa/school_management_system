<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuickActionAttendanceService
{
    /**
     * Get students for attendance taking
     */
    public function getStudentsForAttendance(int $classId, string $date, string $period = 'full_day'): array
    {
        $students = Student::with('user:id,first_name,last_name')
            ->where('class_id', $classId)
            ->where('status', true)
            ->orderBy('roll_number')
            ->orderBy('admission_number')
            ->get();

        // Check if attendance already exists
        $existingAttendance = Attendance::where('class_id', $classId)
            ->where('date', $date)
            ->where('period', $period)
            ->pluck('status', 'student_id')
            ->toArray();

        $studentsData = $students->map(function($student) use ($existingAttendance) {
            return [
                'id' => $student->id,
                'name' => $student->user->first_name . ' ' . $student->user->last_name,
                'admission_number' => $student->admission_number,
                'roll_number' => $student->roll_number,
                'section' => $student->section,
                'current_status' => $existingAttendance[$student->id] ?? 'present', // Default to present
                'has_existing_record' => isset($existingAttendance[$student->id])
            ];
        })->toArray();

        return [
            'students' => $studentsData,
            'class_info' => [
                'id' => $classId,
                'total_students' => count($studentsData),
                'has_existing_attendance' => !empty($existingAttendance)
            ],
            'date' => $date,
            'period' => $period
        ];
    }

    /**
     * Quick take attendance for a class
     */
    public function quickTakeAttendance(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $classId = $data['class_id'];
            $date = $data['date'];
            $period = $data['period'];
            $attendanceRecords = $data['attendance'];

            // Delete existing attendance for this class, date, and period
            Attendance::where('class_id', $classId)
                ->where('date', $date)
                ->where('period', $period)
                ->delete();

            $createdRecords = [];
            $presentCount = 0;
            $absentCount = 0;
            $lateCount = 0;

            foreach ($attendanceRecords as $record) {
                $attendanceData = [
                    'student_id' => $record['student_id'],
                    'class_id' => $classId,
                    'date' => $date,
                    'period' => $period,
                    'status' => $record['status'],
                    'remarks' => $record['remarks'] ?? null,
                    'marked_by' => auth()->id(),
                    'marked_at' => now(),
                ];

                $attendance = Attendance::create($attendanceData);
                $createdRecords[] = $attendance;

                // Count statuses
                switch ($record['status']) {
                    case 'present':
                        $presentCount++;
                        break;
                    case 'absent':
                        $absentCount++;
                        break;
                    case 'late':
                        $lateCount++;
                        break;
                }
            }

            return [
                'total_records' => count($createdRecords),
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'attendance_percentage' => count($attendanceRecords) > 0 ? 
                    round((($presentCount + $lateCount) / count($attendanceRecords)) * 100, 2) : 0,
                'date' => $date,
                'period' => $period,
                'class_id' => $classId
            ];
        });
    }

    /**
     * Get attendance summary for a specific class, date, and period
     */
    public function getAttendanceSummary(?int $classId, string $date, string $period = 'full_day', ?int $schoolId = null): array
    {
        $query = Attendance::with(['student.user:id,first_name,last_name', 'class:id,name,grade,section'])
            ->where('date', $date)
            ->where('period', $period);

        if ($classId) {
            $query->where('class_id', $classId);
        } elseif ($schoolId) {
            $query->whereHas('class', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        $attendanceRecords = $query->get();

        $summary = [
            'date' => $date,
            'period' => $period,
            'total_records' => $attendanceRecords->count(),
            'present_count' => $attendanceRecords->where('status', 'present')->count(),
            'absent_count' => $attendanceRecords->where('status', 'absent')->count(),
            'late_count' => $attendanceRecords->where('status', 'late')->count(),
            'attendance_percentage' => 0,
            'records' => []
        ];

        if ($summary['total_records'] > 0) {
            $summary['attendance_percentage'] = round((($summary['present_count'] + $summary['late_count']) / $summary['total_records']) * 100, 2);
        }

        $summary['records'] = $attendanceRecords->map(function($record) {
            return [
                'student_name' => $record->student->user->first_name . ' ' . $record->student->user->last_name,
                'admission_number' => $record->student->admission_number,
                'class_name' => $record->class->name,
                'grade' => $record->class->grade,
                'section' => $record->class->section,
                'status' => $record->status,
                'remarks' => $record->remarks,
                'marked_at' => $record->marked_at->format('H:i:s')
            ];
        })->toArray();

        return $summary;
    }

    /**
     * Check if attendance already exists
     */
    public function checkAttendanceExists(int $classId, string $date, string $period = 'full_day'): bool
    {
        return Attendance::where('class_id', $classId)
            ->where('date', $date)
            ->where('period', $period)
            ->exists();
    }

    /**
     * Get attendance statistics for quick view
     */
    public function getQuickStats(?int $schoolId = null): array
    {
        $today = now()->toDateString();
        $thisWeekStart = now()->startOfWeek()->toDateString();
        $thisMonthStart = now()->startOfMonth()->toDateString();

        $query = Attendance::query();
        
        if ($schoolId) {
            $query->whereHas('class', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        // Today's attendance
        $todayAttendance = (clone $query)->where('date', $today)->get();
        $todayPresent = $todayAttendance->where('status', 'present')->count();
        $todayAbsent = $todayAttendance->where('status', 'absent')->count();
        $todayLate = $todayAttendance->where('status', 'late')->count();
        $todayTotal = $todayAttendance->count();

        // This week's attendance
        $weekAttendance = (clone $query)->where('date', '>=', $thisWeekStart)->get();
        $weekPresent = $weekAttendance->where('status', 'present')->count();
        $weekTotal = $weekAttendance->count();

        // This month's attendance
        $monthAttendance = (clone $query)->where('date', '>=', $thisMonthStart)->get();
        $monthPresent = $monthAttendance->where('status', 'present')->count();
        $monthTotal = $monthAttendance->count();

        // Classes with attendance today
        $classesWithAttendanceToday = Attendance::where('date', $today)
            ->when($schoolId, function($q) use ($schoolId) {
                $q->whereHas('class', function($cq) use ($schoolId) {
                    $cq->where('school_id', $schoolId);
                });
            })
            ->distinct('class_id')
            ->count('class_id');

        return [
            'today' => [
                'total_records' => $todayTotal,
                'present_count' => $todayPresent,
                'absent_count' => $todayAbsent,
                'late_count' => $todayLate,
                'attendance_percentage' => $todayTotal > 0 ? round((($todayPresent + $todayLate) / $todayTotal) * 100, 2) : 0
            ],
            'this_week' => [
                'total_records' => $weekTotal,
                'present_count' => $weekPresent,
                'attendance_percentage' => $weekTotal > 0 ? round(($weekPresent / $weekTotal) * 100, 2) : 0
            ],
            'this_month' => [
                'total_records' => $monthTotal,
                'present_count' => $monthPresent,
                'attendance_percentage' => $monthTotal > 0 ? round(($monthPresent / $monthTotal) * 100, 2) : 0
            ],
            'classes_with_attendance_today' => $classesWithAttendanceToday
        ];
    }

    /**
     * Get recent attendance records
     */
    public function getRecentAttendance(int $days = 7, ?int $classId = null, ?int $schoolId = null): array
    {
        $startDate = now()->subDays($days)->toDateString();
        
        $query = Attendance::with(['student.user:id,first_name,last_name', 'class:id,name,grade,section'])
            ->where('date', '>=', $startDate)
            ->orderBy('date', 'desc')
            ->orderBy('marked_at', 'desc');

        if ($classId) {
            $query->where('class_id', $classId);
        } elseif ($schoolId) {
            $query->whereHas('class', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        $records = $query->limit(100)->get();

        return $records->map(function($record) {
            return [
                'id' => $record->id,
                'student_name' => $record->student->user->first_name . ' ' . $record->student->user->last_name,
                'admission_number' => $record->student->admission_number,
                'class_name' => $record->class->name,
                'grade' => $record->class->grade,
                'section' => $record->class->section,
                'date' => $record->date,
                'period' => $record->period,
                'status' => $record->status,
                'remarks' => $record->remarks,
                'marked_at' => $record->marked_at->format('Y-m-d H:i:s')
            ];
        })->toArray();
    }

    /**
     * Get attendance trends for dashboard
     */
    public function getAttendanceTrends(?int $schoolId = null, int $days = 30): array
    {
        $startDate = now()->subDays($days)->toDateString();
        
        $query = Attendance::selectRaw('date, status, COUNT(*) as count')
            ->where('date', '>=', $startDate)
            ->groupBy('date', 'status')
            ->orderBy('date');

        if ($schoolId) {
            $query->whereHas('class', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        $data = $query->get();
        
        $trends = [];
        $dates = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dates[] = $date;
            
            $dayData = $data->where('date', $date);
            $present = $dayData->where('status', 'present')->sum('count');
            $absent = $dayData->where('status', 'absent')->sum('count');
            $late = $dayData->where('status', 'late')->sum('count');
            $total = $present + $absent + $late;
            
            $trends[] = [
                'date' => $date,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'total' => $total,
                'attendance_percentage' => $total > 0 ? round((($present + $late) / $total) * 100, 2) : 0
            ];
        }

        return [
            'dates' => $dates,
            'trends' => $trends
        ];
    }
}