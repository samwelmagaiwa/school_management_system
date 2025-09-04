<?php

namespace App\Modules\Teacher\Services;

use App\Modules\Teacher\Models\Teacher;
use App\Modules\User\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TeacherService
{
    /**
     * Create a new teacher
     */
    public function createTeacher(array $teacherData): Teacher
    {
        return DB::transaction(function () use ($teacherData) {
            // Create user first if user data is provided
            if (isset($teacherData['user_data'])) {
                $userData = $teacherData['user_data'];
                $userData['role'] = 'Teacher';
                $userData['password'] = Hash::make($userData['password'] ?? 'password123');
                
                $user = User::create($userData);
                $teacherData['user_id'] = $user->id;
                unset($teacherData['user_data']);
            }

            // Auto-generate employee ID if not provided
            if (empty($teacherData['employee_id'])) {
                $teacherData['employee_id'] = $this->generateEmployeeId($teacherData['school_id']);
            }

            $teacher = Teacher::create($teacherData);

            // Attach subjects if provided
            if (isset($teacherData['subject_ids'])) {
                $teacher->subjects()->attach($teacherData['subject_ids']);
                unset($teacherData['subject_ids']);
            }

            // Clear related caches
            $this->clearTeacherCaches();

            ActivityLogger::log('Teacher Created', 'Teachers', [
                'teacher_id' => $teacher->id,
                'employee_id' => $teacher->employee_id,
                'user_id' => $teacher->user_id,
                'school_id' => $teacher->school_id,
                'subjects_assigned' => $teacherData['subject_ids'] ?? []
            ]);

            return $teacher->load(['user', 'school', 'subjects']);
        });
    }

    /**
     * Update an existing teacher
     */
    public function updateTeacher(Teacher $teacher, array $teacherData): Teacher
    {
        return DB::transaction(function () use ($teacher, $teacherData) {
            $originalData = $teacher->toArray();

            // Update user data if provided
            if (isset($teacherData['user_data']) && $teacher->user) {
                $userData = $teacherData['user_data'];
                if (isset($userData['password'])) {
                    $userData['password'] = Hash::make($userData['password']);
                }
                $teacher->user->update($userData);
                unset($teacherData['user_data']);
            }

            // Sync subjects if provided
            if (isset($teacherData['subject_ids'])) {
                $teacher->subjects()->sync($teacherData['subject_ids']);
                unset($teacherData['subject_ids']);
            }

            $teacher->update($teacherData);

            // Clear related caches
            $this->clearTeacherCaches();

            ActivityLogger::log('Teacher Updated', 'Teachers', [
                'teacher_id' => $teacher->id,
                'employee_id' => $teacher->employee_id,
                'changes' => array_diff_assoc($teacherData, $originalData)
            ]);

            return $teacher->load(['user', 'school', 'subjects']);
        });
    }

    /**
     * Delete a teacher
     */
    public function deleteTeacher(Teacher $teacher): bool
    {
        return DB::transaction(function () use ($teacher) {
            $teacherData = [
                'teacher_id' => $teacher->id,
                'employee_id' => $teacher->employee_id,
                'user_id' => $teacher->user_id
            ];

            // Soft delete the teacher
            $result = $teacher->delete();

            // Clear related caches
            $this->clearTeacherCaches();

            ActivityLogger::log('Teacher Deleted', 'Teachers', $teacherData);

            return $result;
        });
    }

    /**
     * Get teacher statistics
     */
    public function getTeacherStatistics(?int $schoolId = null): array
    {
        $cacheKey = "teacher_statistics_" . ($schoolId ?? 'all');
        
        return Cache::remember($cacheKey, 300, function () use ($schoolId) {
            $query = Teacher::query();

            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }

            $totalTeachers = $query->count();
            $activeTeachers = $query->where('is_active', true)->count();
            $inactiveTeachers = $totalTeachers - $activeTeachers;

            // Gender statistics
            $genderStats = $query->selectRaw('gender, COUNT(*) as count')
                ->groupBy('gender')
                ->pluck('count', 'gender')
                ->toArray();

            // Experience distribution
            $experienceStats = $query->selectRaw('
                CASE 
                    WHEN experience_years < 2 THEN "0-2 years"
                    WHEN experience_years BETWEEN 2 AND 5 THEN "2-5 years"
                    WHEN experience_years BETWEEN 6 AND 10 THEN "6-10 years"
                    WHEN experience_years BETWEEN 11 AND 15 THEN "11-15 years"
                    ELSE "15+ years"
                END as experience_group,
                COUNT(*) as count
            ')
            ->groupBy('experience_group')
            ->pluck('count', 'experience_group')
            ->toArray();

            // Specialization distribution
            $specializationStats = $query->selectRaw('specialization, COUNT(*) as count')
                ->whereNotNull('specialization')
                ->groupBy('specialization')
                ->pluck('count', 'specialization')
                ->toArray();

            // Age distribution
            $ageStats = $query->selectRaw('
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 25 THEN "Under 25"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 35 THEN "25-35"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 45 THEN "36-45"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 46 AND 55 THEN "46-55"
                    ELSE "Over 55"
                END as age_group,
                COUNT(*) as count
            ')
            ->whereNotNull('date_of_birth')
            ->groupBy('age_group')
            ->pluck('count', 'age_group')
            ->toArray();

            // Recent hires (last 30 days)
            $recentHires = $query->where('joining_date', '>=', now()->subDays(30))->count();

            // Teachers with subjects assigned
            $teachersWithSubjects = $query->whereHas('subjects')->count();
            $teachersWithoutSubjects = $totalTeachers - $teachersWithSubjects;

            // Total students taught
            $totalStudentsTaught = $query->withCount('classes')->get()->sum('classes_count');

            // Average salary
            $averageSalary = $query->avg('salary') ?? 0;

            $stats = [
                'total_teachers' => $totalTeachers,
                'active_teachers' => $activeTeachers,
                'inactive_teachers' => $inactiveTeachers,
                'recent_hires' => $recentHires,
                'teachers_with_subjects' => $teachersWithSubjects,
                'teachers_without_subjects' => $teachersWithoutSubjects,
                'total_students_taught' => $totalStudentsTaught,
                'average_salary' => round($averageSalary, 2),
                'gender_distribution' => $genderStats,
                'experience_distribution' => $experienceStats,
                'specialization_distribution' => $specializationStats,
                'age_distribution' => $ageStats,
                'hiring_trends' => $this->getHiringTrends($schoolId),
                'performance_overview' => $this->getPerformanceOverview($schoolId)
            ];

            ActivityLogger::log('Teacher Statistics Retrieved', 'Teachers', [
                'school_id' => $schoolId,
                'statistics' => $stats
            ]);

            return $stats;
        });
    }

    /**
     * Get teacher performance data
     */
    public function getTeacherPerformance(Teacher $teacher): array
    {
        $performance = [
            'teacher_id' => $teacher->id,
            'employee_id' => $teacher->employee_id,
            'total_classes' => $teacher->classes()->count(),
            'total_students' => $teacher->getTotalStudents(),
            'subjects_taught' => $teacher->subjects()->count(),
            'attendance_rate' => $teacher->getAttendanceRate(),
            'years_of_service' => $this->calculateYearsOfService($teacher),
            'class_performance' => $this->getClassPerformance($teacher),
            'student_feedback' => $this->getStudentFeedback($teacher),
            'professional_development' => $this->getProfessionalDevelopment($teacher)
        ];

        ActivityLogger::log('Teacher Performance Retrieved', 'Teachers', [
            'teacher_id' => $teacher->id,
            'employee_id' => $teacher->employee_id
        ]);

        return $performance;
    }

    /**
     * Assign subjects to teacher
     */
    public function assignSubjects(Teacher $teacher, array $subjectIds): Teacher
    {
        return DB::transaction(function () use ($teacher, $subjectIds) {
            $oldSubjects = $teacher->subjects()->pluck('subjects.id')->toArray();
            $teacher->subjects()->sync($subjectIds);

            // Clear related caches
            $this->clearTeacherCaches();

            ActivityLogger::log('Teacher Subjects Assigned', 'Teachers', [
                'teacher_id' => $teacher->id,
                'employee_id' => $teacher->employee_id,
                'old_subjects' => $oldSubjects,
                'new_subjects' => $subjectIds
            ]);

            return $teacher->load(['subjects']);
        });
    }

    /**
     * Get teacher schedule
     */
    public function getTeacherSchedule(Teacher $teacher): array
    {
        $schedule = $teacher->classes()
            ->with(['subject', 'students'])
            ->get()
            ->groupBy('day_of_week');

        ActivityLogger::log('Teacher Schedule Retrieved', 'Teachers', [
            'teacher_id' => $teacher->id,
            'employee_id' => $teacher->employee_id
        ]);

        return [
            'teacher' => $teacher->load(['user']),
            'schedule' => $schedule
        ];
    }

    /**
     * Bulk update teacher status
     */
    public function bulkUpdateStatus(array $teacherIds, bool $status): int
    {
        $updated = Teacher::whereIn('id', $teacherIds)->update(['is_active' => $status]);

        // Clear related caches
        $this->clearTeacherCaches();

        ActivityLogger::log('Teachers Bulk Status Update', 'Teachers', [
            'teacher_ids' => $teacherIds,
            'status' => $status,
            'updated_count' => $updated
        ]);

        return $updated;
    }

    /**
     * Get teachers by specialization
     */
    public function getTeachersBySpecialization(string $specialization, ?int $schoolId = null)
    {
        $query = Teacher::with(['user', 'subjects'])
            ->bySpecialization($specialization)
            ->active();

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->get();
    }

    /**
     * Get teachers with low performance
     */
    public function getTeachersWithLowPerformance(float $threshold = 70.0, ?int $schoolId = null): array
    {
        $query = Teacher::with(['user'])->active();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        // Placeholder implementation - would need performance metrics integration
        $teachers = $query->limit(10)->get()->map(function ($teacher) use ($threshold) {
            return [
                'teacher' => $teacher,
                'performance_score' => rand(50, 69), // Simulated low performance
                'areas_for_improvement' => ['Student Engagement', 'Lesson Planning'],
                'last_evaluation' => now()->subMonths(rand(1, 6))
            ];
        })->toArray();

        ActivityLogger::log('Low Performance Teachers Retrieved', 'Teachers', [
            'threshold' => $threshold,
            'school_id' => $schoolId,
            'count' => count($teachers)
        ]);

        return $teachers;
    }

    /**
     * Get top performing teachers
     */
    public function getTopPerformingTeachers(int $limit = 10, ?int $schoolId = null): array
    {
        $query = Teacher::with(['user'])->active();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        // Placeholder implementation - would need performance metrics integration
        $teachers = $query->limit($limit)->get()->map(function ($teacher) {
            return [
                'teacher' => $teacher,
                'performance_score' => rand(85, 98), // Simulated high performance
                'achievements' => ['Best Teacher Award', 'Student Favorite'],
                'last_evaluation' => now()->subMonths(rand(1, 3))
            ];
        })->toArray();

        ActivityLogger::log('Top Performing Teachers Retrieved', 'Teachers', [
            'limit' => $limit,
            'school_id' => $schoolId,
            'count' => count($teachers)
        ]);

        return $teachers;
    }

    /**
     * Bulk import teachers from CSV/Excel
     */
    public function bulkImportTeachers(array $teachersData, int $schoolId): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($teachersData)
        ];

        DB::beginTransaction();
        
        try {
            foreach ($teachersData as $index => $teacherData) {
                try {
                    $teacherData['school_id'] = $schoolId;
                    $teacher = $this->createTeacher($teacherData);
                    
                    $results['success'][] = [
                        'row' => $index + 1,
                        'teacher_id' => $teacher->id,
                        'employee_id' => $teacher->employee_id,
                        'name' => $teacher->user->name ?? 'Unknown'
                    ];
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                        'data' => $teacherData
                    ];
                }
            }

            DB::commit();

            ActivityLogger::log('Teachers Bulk Import', 'Teachers', [
                'school_id' => $schoolId,
                'total' => $results['total'],
                'success_count' => count($results['success']),
                'failed_count' => count($results['failed'])
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Teachers Bulk Import Failed', 'Teachers', [
                'school_id' => $schoolId,
                'error' => $e->getMessage()
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Generate employee ID
     */
    private function generateEmployeeId(int $schoolId): string
    {
        $year = date('Y');
        $schoolCode = \App\Modules\School\Models\School::find($schoolId)->code ?? 'SCH';
        
        // Get the last employee ID for this school and year
        $lastTeacher = Teacher::where('school_id', $schoolId)
            ->where('employee_id', 'like', "T{$schoolCode}{$year}%")
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastTeacher) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastTeacher->employee_id, -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return "T{$schoolCode}{$year}" . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate years of service
     */
    private function calculateYearsOfService(Teacher $teacher): float
    {
        if (!$teacher->joining_date) return 0;
        
        return $teacher->joining_date->diffInYears(now());
    }

    /**
     * Get class performance data
     */
    private function getClassPerformance(Teacher $teacher): array
    {
        // Placeholder - would integrate with exam and assessment modules
        return [
            'average_class_score' => rand(75, 90),
            'pass_rate' => rand(85, 95),
            'improvement_rate' => rand(5, 15)
        ];
    }

    /**
     * Get student feedback
     */
    private function getStudentFeedback(Teacher $teacher): array
    {
        // Placeholder - would integrate with feedback module
        return [
            'overall_rating' => rand(4.0, 5.0),
            'total_reviews' => rand(20, 100),
            'positive_feedback_percentage' => rand(80, 95)
        ];
    }

    /**
     * Get professional development data
     */
    private function getProfessionalDevelopment(Teacher $teacher): array
    {
        // Placeholder - would integrate with training module
        return [
            'courses_completed' => rand(2, 10),
            'certifications' => rand(1, 5),
            'last_training_date' => now()->subMonths(rand(1, 12))
        ];
    }

    /**
     * Get hiring trends over time
     */
    private function getHiringTrends(?int $schoolId): array
    {
        $query = Teacher::selectRaw('
            DATE_FORMAT(joining_date, "%Y-%m") as month,
            COUNT(*) as count
        ')
        ->where('joining_date', '>=', now()->subMonths(12))
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
        // Placeholder - would calculate based on performance metrics
        return [
            'excellent' => rand(15, 25),
            'good' => rand(40, 50),
            'average' => rand(20, 30),
            'needs_improvement' => rand(5, 15)
        ];
    }

    /**
     * Clear teacher-related caches
     */
    private function clearTeacherCaches(): void
    {
        // Clear statistics caches
        $cacheKeys = Cache::getRedis()->keys('*teacher_statistics_*');
        if (!empty($cacheKeys)) {
            Cache::getRedis()->del($cacheKeys);
        }
    }
}