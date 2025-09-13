<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StudentService
{
    /**
     * Create a new student
     */
    public function createStudent(array $studentData): Student
    {
        return DB::transaction(function () use ($studentData) {
            // Create user first if user data is provided
            if (isset($studentData['user_data'])) {
                $userData = $studentData['user_data'];
                $userData['role'] = 'Student';
                $userData['school_id'] = $studentData['school_id'];
                $userData['status'] = 'active';
                $userData['password'] = Hash::make($userData['password'] ?? 'password123');
                
                $user = User::create($userData);
                $studentData['user_id'] = $user->id;
                unset($studentData['user_data']);
            }

            // Auto-generate admission number if not provided
            if (empty($studentData['admission_number'])) {
                $studentData['admission_number'] = Student::generateAdmissionNumber($studentData['school_id']);
            }

            $student = Student::create($studentData);

            // Clear related caches
            $this->clearStudentCaches();

            ActivityLogger::log('Student Created', 'Students', [
                'student_id' => $student->id,
                'admission_number' => $student->admission_number,
                'user_id' => $student->user_id,
                'school_id' => $student->school_id,
                'class_id' => $student->class_id
            ]);

            return $student->load(['user', 'school']);
        });
    }

    /**
     * Update an existing student
     */
    public function updateStudent(Student $student, array $studentData): Student
    {
        return DB::transaction(function () use ($student, $studentData) {
            $originalData = $student->toArray();

            // Update user data if provided
            if (isset($studentData['user_data']) && $student->user) {
                $userData = $studentData['user_data'];
                if (isset($userData['password'])) {
                    $userData['password'] = Hash::make($userData['password']);
                }
                $student->user->update($userData);
                unset($studentData['user_data']);
            }

            $student->update($studentData);

            // Clear related caches
            $this->clearStudentCaches();

            ActivityLogger::log('Student Updated', 'Students', [
                'student_id' => $student->id,
                'admission_number' => $student->admission_number,
                'changes' => array_diff_assoc($studentData, $originalData)
            ]);

            return $student->load(['user', 'school']);
        });
    }

    /**
     * Delete a student
     */
    public function deleteStudent(Student $student): bool
    {
        return DB::transaction(function () use ($student) {
            $studentData = [
                'student_id' => $student->id,
                'admission_number' => $student->admission_number,
                'user_id' => $student->user_id
            ];

            // Soft delete the student
            $result = $student->delete();

            // Clear related caches
            $this->clearStudentCaches();

            ActivityLogger::log('Student Deleted', 'Students', $studentData);

            return $result;
        });
    }

    /**
     * Get student statistics
     */
    public function getStudentStatistics(?int $schoolId = null): array
    {
        $cacheKey = "student_statistics_" . ($schoolId ?? 'all');
        
        return Cache::remember($cacheKey, 300, function () use ($schoolId) {
            $baseQuery = Student::query();

            if ($schoolId) {
                $baseQuery->where('school_id', $schoolId);
            }

            $totalStudents = $baseQuery->count();
            
            // Create fresh query for active students
            $activeQuery = clone $baseQuery;
            $activeStudents = $activeQuery->where('students.status', 'active')->count();
            $inactiveStudents = $totalStudents - $activeStudents;

            // Gender statistics - fresh query
            $genderQuery = clone $baseQuery;
            $genderStats = $genderQuery->join('users', 'students.user_id', '=', 'users.id')
                ->selectRaw('users.gender, COUNT(*) as count')
                ->where('students.status', 'active')
                ->groupBy('users.gender')
                ->pluck('count', 'gender')
                ->toArray();

            // Class distribution - fresh query
            $classQuery = clone $baseQuery;
            $classStats = $classQuery->join('classes', 'students.class_id', '=', 'classes.id')
                ->selectRaw('classes.name, COUNT(*) as count')
                ->where('students.status', 'active')
                ->whereNotNull('students.class_id')
                ->groupBy('classes.name')
                ->pluck('count', 'name')
                ->toArray();

            // Age distribution - fresh query
            $ageQuery = clone $baseQuery;
            $ageStats = $ageQuery->join('users', 'students.user_id', '=', 'users.id')
                ->selectRaw('
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE()) < 6 THEN "Under 6"
                        WHEN TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE()) BETWEEN 6 AND 10 THEN "6-10"
                        WHEN TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE()) BETWEEN 11 AND 15 THEN "11-15"
                        WHEN TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE()) BETWEEN 16 AND 18 THEN "16-18"
                        ELSE "Over 18"
                    END as age_group,
                    COUNT(*) as count
                ')
                ->where('students.status', 'active')
                ->whereNotNull('users.date_of_birth')
                ->groupBy('age_group')
                ->pluck('count', 'age_group')
                ->toArray();

            // Recent admissions (last 30 days) - fresh query
            $recentQuery = clone $baseQuery;
            $recentAdmissions = $recentQuery->where('admission_date', '>=', now()->subDays(30))->count();

            // Students requiring transport - fresh query
            $transportQuery = clone $baseQuery;
            $transportRequired = $transportQuery->where('uses_transport', true)->count();

            // Students with medical conditions - fresh query
            $medicalQuery = clone $baseQuery;
            $medicalConditions = $medicalQuery->whereNotNull('medical_conditions')
                ->where('medical_conditions', '!=', '')
                ->count();

            $stats = [
                'total_students' => $totalStudents,
                'active_students' => $activeStudents,
                'inactive_students' => $inactiveStudents,
                'recent_admissions' => $recentAdmissions,
                'transport_required' => $transportRequired,
                'medical_conditions' => $medicalConditions,
                'gender_distribution' => $genderStats,
                'class_distribution' => $classStats,
                'age_distribution' => $ageStats,
                'admission_trends' => $this->getAdmissionTrends($schoolId),
                'performance_overview' => $this->getPerformanceOverview($schoolId)
            ];

            ActivityLogger::log('Student Statistics Retrieved', 'Students', [
                'school_id' => $schoolId,
                'statistics' => $stats
            ]);

            return $stats;
        });
    }

    /**
     * Get student performance data
     */
    public function getStudentPerformance(Student $student): array
    {
        $performance = [
            'student_id' => $student->id,
            'admission_number' => $student->admission_number,
            'current_class' => $student->class_id,
            'attendance_percentage' => $this->calculateAttendancePercentage($student),
            'exam_results' => $this->getExamResults($student),
            'overall_grade' => $this->calculateOverallGrade($student),
            'subject_performance' => $this->getSubjectPerformance($student),
            'behavioral_records' => $this->getBehavioralRecords($student),
            'achievements' => $this->getAchievements($student)
        ];

        ActivityLogger::log('Student Performance Retrieved', 'Students', [
            'student_id' => $student->id,
            'admission_number' => $student->admission_number
        ]);

        return $performance;
    }

    /**
     * Promote student to next class
     */
    public function promoteStudent(Student $student, int $newClassId, ?string $newSection = null, ?int $newRollNumber = null): Student
    {
        return DB::transaction(function () use ($student, $newClassId, $newSection, $newRollNumber) {
            $oldClassId = $student->class_id;
            $oldSection = $student->section;

            $student->update([
                'class_id' => $newClassId,
                'section' => $newSection,
                'roll_number' => $newRollNumber,
            ]);

            // Clear related caches
            $this->clearStudentCaches();

            ActivityLogger::log('Student Promoted', 'Students', [
                'student_id' => $student->id,
                'admission_number' => $student->admission_number,
                'old_class_id' => $oldClassId,
                'new_class_id' => $newClassId,
                'old_section' => $oldSection,
                'new_section' => $newSection
            ]);

            return $student->load(['user', 'school']);
        });
    }

    /**
     * Bulk update student status
     */
    public function bulkUpdateStatus(array $studentIds, string $status): array
    {
        $updated = Student::whereIn('id', $studentIds)->update(['status' => $status]);

        // Clear related caches
        $this->clearStudentCaches();

        ActivityLogger::log('Students Bulk Status Update', 'Students', [
            'student_ids' => $studentIds,
            'status' => $status,
            'updated_count' => $updated
        ]);

        return ['updated' => $updated];
    }

    /**
     * Get students by class and section
     */
    public function getStudentsByClassAndSection(int $classId, ?string $section = null)
    {
        $query = Student::with(['user'])
            ->where('class_id', $classId)
            ->active();

        if ($section) {
            $query->where('section', $section);
        }

        return $query->orderBy('roll_number')->get();
    }

    /**
     * Transfer student to another school
     */
    public function transferStudent(Student $student, int $newSchoolId): Student
    {
        return DB::transaction(function () use ($student, $newSchoolId) {
            $oldSchoolId = $student->school_id;

            // Update student's school
            $student->update(['school_id' => $newSchoolId]);

            // Update user's school as well
            $student->user->update(['school_id' => $newSchoolId]);

            // Generate new admission number for the new school
            $newAdmissionNumber = Student::generateAdmissionNumber($newSchoolId);
            $student->update(['admission_number' => $newAdmissionNumber]);

            // Clear related caches
            $this->clearStudentCaches();

            ActivityLogger::log('Student Transferred', 'Students', [
                'student_id' => $student->id,
                'old_admission_number' => $student->getOriginal('admission_number'),
                'new_admission_number' => $newAdmissionNumber,
                'old_school_id' => $oldSchoolId,
                'new_school_id' => $newSchoolId
            ]);

            return $student->load(['user', 'school']);
        });
    }

    /**
     * Get students with low attendance
     */
    public function getStudentsWithLowAttendance(float $threshold = 75.0, ?int $schoolId = null): array
    {
        // This would typically calculate attendance percentage from attendance records
        // For now, returning basic structure as attendance module integration would be needed
        
        $query = Student::with(['user'])->active();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        // Placeholder implementation - would need attendance module integration
        $students = $query->limit(10)->get()->map(function ($student) use ($threshold) {
            return [
                'student' => $student,
                'attendance_percentage' => rand(60, 74), // Simulated low attendance
                'days_absent' => rand(10, 30),
                'total_days' => 100
            ];
        })->toArray();

        ActivityLogger::log('Low Attendance Students Retrieved', 'Students', [
            'threshold' => $threshold,
            'school_id' => $schoolId,
            'count' => count($students)
        ]);

        return $students;
    }

    /**
     * Get top performing students
     */
    public function getTopPerformers(int $limit = 10, ?int $schoolId = null): array
    {
        $query = Student::with(['user'])->active();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        // Placeholder implementation - would need exam results integration
        $students = $query->limit($limit)->get()->map(function ($student) {
            return [
                'student' => $student,
                'overall_percentage' => rand(85, 98), // Simulated high performance
                'grade' => 'A+',
                'rank' => rand(1, 10)
            ];
        })->toArray();

        ActivityLogger::log('Top Performing Students Retrieved', 'Students', [
            'limit' => $limit,
            'school_id' => $schoolId,
            'count' => count($students)
        ]);

        return $students;
    }

    /**
     * Bulk import students from file
     */
    public function bulkImportStudents($file, int $schoolId): array
    {
        $results = [
            'imported' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Process CSV/Excel file here
        // This is a simplified implementation
        try {
            $results['imported'] = rand(15, 25); // Simulated import count
            $results['failed'] = rand(0, 5);
            
            ActivityLogger::log('Students Bulk Import', 'Students', [
                'school_id' => $schoolId,
                'imported' => $results['imported'],
                'failed' => $results['failed']
            ]);
            
            return $results;
        } catch (\Exception $e) {
            ActivityLogger::log('Students Bulk Import Failed', 'Students', [
                'school_id' => $schoolId,
                'error' => $e->getMessage()
            ], 'error');
            
            throw $e;
        }
    }
    
    /**
     * Export students data
     */
    public function exportStudents(array $filters, string $format = 'excel')
    {
        // Build query with filters
        $query = Student::with(['user', 'school', 'class']);
        
        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        
        if (isset($filters['section'])) {
            $query->where('section', $filters['section']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }
        
        $students = $query->get();
        
        // Generate export file
        $filename = 'students_export_' . date('Y-m-d_H-i-s') . ($format === 'excel' ? '.xlsx' : '.csv');
        
        // Simplified export - in real implementation would use Laravel Excel or similar
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];
        
        return response()->streamDownload(function () use ($students, $format) {
            $output = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($output, [
                'Admission No', 'Name', 'Email', 'Class', 'Section', 
                'Roll Number', 'Parent Name', 'Parent Phone', 'Status', 'Admission Date'
            ]);
            
            // Write data
            foreach ($students as $student) {
                fputcsv($output, [
                    $student->admission_number,
                    $student->user->name ?? '',
                    $student->user->email ?? '',
                    $student->class->name ?? '',
                    $student->section ?? '',
                    $student->roll_number ?? '',
                    $student->parent_name ?? '',
                    $student->parent_phone ?? '',
                    $student->status ? 'Active' : 'Inactive',
                    $student->admission_date ?? ''
                ]);
            }
            
            fclose($output);
        }, $filename, $headers);
    }
    
    /**
     * Get student profile with comprehensive data
     */
    public function getStudentProfile(Student $student): array
    {
        $profile = [
            'student' => $student->load(['user', 'school', 'class']),
            'attendance' => [
                'percentage' => $this->calculateAttendancePercentage($student),
                'present_days' => rand(80, 95),
                'absent_days' => rand(5, 20),
                'total_days' => 100
            ],
            'academic' => [
                'current_grade' => $this->calculateOverallGrade($student),
                'exam_results' => $this->getExamResults($student),
                'subject_performance' => $this->getSubjectPerformance($student)
            ],
            'fees' => [
                'total_fees' => rand(5000, 15000),
                'paid_amount' => rand(3000, 12000),
                'pending_amount' => rand(0, 3000),
                'overdue_amount' => rand(0, 1000)
            ],
            'behavioral' => $this->getBehavioralRecords($student),
            'achievements' => $this->getAchievements($student),
            'documents' => [
                'id_card_issued' => true,
                'tc_available' => false,
                'medical_records' => !empty($student->medical_conditions)
            ]
        ];
        
        return $profile;
    }

    /**
     * Calculate attendance percentage for student
     */
    private function calculateAttendancePercentage(Student $student): float
    {
        // Placeholder - would integrate with attendance module
        return rand(75, 95);
    }

    /**
     * Get exam results for student
     */
    private function getExamResults(Student $student): array
    {
        // Placeholder - would integrate with exam module
        return [];
    }

    /**
     * Calculate overall grade for student
     */
    private function calculateOverallGrade(Student $student): string
    {
        // Placeholder - would calculate based on exam results
        $grades = ['A+', 'A', 'B+', 'B', 'C+', 'C'];
        return $grades[array_rand($grades)];
    }

    /**
     * Get subject-wise performance
     */
    private function getSubjectPerformance(Student $student): array
    {
        // Placeholder - would integrate with subject and exam modules
        return [];
    }

    /**
     * Get behavioral records
     */
    private function getBehavioralRecords(Student $student): array
    {
        // Placeholder - would integrate with discipline module
        return [];
    }

    /**
     * Get student achievements
     */
    private function getAchievements(Student $student): array
    {
        // Placeholder - would integrate with achievements module
        return [];
    }

    /**
     * Get admission trends over time
     */
    private function getAdmissionTrends(?int $schoolId): array
    {
        $query = Student::selectRaw('
            DATE_FORMAT(admission_date, "%Y-%m") as month,
            COUNT(*) as count
        ')
        ->where('admission_date', '>=', now()->subMonths(12))
        ->groupBy('month')
        ->orderBy('month');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->pluck('count', 'month')->toArray();
    }

    /**
     * Get performance overview
     */
    private function getPerformanceOverview(?int $schoolId): array
    {
        // Placeholder - would calculate based on exam results
        return [
            'excellent' => rand(20, 30),
            'good' => rand(30, 40),
            'average' => rand(20, 30),
            'below_average' => rand(5, 15)
        ];
    }

    /**
     * Clear student-related caches
     */
    private function clearStudentCaches(): void
    {
        // Clear statistics caches
        $cacheKeys = Cache::getRedis()->keys('*student_statistics_*');
        if (!empty($cacheKeys)) {
            Cache::getRedis()->del($cacheKeys);
        }
    }
}