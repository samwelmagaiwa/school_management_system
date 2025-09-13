<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Teacher;

class SchoolManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin User
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => Hash::make('12345678'),
                'role' => 'SuperAdmin',
                'email_verified_at' => now(),
                'status' => true,
            ]
        );

        // Create a sample school
        $school = School::updateOrCreate(
            ['code' => 'DHS'],
            [
                'name' => 'Demo High School',
                'address' => '123 Education Street, Learning City',
                'phone' => '+1234567890',
                'email' => 'info@demohighschool.com',
                'website' => 'https://demohighschool.com',
                'principal_name' => 'Dr. Jane Smith',
                'established_date' => '2000-01-01',
                'description' => 'A premier educational institution focused on excellence.',
                'status' => true,
            ]
        );

        // Create School Admin
        $schoolAdmin = User::updateOrCreate(
            ['email' => 'schooladmin@gmail.com'],
            [
                'first_name' => 'School',
                'last_name' => 'Admin',
                'password' => Hash::make('12345678'),
                'role' => 'Admin',
                'school_id' => $school->id,
                'email_verified_at' => now(),
                'status' => true,
            ]
        );

        // Create Classes
        $classes = [
            ['name' => 'Grade 9', 'section' => 'A', 'capacity' => 30, 'grade_level' => 9],
            ['name' => 'Grade 9', 'section' => 'B', 'capacity' => 30, 'grade_level' => 9],
            ['name' => 'Grade 10', 'section' => 'A', 'capacity' => 30, 'grade_level' => 10],
            ['name' => 'Grade 10', 'section' => 'B', 'capacity' => 30, 'grade_level' => 10],
            ['name' => 'Grade 11', 'section' => 'A', 'capacity' => 30, 'grade_level' => 11],
            ['name' => 'Grade 12', 'section' => 'A', 'capacity' => 30, 'grade_level' => 12],
        ];

        $schoolClasses = [];
        foreach ($classes as $classData) {
            $schoolClasses[] = SchoolClass::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $classData['name'],
                    'section' => $classData['section']
                ],
                [
                    'capacity' => $classData['capacity'],
                    'is_active' => true,
                ]
            );
        }

        // Create Subjects
        $subjects = [
            'Mathematics',
            'English Language',
            'Science',
            'Physics',
            'Chemistry',
            'Biology',
            'History',
            'Geography',
            'Computer Science',
            'Physical Education',
        ];

        $subjectModels = [];
        foreach ($subjects as $subjectName) {
            $subjectModels[] = Subject::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $subjectName
                ],
                [
                    'code' => strtoupper(substr($subjectName, 0, 3)) . rand(100, 999),
                    'description' => $subjectName . ' curriculum for high school students',
                    'status' => true,
                ]
            );
        }

        // Create Teacher Users
        $teacherUsers = [
            [
                'first_name' => 'John',
                'last_name' => 'Mathematics',
                'email' => 'john.math@gmail.com',
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.english@gmail.com',
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'email' => 'michael.science@gmail.com',
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Davis',
                'email' => 'emily.physics@gmail.com',
            ],
        ];

        foreach ($teacherUsers as $teacherData) {
            $user = User::updateOrCreate(
                ['email' => $teacherData['email']],
                [
                    'first_name' => $teacherData['first_name'],
                    'last_name' => $teacherData['last_name'],
                    'password' => Hash::make('12345678'),
                    'role' => 'Teacher',
                    'school_id' => $school->id,
                    'email_verified_at' => now(),
                    'status' => true,
                ]
            );

            // Create Teacher record if it doesn't exist
            if (!Teacher::where('user_id', $user->id)->exists()) {
                Teacher::create([
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                    'employee_id' => 'EMP' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                    'phone' => '+1234567' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                    'address' => rand(100, 999) . ' Teacher Street, Education City',
                    'date_of_birth' => now()->subYears(rand(25, 45))->format('Y-m-d'),
                    'gender' => rand(0, 1) ? 'male' : 'female',
                    'qualification' => 'Bachelor of Education',
                    'experience_years' => rand(1, 15),
                    'joining_date' => now()->subYears(rand(1, 5))->format('Y-m-d'),
                    'salary' => rand(40000, 80000),
                    'status' => true,
                ]);
            }
        }

        // Create Student Users
        $studentNames = [
            ['Alice', 'Wilson'],
            ['Bob', 'Anderson'],
            ['Charlie', 'Thomas'],
            ['Diana', 'Jackson'],
            ['Edward', 'White'],
        ];

        foreach ($studentNames as $index => $studentName) {
            $email = strtolower($studentName[0]) . '.' . strtolower($studentName[1]) . '@gmail.com';
            
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $studentName[0],
                    'last_name' => $studentName[1],
                    'password' => Hash::make('12345678'),
                    'role' => 'Student',
                    'school_id' => $school->id,
                    'email_verified_at' => now(),
                    'status' => true,
                ]
            );

            // Assign student to first class
            $randomClass = $schoolClasses[0];

            // Create Student record if it doesn't exist
            if (!Student::where('user_id', $user->id)->exists()) {
                Student::create([
                    'user_id' => $user->id,
                    'school_id' => $school->id,
                    'class_id' => $randomClass->id,
                    'admission_number' => $school->code . '2024' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                    'roll_number' => $index + 1,
                    'section' => $randomClass->section,
                    'date_of_birth' => now()->subYears(rand(14, 18))->format('Y-m-d'),
                    'gender' => rand(0, 1) ? 'male' : 'female',
                    'blood_group' => ['A+', 'B+', 'O+', 'AB+'][rand(0, 3)],
                    'phone' => '+1234567' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                    'address' => rand(100, 999) . ' Student Avenue, Learning City',
                    'parent_name' => 'Parent of ' . $studentName[0] . ' ' . $studentName[1],
                    'parent_phone' => '+1234568' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                    'parent_email' => 'parent.' . strtolower($studentName[0]) . '@gmail.com',
                    'admission_date' => now()->subMonths(rand(1, 12))->format('Y-m-d'),
                    'emergency_contacts' => [
                        [
                            'name' => 'Emergency Contact',
                            'relationship' => 'Guardian',
                            'phone' => '+1234569' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                        ]
                    ],
                    'status' => true,
                ]);
            }
        }

        $this->command->info('✅ School Management System seeded successfully!');
        $this->command->info('');
        $this->command->info('🎯 Login Credentials:');
        $this->command->info('📧 Super Admin: superadmin@gmail.com | Password: 12345678');
        $this->command->info('📧 School Admin: schooladmin@gmail.com | Password: 12345678');
        $this->command->info('📧 Teacher Example: john.math@gmail.com | Password: 12345678');
        $this->command->info('📧 Student Example: alice.wilson@gmail.com | Password: 12345678');
        $this->command->info('');
        $this->command->info('📊 Created:');
        $this->command->info("• 1 School: {$school->name}");
        $this->command->info('• ' . count($schoolClasses) . ' Classes');
        $this->command->info('• ' . count($subjectModels) . ' Subjects');
        $this->command->info('• ' . count($teacherUsers) . ' Teachers');
        $this->command->info('• ' . count($studentNames) . ' Students');
    }
}
