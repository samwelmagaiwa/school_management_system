<?php

namespace App\Services;

use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Teacher\Models\Teacher;
use App\Modules\Class\Models\SchoolClass;
use App\Modules\Subject\Models\Subject;
use Illuminate\Support\Facades\DB;

class SchoolService
{
    /**
     * Get comprehensive school statistics
     */
    public function getSchoolStatistics(School $school): array
    {
        return [
            'overview' => [
                'total_students' => $school->students()->count(),
                'total_teachers' => $school->teachers()->count(),
                'total_classes' => $school->classes()->count(),
                'total_subjects' => $school->subjects()->count(),
                'active_users' => $school->users()->where('is_active', true)->count(),
            ],
            'students' => [
                'by_gender' => $this->getStudentsByGender($school),
                'by_class' => $this->getStudentsByClass($school),
                'recent_admissions' => $this->getRecentAdmissions($school),
            ],
            'teachers' => [
                'by_specialization' => $this->getTeachersBySpecialization($school),
                'experience_distribution' => $this->getTeachersExperienceDistribution($school),
            ],
            'academic' => [
                'attendance_rate' => $this->getOverallAttendanceRate($school),
                'exam_performance' => $this->getExamPerformance($school),
            ],
        ];
    }

    /**
     * Switch user context to a different school
     */
    public function switchSchoolContext(int $userId, int $schoolId): bool
    {
        // Validate user has access to the school
        $user = \App\Modules\Auth\Models\User::find($userId);
        
        if (!$user || $user->school_id !== $schoolId) {
            return false;
        }

        // Update session or cache with new school context
        session(['current_school_id' => $schoolId]);
        
        return true;
    }

    /**
     * Get multi-school dashboard data for super admin
     */
    public function getMultiSchoolDashboard(): array
    {
        $schools = School::with(['students', 'teachers', 'classes'])->get();
        
        return [
            'total_schools' => $schools->count(),
            'total_students' => $schools->sum(fn($school) => $school->students->count()),
            'total_teachers' => $schools->sum(fn($school) => $school->teachers->count()),
            'schools_overview' => $schools->map(function ($school) {
                return [
                    'id' => $school->id,
                    'name' => $school->name,
                    'students_count' => $school->students->count(),
                    'teachers_count' => $school->teachers->count(),
                    'classes_count' => $school->classes->count(),
                    'is_active' => $school->is_active,
                ];
            }),
        ];
    }

    /**
     * Bulk operations for school setup
     */
    public function setupSchoolStructure(School $school, array $data): array
    {
        DB::beginTransaction();
        
        try {
            $results = [];
            
            // Create classes
            if (isset($data['classes'])) {
                $results['classes'] = $this->createBulkClasses($school, $data['classes']);
            }
            
            // Create subjects
            if (isset($data['subjects'])) {
                $results['subjects'] = $this->createBulkSubjects($school, $data['subjects']);
            }
            
            // Create fee types
            if (isset($data['fee_types'])) {
                $results['fee_types'] = $this->createBulkFeeTypes($school, $data['fee_types']);
            }
            
            DB::commit();
            return $results;
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function getStudentsByGender(School $school): array
    {
        return $school->students()
            ->select('gender', DB::raw('count(*) as count'))
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();
    }

    private function getStudentsByClass(School $school): array
    {
        return $school->classes()
            ->withCount('students')
            ->get()
            ->map(function ($class) {
                return [
                    'class_name' => $class->full_name,
                    'student_count' => $class->students_count,
                ];
            })
            ->toArray();
    }

    private function getRecentAdmissions(School $school): int
    {
        return $school->students()
            ->where('admission_date', '>=', now()->subDays(30))
            ->count();
    }

    private function getTeachersBySpecialization(School $school): array
    {
        return $school->teachers()
            ->select('specialization', DB::raw('count(*) as count'))
            ->groupBy('specialization')
            ->pluck('count', 'specialization')
            ->toArray();
    }

    private function getTeachersExperienceDistribution(School $school): array
    {
        return $school->teachers()
            ->select(
                DB::raw('
                    CASE 
                        WHEN experience_years < 2 THEN "0-2 years"
                        WHEN experience_years < 5 THEN "2-5 years"
                        WHEN experience_years < 10 THEN "5-10 years"
                        ELSE "10+ years"
                    END as experience_range
                '),
                DB::raw('count(*) as count')
            )
            ->groupBy('experience_range')
            ->pluck('count', 'experience_range')
            ->toArray();
    }

    private function getOverallAttendanceRate(School $school): float
    {
        $students = $school->students;
        if ($students->isEmpty()) return 0;

        $totalAttendance = $students->sum(function ($student) {
            return $student->getAttendancePercentage();
        });

        return round($totalAttendance / $students->count(), 2);
    }

    private function getExamPerformance(School $school): array
    {
        // This would calculate overall exam performance metrics
        return [
            'average_score' => 0, // Calculate from exam results
            'pass_rate' => 0, // Calculate pass percentage
            'top_performers' => [], // Get top performing students
        ];
    }

    private function createBulkClasses(School $school, array $classes): array
    {
        $created = [];
        foreach ($classes as $classData) {
            $created[] = SchoolClass::create(array_merge($classData, [
                'school_id' => $school->id
            ]));
        }
        return $created;
    }

    private function createBulkSubjects(School $school, array $subjects): array
    {
        $created = [];
        foreach ($subjects as $subjectData) {
            $created[] = Subject::create(array_merge($subjectData, [
                'school_id' => $school->id
            ]));
        }
        return $created;
    }

    private function createBulkFeeTypes(School $school, array $feeTypes): array
    {
        $created = [];
        foreach ($feeTypes as $feeTypeData) {
            $created[] = \App\Modules\Fee\Models\FeeType::create(array_merge($feeTypeData, [
                'school_id' => $school->id
            ]));
        }
        return $created;
    }
}