<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;

use Illuminate\Support\Facades\DB;

class QuickActionClassService
{
    /**
     * Quick create class with minimal required data
     */
    public function quickCreateClass(array $data): SchoolClass
    {
        return DB::transaction(function () use ($data) {
            // Generate class code if not provided
            if (!isset($data['code'])) {
                $data['code'] = $this->getNextClassCode($data['school_id']);
            }

            // Create class record
            $classData = [
                'school_id' => $data['school_id'],
                'name' => $data['name'],
                'code' => $data['code'],
                'grade' => $data['grade'],
                'section' => $data['section'] ?? 'A',
                'capacity' => $data['capacity'] ?? 40,
                'class_teacher_id' => $data['class_teacher_id'] ?? null,
                'room_number' => $data['room_number'] ?? null,
                'description' => $data['description'] ?? null,
                'academic_year' => $data['academic_year'] ?? date('Y') . '-' . (date('Y') + 1),
                'status' => true,
            ];

            $class = SchoolClass::create($classData);

            // Attach subjects if provided
            if (isset($data['subject_ids']) && is_array($data['subject_ids'])) {
                $class->subjects()->attach($data['subject_ids']);
            }

            return $class;
        });
    }

    /**
     * Get next class code for school
     */
    public function getNextClassCode(?int $schoolId = null): string
    {
        if ($schoolId) {
            $school = School::find($schoolId);
            $prefix = $school ? $school->code : 'SCH';
        } else {
            $prefix = 'CLS';
        }

        $year = date('Y');
        $lastClass = SchoolClass::where('school_id', $schoolId)
            ->where('code', 'like', "{$prefix}{$year}%")
            ->orderBy('code', 'desc')
            ->first();

        if ($lastClass) {
            $lastNumber = (int) substr($lastClass->code, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . $year . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get available teachers for class assignment
     */
    public function getAvailableTeachers(int $schoolId): array
    {
        // Get teachers who are not already assigned as class teachers
        $assignedTeacherIds = SchoolClass::where('school_id', $schoolId)
            ->where('status', true)
            ->whereNotNull('class_teacher_id')
            ->pluck('class_teacher_id')
            ->toArray();

        $teachers = Teacher::with('user:id,first_name,last_name')
            ->where('school_id', $schoolId)
            ->where('status', true)
            ->whereNotIn('id', $assignedTeacherIds)
            ->get();

        return $teachers->map(function($teacher) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->user->first_name . ' ' . $teacher->user->last_name,
                'employee_id' => $teacher->employee_id,
                'experience_years' => $teacher->experience_years,
                'employment_type' => $teacher->employment_type
            ];
        })->toArray();
    }

    /**
     * Validate class data
     */
    public function validateClassData(array $data): array
    {
        $errors = [];

        // Check if class code already exists
        if (isset($data['code'])) {
            $exists = SchoolClass::where('code', $data['code'])
                ->where('school_id', $data['school_id'])
                ->exists();
            if ($exists) {
                $errors['code'] = 'Class code already exists';
            }
        }

        // Check if class name already exists for the same grade and section
        $exists = SchoolClass::where('school_id', $data['school_id'])
            ->where('grade', $data['grade'])
            ->where('section', $data['section'] ?? 'A')
            ->where('name', $data['name'])
            ->exists();
        if ($exists) {
            $errors['name'] = 'Class with this name already exists for the same grade and section';
        }

        // Validate teacher assignment
        if (isset($data['class_teacher_id'])) {
            $teacher = Teacher::find($data['class_teacher_id']);
            if (!$teacher || $teacher->school_id != $data['school_id']) {
                $errors['class_teacher_id'] = 'Selected teacher does not belong to this school';
            } else {
                // Check if teacher is already assigned to another class
                $alreadyAssigned = SchoolClass::where('class_teacher_id', $data['class_teacher_id'])
                    ->where('status', true)
                    ->exists();
                if ($alreadyAssigned) {
                    $errors['class_teacher_id'] = 'This teacher is already assigned to another class';
                }
            }
        }

        // Validate subjects belong to school
        if (isset($data['subject_ids']) && is_array($data['subject_ids'])) {
            $validSubjects = Subject::where('school_id', $data['school_id'])
                ->whereIn('id', $data['subject_ids'])
                ->pluck('id')
                ->toArray();
            
            $invalidSubjects = array_diff($data['subject_ids'], $validSubjects);
            if (!empty($invalidSubjects)) {
                $errors['subject_ids'] = 'Some selected subjects do not belong to this school';
            }
        }

        return $errors;
    }

    /**
     * Get class statistics for quick view
     */
    public function getQuickStats(?int $schoolId = null): array
    {
        $query = SchoolClass::query();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $totalClasses = $query->where('status', true)->count();
        
        // Classes by grade
        $classesByGrade = $query->where('status', true)
            ->selectRaw('grade, COUNT(*) as count')
            ->groupBy('grade')
            ->orderBy('grade')
            ->pluck('count', 'grade')
            ->toArray();

        // Classes with teachers assigned
        $classesWithTeachers = $query->where('status', true)
            ->whereNotNull('class_teacher_id')
            ->count();

        // Average capacity
        $avgCapacity = $query->where('status', true)->avg('capacity') ?? 0;

        // Total students enrolled
        $totalStudents = 0;
        if ($schoolId) {
            $totalStudents = Student::where('school_id', $schoolId)
                ->where('status', true)
                ->count();
        } else {
            $totalStudents = Student::where('status', true)->count();
        }

        // Classes at capacity
        $classesAtCapacity = 0;
        $classes = $query->where('status', true)->get();
        foreach ($classes as $class) {
            $studentCount = Student::where('class_id', $class->id)
                ->where('status', true)
                ->count();
            if ($studentCount >= $class->capacity) {
                $classesAtCapacity++;
            }
        }

        return [
            'total_classes' => $totalClasses,
            'classes_by_grade' => $classesByGrade,
            'classes_with_teachers' => $classesWithTeachers,
            'classes_without_teachers' => $totalClasses - $classesWithTeachers,
            'average_capacity' => round($avgCapacity, 0),
            'total_students' => $totalStudents,
            'classes_at_capacity' => $classesAtCapacity,
            'teacher_assignment_ratio' => $totalClasses > 0 ? round(($classesWithTeachers / $totalClasses) * 100, 1) : 0
        ];
    }

    /**
     * Get class capacity utilization
     */
    public function getClassCapacityUtilization(?int $schoolId = null): array
    {
        $query = SchoolClass::query();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $classes = $query->where('status', true)->get();
        $utilization = [];

        foreach ($classes as $class) {
            $studentCount = Student::where('class_id', $class->id)
                ->where('status', true)
                ->count();
            
            $utilizationPercent = $class->capacity > 0 ? round(($studentCount / $class->capacity) * 100, 1) : 0;
            
            $utilization[] = [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'grade' => $class->grade,
                'section' => $class->section,
                'capacity' => $class->capacity,
                'current_students' => $studentCount,
                'utilization_percent' => $utilizationPercent,
                'available_seats' => max(0, $class->capacity - $studentCount)
            ];
        }

        return $utilization;
    }
}