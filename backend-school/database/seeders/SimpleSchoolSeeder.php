<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\School;

class SimpleSchoolSeeder extends Seeder
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
            User::updateOrCreate(
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
        }

        // Create Student Users
        $studentNames = [
            ['Alice', 'Wilson'],
            ['Bob', 'Anderson'], 
            ['Charlie', 'Thomas'],
            ['Diana', 'Jackson'],
            ['Edward', 'White'],
            ['Fiona', 'Brown'],
            ['George', 'Davis'],
            ['Helen', 'Miller'],
            ['Ian', 'Garcia'],
            ['Julia', 'Martinez'],
        ];

        foreach ($studentNames as $studentName) {
            $email = strtolower($studentName[0]) . '.' . strtolower($studentName[1]) . '@gmail.com';
            
            User::updateOrCreate(
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
        }

        // Create Parent Users
        $parentNames = [
            ['Parent', 'Wilson'],
            ['Parent', 'Anderson'],
            ['Parent', 'Thomas'],
        ];

        foreach ($parentNames as $parentName) {
            $email = strtolower('parent.' . $parentName[1]) . '@gmail.com';
            
            User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $parentName[0],
                    'last_name' => $parentName[1],
                    'password' => Hash::make('12345678'),
                    'role' => 'Parent',
                    'school_id' => $school->id,
                    'email_verified_at' => now(),
                    'status' => true,
                ]
            );
        }

        // Create Accountant
        User::updateOrCreate(
            ['email' => 'accountant@gmail.com'],
            [
                'first_name' => 'Finance',
                'last_name' => 'Manager',
                'password' => Hash::make('12345678'),
                'role' => 'Accountant',
                'school_id' => $school->id,
                'email_verified_at' => now(),
                'status' => true,
            ]
        );

        // Create HR
        User::updateOrCreate(
            ['email' => 'hr@gmail.com'],
            [
                'first_name' => 'Human',
                'last_name' => 'Resources',
                'password' => Hash::make('12345678'),
                'role' => 'HR',
                'school_id' => $school->id,
                'email_verified_at' => now(),
                'status' => true,
            ]
        );

        $this->command->info('✅ School Management System seeded successfully!');
        $this->command->info('');
        $this->command->info('🎯 Login Credentials (All passwords: 12345678):');
        $this->command->info('📧 Super Admin: superadmin@gmail.com');
        $this->command->info('📧 School Admin: schooladmin@gmail.com');
        $this->command->info('📧 Teacher Example: john.math@gmail.com');
        $this->command->info('📧 Student Example: alice.wilson@gmail.com');
        $this->command->info('📧 Parent Example: parent.wilson@gmail.com');
        $this->command->info('📧 Accountant: accountant@gmail.com');
        $this->command->info('📧 HR: hr@gmail.com');
        $this->command->info('');
        $this->command->info('📊 Created:');
        $this->command->info("• 1 School: {$school->name}");
        $this->command->info('• ' . count($teacherUsers) . ' Teachers');
        $this->command->info('• ' . count($studentNames) . ' Students');
        $this->command->info('• ' . count($parentNames) . ' Parents');
        $this->command->info('• 1 Super Admin, 1 School Admin, 1 Accountant, 1 HR');
    }
}
