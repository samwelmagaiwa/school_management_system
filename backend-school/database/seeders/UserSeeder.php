<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Modules\User\Models\User;
use App\Modules\School\Models\School;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the demo school (should be created first)
        $school = School::where('code', 'DHS001')->first();

        // Create SuperAdmin user with specified credentials
        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@school.com',
            'password' => Hash::make('12345678'),
            'role' => 'SuperAdmin',
            'school_id' => null,
            'status' => true,
            'phone' => '+1234567890',
            'gender' => 'male',
            'address' => 'System Administrator',
            'date_of_birth' => '1980-01-01'
        ]);

        // Create School Admin user
        User::create([
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'admin@demoschool.com',
            'password' => Hash::make('12345678'),
            'role' => 'Admin',
            'school_id' => $school?->id,
            'status' => true,
            'phone' => '+1234567891',
            'gender' => 'female',
            'address' => '123 School Admin Street',
            'date_of_birth' => '1985-05-15'
        ]);

        // Create Teacher user
        User::create([
            'first_name' => 'John',
            'last_name' => 'Teacher',
            'email' => 'teacher@demoschool.com',
            'password' => Hash::make('12345678'),
            'role' => 'Teacher',
            'school_id' => $school?->id,
            'status' => true,
            'phone' => '+1234567892',
            'gender' => 'male',
            'address' => '456 Teacher Avenue',
            'date_of_birth' => '1990-03-20'
        ]);

        // Create Student user
        User::create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student@demoschool.com',
            'password' => Hash::make('12345678'),
            'role' => 'Student',
            'school_id' => $school?->id,
            'status' => true,
            'phone' => '+1234567893',
            'gender' => 'female',
            'address' => '789 Student Lane',
            'date_of_birth' => '2005-08-10'
        ]);

        // Create Parent user
        User::create([
            'first_name' => 'Bob',
            'last_name' => 'Parent',
            'email' => 'parent@demoschool.com',
            'password' => Hash::make('12345678'),
            'role' => 'Parent',
            'school_id' => $school?->id,
            'status' => true,
            'phone' => '+1234567894',
            'gender' => 'male',
            'address' => '321 Parent Road',
            'date_of_birth' => '1975-12-05'
        ]);

        // Create HR user (using Admin role as HR is not in the model's ROLES constant)
        User::create([
            'first_name' => 'Alice',
            'last_name' => 'HR',
            'email' => 'hr@demoschool.com',
            'password' => Hash::make('12345678'),
            'role' => 'Admin',
            'school_id' => $school?->id,
            'status' => true,
            'phone' => '+1234567895',
            'gender' => 'female',
            'address' => '654 HR Boulevard',
            'date_of_birth' => '1982-07-25'
        ]);

        // Create additional demo users for testing
        User::create([
            'first_name' => 'Demo',
            'last_name' => 'Teacher2',
            'email' => 'teacher2@demoschool.com',
            'password' => Hash::make('12345678'),
            'role' => 'Teacher',
            'school_id' => $school?->id,
            'status' => true,
            'phone' => '+1234567896',
            'gender' => 'female',
            'address' => '987 Demo Street',
            'date_of_birth' => '1988-11-30'
        ]);

        User::create([
            'first_name' => 'Demo',
            'last_name' => 'Student2',
            'email' => 'student2@demoschool.com',
            'password' => Hash::make('12345678'),
            'role' => 'Student',
            'school_id' => $school?->id,
            'status' => true,
            'phone' => '+1234567897',
            'gender' => 'male',
            'address' => '147 Demo Avenue',
            'date_of_birth' => '2006-02-14'
        ]);
    }
}