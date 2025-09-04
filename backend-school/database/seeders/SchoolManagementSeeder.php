<?php

namespace Database\Seeders;

use App\Modules\School\Models\School;
use App\Modules\User\Models\User;
use App\Modules\Student\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create SuperAdmin user
        $superAdmin = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@school.com',
            'password' => Hash::make('password'),
            'phone' => '+1234567890',
            'date_of_birth' => '1980-01-01',
            'gender' => 'Male',
            'role' => 'SuperAdmin',
            'school_id' => null,
            'status' => true,
            'email_verified_at' => now(),
        ]);

        // Create sample schools
        $schools = [
            [
                'name' => 'Greenwood High School',
                'code' => 'GHS',
                'address' => '123 Education Street, Learning City, LC 12345',
                'phone' => '+1234567891',
                'email' => 'info@greenwood.edu',
                'website' => 'https://greenwood.edu',
                'established_date' => '1995-06-15',
                'principal_name' => 'Dr. Sarah Johnson',
                'description' => 'A premier educational institution focused on academic excellence.',
                'status' => true,
            ],
            [
                'name' => 'Riverside Elementary',
                'code' => 'RES',
                'address' => '456 River Road, Riverside, RS 67890',
                'phone' => '+1234567892',
                'email' => 'contact@riverside.edu',
                'website' => 'https://riverside.edu',
                'established_date' => '2000-09-01',
                'principal_name' => 'Mr. Michael Brown',
                'description' => 'Nurturing young minds with innovative teaching methods.',
                'status' => true,
            ],
        ];

        foreach ($schools as $schoolData) {
            $school = School::create($schoolData);

            // Create Admin for each school
            $admin = User::create([
                'first_name' => 'School',
                'last_name' => 'Admin',
                'email' => strtolower($school->code) . '.admin@school.com',
                'password' => Hash::make('password'),
                'phone' => '+1234567893',
                'date_of_birth' => '1985-05-15',
                'gender' => 'Female',
                'role' => 'Admin',
                'school_id' => $school->id,
                'status' => true,
                'email_verified_at' => now(),
            ]);

            // Create Teachers for each school
            for ($i = 1; $i <= 3; $i++) {
                $teacher = User::create([
                    'first_name' => 'Teacher',
                    'last_name' => "Number{$i}",
                    'email' => strtolower($school->code) . ".teacher{$i}@school.com",
                    'password' => Hash::make('password'),
                    'phone' => '+123456789' . (3 + $i),
                    'date_of_birth' => '1990-0' . ($i + 2) . '-10',
                    'gender' => $i % 2 == 0 ? 'Female' : 'Male',
                    'role' => 'Teacher',
                    'school_id' => $school->id,
                    'status' => true,
                    'email_verified_at' => now(),
                ]);
            }

            // Create Students for each school
            for ($i = 1; $i <= 10; $i++) {
                $studentUser = User::create([
                    'first_name' => 'Student',
                    'last_name' => "Number{$i}",
                    'email' => strtolower($school->code) . ".student{$i}@school.com",
                    'password' => Hash::make('password'),
                    'phone' => '+123456789' . (10 + $i),
                    'date_of_birth' => '2010-0' . (($i % 9) + 1) . '-15',
                    'gender' => $i % 2 == 0 ? 'Female' : 'Male',
                    'role' => 'Student',
                    'school_id' => $school->id,
                    'status' => true,
                    'email_verified_at' => now(),
                ]);

                // Create Student record
                Student::create([
                    'user_id' => $studentUser->id,
                    'school_id' => $school->id,
                    'admission_no' => $school->code . date('Y') . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'class_id' => null, // Would be set based on available classes
                    'section' => ['A', 'B', 'C'][$i % 3],
                    'roll_number' => $i,
                    'admission_date' => now()->subMonths(rand(1, 12)),
                    'parent_name' => 'Parent of Student Number' . $i,
                    'parent_phone' => '+123456789' . (20 + $i),
                    'parent_email' => strtolower($school->code) . ".parent{$i}@school.com",
                    'emergency_contact' => '+123456789' . (30 + $i),
                    'blood_group' => ['A+', 'B+', 'O+', 'AB+'][$i % 4],
                    'medical_conditions' => $i % 5 == 0 ? 'Asthma' : null,
                    'transport_required' => $i % 3 == 0,
                    'status' => true,
                ]);

                // Create Parent user
                User::create([
                    'first_name' => 'Parent',
                    'last_name' => "OfStudent{$i}",
                    'email' => strtolower($school->code) . ".parent{$i}@school.com",
                    'password' => Hash::make('password'),
                    'phone' => '+123456789' . (20 + $i),
                    'date_of_birth' => '1980-0' . (($i % 9) + 1) . '-20',
                    'gender' => $i % 2 == 0 ? 'Male' : 'Female',
                    'role' => 'Parent',
                    'school_id' => $school->id,
                    'status' => true,
                    'email_verified_at' => now(),
                ]);
            }
        }

        $this->command->info('School Management System seeded successfully!');
        $this->command->info('SuperAdmin: superadmin@school.com / password');
        $this->command->info('School Admins: ghs.admin@school.com, res.admin@school.com / password');
        $this->command->info('Teachers: ghs.teacher1@school.com, res.teacher1@school.com / password');
        $this->command->info('Students: ghs.student1@school.com, res.student1@school.com / password');
    }
}