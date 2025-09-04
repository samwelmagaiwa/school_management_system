<?php

namespace App\Modules\Class\Services;

use App\Modules\Class\Models\SchoolClass;
use App\Modules\Student\Models\Student;
use App\Modules\Subject\Models\Subject;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ClassService
{
    /**
     * Get paginated classes with filters
     */
    public function getClasses(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SchoolClass::with(['school', 'classTeacher.user', 'students', 'subjects'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('section', 'like', "%{$search}%")
                      ->orWhere('grade', 'like', "%{$search}%");
                });
            })
            ->when($filters['school_id'] ?? null, function ($query, $schoolId) {
                return $query->where('school_id', $schoolId);
            })
            ->when($filters['grade'] ?? null, function ($query, $grade) {
                return $query->where('grade', $grade);
            })
            ->when($filters['academic_year'] ?? null, function ($query, $year) {
                return $query->where('academic_year', $year);
            })
            ->when($filters['is_active'] ?? null, function ($query, $isActive) {
                return $query->where('is_active', $isActive);
            })
            ->orderBy('grade')
            ->orderBy('name')
            ->orderBy('section');

        $classes = $query->paginate($perPage);

        // Add computed attributes
        $classes->getCollection()->transform(function ($class) {
            $class->current_strength = $class->students()->where('is_active', true)->count();
            $class->capacity_percentage = $class->capacity > 0 ? 
                round(($class->current_strength / $class->capacity) * 100, 1) : 0;
            $class->full_name = $class->name . ($class->section ? ' - ' . $class->section : '');
            return $class;
        });

        ActivityLogger::log('Classes List Retrieved', 'Classes', [
            'filters' => $filters,
            'total_results' => $classes->total(),
            'per_page' => $perPage
        ]);

        return $classes;
    }

    /**
     * Create a new class
     */
    public function createClass(array $data): SchoolClass
    {
        DB::beginTransaction();
        
        try {
            // Generate class code if not provided
            if (!isset($data['class_code'])) {
                $data['class_code'] = $this->generateClassCode($data['grade'], $data['section'] ?? '');
            }

            $class = SchoolClass::create($data);

            // Assign subjects if provided
            if (isset($data['subject_ids']) && is_array($data['subject_ids'])) {
                $class->subjects()->sync($data['subject_ids']);
            }

            // Clear related caches
            $this->clearClassCaches();

            DB::commit();

            ActivityLogger::log('Class Created', 'Classes', [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'section' => $class->section,
                'grade' => $class->grade,
                'school_id' => $class->school_id,
                'capacity' => $class->capacity
            ]);

            return $class->load(['school', 'classTeacher.user', 'subjects']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Class Creation Failed', 'Classes', [
                'error' => $e->getMessage(),
                'input_data' => $data
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Update an existing class
     */
    public function updateClass(SchoolClass $class, array $data): SchoolClass
    {
        DB::beginTransaction();
        
        try {
            $originalData = $class->toArray();
            
            $class->update($data);

            // Update subjects if provided
            if (isset($data['subject_ids']) && is_array($data['subject_ids'])) {
                $class->subjects()->sync($data['subject_ids']);
            }

            // Clear related caches
            $this->clearClassCaches();

            DB::commit();

            ActivityLogger::log('Class Updated', 'Classes', [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'changes' => array_diff_assoc($data, $originalData)
            ]);

            return $class->load(['school', 'classTeacher.user', 'subjects']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Class Update Failed', 'Classes', [
                'class_id' => $class->id,
                'error' => $e->getMessage(),
                'input_data' => $data
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Delete a class
     */
    public function deleteClass(SchoolClass $class): bool
    {
        DB::beginTransaction();
        
        try {
            // Check if class has students
            $studentCount = $class->students()->count();
            if ($studentCount > 0) {
                throw new \Exception("Cannot delete class with {$studentCount} students. Please reassign students first.");
            }

            $classData = [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'section' => $class->section,
                'grade' => $class->grade
            ];

            // Remove subject associations
            $class->subjects()->detach();
            
            // Soft delete the class
            $class->delete();

            // Clear related caches
            $this->clearClassCaches();

            DB::commit();

            ActivityLogger::log('Class Deleted', 'Classes', $classData);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Class Deletion Failed', 'Classes', [
                'class_id' => $class->id,
                'error' => $e->getMessage()
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Get class statistics
     */
    public function getClassStatistics(array $filters = []): array
    {
        $cacheKey = 'class_statistics_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $query = SchoolClass::query();
            
            // Apply filters
            if (isset($filters['school_id'])) {
                $query->where('school_id', $filters['school_id']);
            }
            
            if (isset($filters['academic_year'])) {
                $query->where('academic_year', $filters['academic_year']);
            }

            $totalClasses = $query->count();
            $activeClasses = $query->where('is_active', true)->count();
            
            // Get student statistics
            $studentStats = DB::table('students')
                ->join('classes', 'students.class_id', '=', 'classes.id')
                ->when(isset($filters['school_id']), function ($query) use ($filters) {
                    return $query->where('classes.school_id', $filters['school_id']);
                })
                ->when(isset($filters['academic_year']), function ($query) use ($filters) {
                    return $query->where('classes.academic_year', $filters['academic_year']);
                })
                ->selectRaw('
                    COUNT(*) as total_students,
                    AVG(CASE WHEN classes.capacity > 0 THEN (COUNT(students.id) * 100.0 / classes.capacity) ELSE 0 END) as avg_capacity_utilization
                ')
                ->first();

            $averageClassSize = $totalClasses > 0 ? 
                round(($studentStats->total_students ?? 0) / $totalClasses, 1) : 0;

            $stats = [
                'total_classes' => $totalClasses,
                'active_classes' => $activeClasses,
                'inactive_classes' => $totalClasses - $activeClasses,
                'total_students' => $studentStats->total_students ?? 0,
                'average_class_size' => $averageClassSize,
                'capacity_utilization' => round($studentStats->avg_capacity_utilization ?? 0, 1),
                'classes_by_grade' => $this->getClassesByGrade($filters),
                'capacity_distribution' => $this->getCapacityDistribution($filters)
            ];

            ActivityLogger::log('Class Statistics Retrieved', 'Classes', [
                'filters' => $filters,
                'statistics' => $stats
            ]);

            return $stats;
        });
    }

    /**
     * Assign students to a class
     */
    public function assignStudents(SchoolClass $class, array $studentIds): array
    {
        DB::beginTransaction();
        
        try {
            // Check capacity
            $currentStrength = $class->students()->where('is_active', true)->count();
            $newStudentsCount = count($studentIds);
            
            if (($currentStrength + $newStudentsCount) > $class->capacity) {
                throw new \Exception("Class capacity exceeded. Current: {$currentStrength}, Adding: {$newStudentsCount}, Capacity: {$class->capacity}");
            }

            // Validate students exist and are not already assigned to another class
            $students = Student::whereIn('id', $studentIds)
                ->where('school_id', $class->school_id)
                ->get();

            if ($students->count() !== count($studentIds)) {
                throw new \Exception("Some students not found or belong to different school");
            }

            $alreadyAssigned = $students->whereNotNull('class_id')->pluck('id')->toArray();
            if (!empty($alreadyAssigned)) {
                throw new \Exception("Students with IDs " . implode(', ', $alreadyAssigned) . " are already assigned to other classes");
            }

            // Assign students to class
            Student::whereIn('id', $studentIds)->update(['class_id' => $class->id]);

            // Update class current strength
            $class->update(['current_strength' => $class->students()->where('is_active', true)->count()]);

            // Clear related caches
            $this->clearClassCaches();

            DB::commit();

            ActivityLogger::log('Students Assigned to Class', 'Classes', [
                'class_id' => $class->id,
                'class_name' => $class->full_name,
                'student_ids' => $studentIds,
                'student_count' => count($studentIds),
                'new_class_strength' => $class->current_strength
            ]);

            return [
                'success' => true,
                'message' => count($studentIds) . ' students assigned successfully',
                'class' => $class->load(['students.user'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Student Assignment Failed', 'Classes', [
                'class_id' => $class->id,
                'student_ids' => $studentIds,
                'error' => $e->getMessage()
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Assign subjects to a class
     */
    public function assignSubjects(SchoolClass $class, array $subjectIds): array
    {
        DB::beginTransaction();
        
        try {
            // Validate subjects exist
            $subjects = Subject::whereIn('id', $subjectIds)->get();
            
            if ($subjects->count() !== count($subjectIds)) {
                throw new \Exception("Some subjects not found");
            }

            // Sync subjects with class
            $class->subjects()->sync($subjectIds);

            // Clear related caches
            $this->clearClassCaches();

            DB::commit();

            ActivityLogger::log('Subjects Assigned to Class', 'Classes', [
                'class_id' => $class->id,
                'class_name' => $class->full_name,
                'subject_ids' => $subjectIds,
                'subject_count' => count($subjectIds)
            ]);

            return [
                'success' => true,
                'message' => count($subjectIds) . ' subjects assigned successfully',
                'class' => $class->load(['subjects'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            ActivityLogger::log('Subject Assignment Failed', 'Classes', [
                'class_id' => $class->id,
                'subject_ids' => $subjectIds,
                'error' => $e->getMessage()
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Get available grades
     */
    public function getAvailableGrades(): array
    {
        return Cache::remember('available_grades', 3600, function () {
            return SchoolClass::distinct()
                ->orderBy('grade')
                ->pluck('grade')
                ->filter()
                ->values()
                ->toArray();
        });
    }

    /**
     * Get available academic years
     */
    public function getAvailableAcademicYears(): array
    {
        return Cache::remember('available_academic_years', 3600, function () {
            return SchoolClass::distinct()
                ->orderByDesc('academic_year')
                ->pluck('academic_year')
                ->filter()
                ->values()
                ->toArray();
        });
    }

    /**
     * Generate class code
     */
    private function generateClassCode(string $grade, string $section = ''): string
    {
        $code = $grade;
        if ($section) {
            $code .= strtoupper($section);
        }
        return $code;
    }

    /**
     * Get classes grouped by grade
     */
    private function getClassesByGrade(array $filters = []): array
    {
        $query = SchoolClass::query();
        
        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        return $query->selectRaw('grade, COUNT(*) as count')
            ->groupBy('grade')
            ->orderBy('grade')
            ->pluck('count', 'grade')
            ->toArray();
    }

    /**
     * Get capacity distribution
     */
    private function getCapacityDistribution(array $filters = []): array
    {
        $query = SchoolClass::query();
        
        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        $classes = $query->get(['capacity', 'current_strength']);
        
        $distribution = [
            'under_utilized' => 0, // < 70%
            'well_utilized' => 0,  // 70-90%
            'over_utilized' => 0,  // > 90%
            'full' => 0            // 100%
        ];

        foreach ($classes as $class) {
            $utilization = $class->capacity > 0 ? ($class->current_strength / $class->capacity) * 100 : 0;
            
            if ($utilization >= 100) {
                $distribution['full']++;
            } elseif ($utilization > 90) {
                $distribution['over_utilized']++;
            } elseif ($utilization >= 70) {
                $distribution['well_utilized']++;
            } else {
                $distribution['under_utilized']++;
            }
        }

        return $distribution;
    }

    /**
     * Clear class-related caches
     */
    private function clearClassCaches(): void
    {
        Cache::forget('available_grades');
        Cache::forget('available_academic_years');
        
        // Clear statistics caches
        $cacheKeys = Cache::getRedis()->keys('*class_statistics_*');
        if (!empty($cacheKeys)) {
            Cache::getRedis()->del($cacheKeys);
        }
    }
}