<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Modules\User\Models\User;
use App\Modules\School\Models\School;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a demo school (matching actual table structure)
        $school = School::create([
            'name' => 'Demo High School',
            'code' => 'DHS001',
            'email' => 'admin@demoschool.com',
            'phone' => '+1234567890',
            'address' => '123 Education Street, Education City, Knowledge State, Learning Country - 12345',
            'website' => 'https://demoschool.com',
            'established_year' => 2000,
            'principal_name' => 'Dr. Jane Smith',
            'principal_email' => 'principal@demoschool.com',
            'principal_phone' => '+1234567899',
            'description' => 'A premier educational institution focused on excellence.',
            'board_affiliation' => 'CBSE',
            'school_type' => 'all',
            'registration_number' => 'REG001',
            'tax_id' => 'TAX123456',
            'is_active' => true
        ]);

        // Seed users with the dedicated UserSeeder
        $this->call(UserSeeder::class);
    }
}