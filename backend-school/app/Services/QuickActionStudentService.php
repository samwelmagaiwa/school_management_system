<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class QuickActionStudentService
{
    /**
     * Quick create student with minimal required data
     */
    public function quickCreateStudent(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            // Create user account
            $userData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'student123'),
                'role' => 'Student',
                'school_id' => $data['school_id'],
                'status' => true,
                'email_verified_at' => now(),
            ];

            $user = User::create($userData);

            // Generate admission number if not provided
            if (!isset($data['admission_number'])) {
                $data['admission_number'] = $this->getNextAdmissionNumber($data['school_id']);
            }

            // Create student record
            $studentData = [
                'user_id' => $user->id,
                'school_id' => $data['school_id'],
                'class_id' => $data['class_id'],
                'admission_number' => $data['admission_number'],
                'roll_number' => $data['roll_number'] ?? null,
                'section' => $data['section'] ?? 'A',
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? 'male',
                'blood_group' => $data['blood_group'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'parent_name' => $data['parent_name'] ?? null,
                'parent_phone' => $data['parent_phone'] ?? null,
                'parent_email' => $data['parent_email'] ?? null,
                'admission_date' => $data['admission_date'] ?? now()->toDateString(),
                'status' => true,
            ];

            $student = Student::create($studentData);

            return $student;
        });
    }

    /**
     * Get next admission number for school
     */
    public function getNextAdmissionNumber(?int $schoolId = null): string
    {
        if ($schoolId) {
            $school = School::find($schoolId);
            $prefix = $school ? $school->code : 'SCH';
        } else {
            $prefix = 'STU';
        }

        $year = date('Y');
        $lastStudent = Student::where('school_id', $schoolId)
            ->where('admission_number', 'like', "{$prefix}{$year}%")
            ->orderBy('admission_number', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->admission_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . $year . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get available roll number for class and section
     */
    public function getNextRollNumber(int $classId, string $section): int
    {
        $lastRoll = Student::where('class_id', $classId)
            ->where('section', $section)
            ->max('roll_number');

        return ($lastRoll ?? 0) + 1;
    }

    /**
     * Validate student data
     */
    public function validateStudentData(array $data): array
    {
        $errors = [];

        // Check if email already exists
        if (User::where('email', $data['email'])->exists()) {
            $errors['email'] = 'Email already exists';
        }

        // Check if admission number already exists
        if (isset($data['admission_number'])) {
            $exists = Student::where('admission_number', $data['admission_number'])
                ->where('school_id', $data['school_id'])
                ->exists();
            if ($exists) {
                $errors['admission_number'] = 'Admission number already exists';
            }
        }

        // Check class capacity
        if (isset($data['class_id'])) {
            $class = SchoolClass::find($data['class_id']);
            if ($class) {
                $currentCount = Student::where('class_id', $data['class_id'])
                    ->where('section', $data['section'] ?? 'A')
                    ->where('status', true)
                    ->count();
                
                if ($currentCount >= $class->capacity) {
                    $errors['class_id'] = 'Class section is at full capacity';
                }
            }
        }

        return $errors;
    }

    /**
     * Get student statistics for quick view
     */
    public function getQuickStats(?int $schoolId = null): array
    {
        $query = Student::query();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $totalStudents = $query->where('status', true)->count();
        $newThisMonth = $query->where('status', true)
            ->whereMonth('admission_date', now()->month)
            ->whereYear('admission_date', now()->year)
            ->count();

        $maleCount = $query->where('status', true)->where('gender', 'male')->count();
        $femaleCount = $query->where('status', true)->where('gender', 'female')->count();

        return [
            'total_students' => $totalStudents,
            'new_this_month' => $newThisMonth,
            'male_students' => $maleCount,
            'female_students' => $femaleCount,
            'gender_ratio' => $totalStudents > 0 ? round(($maleCount / $totalStudents) * 100, 1) : 0
        ];
    }
}