<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QuickActionTeacherService
{
    /**
     * Quick create teacher with minimal required data
     */
    public function quickCreateTeacher(array $data): Teacher
    {
        return DB::transaction(function () use ($data) {
            // Create user account
            $userData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'teacher123'),
                'role' => 'Teacher',
                'school_id' => $data['school_id'],
                'status' => true,
                'email_verified_at' => now(),
            ];

            $user = User::create($userData);

            // Generate employee ID if not provided
            if (!isset($data['employee_id'])) {
                $data['employee_id'] = $this->getNextEmployeeId($data['school_id']);
            }

            // Create teacher record
            $teacherData = [
                'user_id' => $user->id,
                'school_id' => $data['school_id'],
                'employee_id' => $data['employee_id'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? 'male',
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'experience_years' => $data['experience_years'] ?? 0,
                'employment_type' => $data['employment_type'] ?? 'full_time',
                'salary' => $data['salary'] ?? null,
                'joining_date' => $data['joining_date'] ?? now()->toDateString(),
                'status' => true,
            ];

            $teacher = Teacher::create($teacherData);

            // Attach subjects if provided
            if (isset($data['subject_ids']) && is_array($data['subject_ids'])) {
                $teacher->subjects()->attach($data['subject_ids']);
            }

            return $teacher;
        });
    }

    /**
     * Get next employee ID for school
     */
    public function getNextEmployeeId(?int $schoolId = null): string
    {
        if ($schoolId) {
            $school = School::find($schoolId);
            $prefix = $school ? $school->code : 'SCH';
        } else {
            $prefix = 'TCH';
        }

        $year = date('Y');
        $lastTeacher = Teacher::where('school_id', $schoolId)
            ->where('employee_id', 'like', "{$prefix}{$year}%")
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastTeacher) {
            $lastNumber = (int) substr($lastTeacher->employee_id, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . $year . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Validate teacher data
     */
    public function validateTeacherData(array $data): array
    {
        $errors = [];

        // Check if email already exists
        if (User::where('email', $data['email'])->exists()) {
            $errors['email'] = 'Email already exists';
        }

        // Check if employee ID already exists
        if (isset($data['employee_id'])) {
            $exists = Teacher::where('employee_id', $data['employee_id'])
                ->where('school_id', $data['school_id'])
                ->exists();
            if ($exists) {
                $errors['employee_id'] = 'Employee ID already exists';
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
     * Get teacher statistics for quick view
     */
    public function getQuickStats(?int $schoolId = null): array
    {
        $query = Teacher::query();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $totalTeachers = $query->where('status', true)->count();
        $newThisMonth = $query->where('status', true)
            ->whereMonth('joining_date', now()->month)
            ->whereYear('joining_date', now()->year)
            ->count();

        $fullTimeCount = $query->where('status', true)->where('employment_type', 'full_time')->count();
        $partTimeCount = $query->where('status', true)->where('employment_type', 'part_time')->count();

        $maleCount = $query->where('status', true)->where('gender', 'male')->count();
        $femaleCount = $query->where('status', true)->where('gender', 'female')->count();

        // Average experience
        $avgExperience = $query->where('status', true)->avg('experience_years') ?? 0;

        return [
            'total_teachers' => $totalTeachers,
            'new_this_month' => $newThisMonth,
            'full_time_teachers' => $fullTimeCount,
            'part_time_teachers' => $partTimeCount,
            'male_teachers' => $maleCount,
            'female_teachers' => $femaleCount,
            'average_experience' => round($avgExperience, 1),
            'employment_ratio' => $totalTeachers > 0 ? round(($fullTimeCount / $totalTeachers) * 100, 1) : 0
        ];
    }

    /**
     * Get teachers by subject
     */
    public function getTeachersBySubject(int $subjectId, ?int $schoolId = null): array
    {
        $query = Teacher::with(['user:id,first_name,last_name'])
            ->whereHas('subjects', function($q) use ($subjectId) {
                $q->where('subjects.id', $subjectId);
            })
            ->where('status', true);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->get()->map(function($teacher) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->user->first_name . ' ' . $teacher->user->last_name,
                'employee_id' => $teacher->employee_id,
                'experience_years' => $teacher->experience_years,
                'employment_type' => $teacher->employment_type
            ];
        })->toArray();
    }
}