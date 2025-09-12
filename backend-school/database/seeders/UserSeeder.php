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
        User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => Hash::make('12345678'),
                'role' => 'SuperAdmin',
                'school_id' => null,
                'status' => true,
                'phone' => '+1234567890',
                'gender' => 'male',
                'address' => 'System Administrator',
                'date_of_birth' => '1980-01-01'
            ]
        );

        // Create School Admin user
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'School',
                'last_name' => 'Admin',
                'password' => Hash::make('12345678'),
                'role' => 'Admin',
                'school_id' => $school?->id,
                'status' => true,
                'phone' => '+1234567891',
                'gender' => 'female',
                'address' => '123 School Admin Street',
                'date_of_birth' => '1985-05-15'
            ]
        );

        // Create Teacher user
        User::updateOrCreate(
            ['email' => 'teacher@gmail.com'],
            [
                'first_name' => 'John',
                'last_name' => 'Teacher',
                'password' => Hash::make('12345678'),
                'role' => 'Teacher',
                'school_id' => $school?->id,
                'status' => true,
                'phone' => '+1234567892',
                'gender' => 'male',
                'address' => '456 Teacher Avenue',
                'date_of_birth' => '1990-03-20'
            ]
        );

        // Create Student user
        User::updateOrCreate(
            ['email' => 'student@gmail.com'],
            [
                'first_name' => 'Jane',
                'last_name' => 'Student',
                'password' => Hash::make('12345678'),
                'role' => 'Student',
                'school_id' => $school?->id,
                'status' => true,
                'phone' => '+1234567893',
                'gender' => 'female',
                'address' => '789 Student Lane',
                'date_of_birth' => '2005-08-10'
            ]
        );

        // Create Parent user
        User::updateOrCreate(
            ['email' => 'parent@gmail.com'],
            [
                'first_name' => 'Bob',
                'last_name' => 'Parent',
                'password' => Hash::make('12345678'),
                'role' => 'Parent',
                'school_id' => $school?->id,
                'status' => true,
                'phone' => '+1234567894',
                'gender' => 'male',
                'address' => '321 Parent Road',
                'date_of_birth' => '1975-12-05'
            ]
        );

        // Create Accountant user
        User::updateOrCreate(
            ['email' => 'accountant@gmail.com'],
            [
                'first_name' => 'Michael',
                'last_name' => 'Accountant',
                'password' => Hash::make('12345678'),
                'role' => 'Accountant',
                'school_id' => $school?->id,
                'status' => true,
                'phone' => '+1234567895',
                'gender' => 'male',
                'address' => '654 Finance Street',
                'date_of_birth' => '1982-07-25'
            ]
        );

        // Create HR user
        User::updateOrCreate(
            ['email' => 'hr@gmail.com'],
            [
                'first_name' => 'Alice',
                'last_name' => 'HR',
                'password' => Hash::make('12345678'),
                'role' => 'HR',
                'school_id' => $school?->id,
                'status' => true,
                'phone' => '+1234567896',
                'gender' => 'female',
                'address' => '654 HR Boulevard',
                'date_of_birth' => '1982-07-25'
            ]
        );

        // Create additional demo users for testing
        User::updateOrCreate(
            ['email' => 'teacher2@gmail.com'],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Teacher',
                'password' => Hash::make('12345678'),
                'role' => 'Teacher',
                'school_id' => $school?->id,
                'status' => true,
                'phone' => '+1234567897',
                'gender' => 'female',
                'address' => '987 Demo Street',
                'date_of_birth' => '1988-11-30'
            ]
        );

        User::updateOrCreate(
            ['email' => 'student2@gmail.com'],
            [
                'first_name' => 'Alex',
                'last_name' => 'Student',
                'password' => Hash::make('12345678'),
                'role' => 'Student',
                'school_id' => $school?->id,
                'status' => true,
                'phone' => '+1234567898',
                'gender' => 'male',
                'address' => '147 Demo Avenue',
                'date_of_birth' => '2006-02-14'
            ]
        );
    }
}