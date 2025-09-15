<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use Illuminate\Support\Facades\DB;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Safely clear existing schools while handling foreign key constraints
        $this->clearExistingSchools();

        $schools = [
            [
                'name' => 'Greenwood Elementary School',
                'code' => 'GES001',
                'address' => '123 Oak Street, Springfield, IL 62701',
                'phone' => '+1-555-0101',
                'email' => 'info@greenwood-elementary.edu',
                'website' => 'https://greenwood-elementary.edu',
                'logo' => null,
                'established_year' => 1985,
                'principal_name' => 'Dr. Sarah Johnson',
                'principal_email' => 'principal@greenwood-elementary.edu',
                'principal_phone' => '+1-555-0102',
                'description' => 'A nurturing elementary school focused on building strong foundations in learning and character development.',
                'board_affiliation' => 'Springfield School District',
                'school_type' => 'primary',
                'registration_number' => 'REG-GES-001',
                'tax_id' => 'TAX-36-1234567',
                'settings' => json_encode([
                    'academic_year_start' => 'August',
                    'academic_year_end' => 'June',
                    'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'school_hours' => '8:00 AM - 3:00 PM'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lincoln High School',
                'code' => 'LHS002',
                'address' => '456 Maple Avenue, Springfield, IL 62702',
                'phone' => '+1-555-0201',
                'email' => 'admin@lincoln-high.edu',
                'website' => 'https://lincoln-high.edu',
                'logo' => null,
                'established_year' => 1962,
                'principal_name' => 'Mr. Robert Chen',
                'principal_email' => 'principal@lincoln-high.edu',
                'principal_phone' => '+1-555-0202',
                'description' => 'A comprehensive high school offering diverse academic programs and extracurricular activities.',
                'board_affiliation' => 'Springfield School District',
                'school_type' => 'secondary',
                'registration_number' => 'REG-LHS-002',
                'tax_id' => 'TAX-36-2345678',
                'settings' => json_encode([
                    'academic_year_start' => 'September',
                    'academic_year_end' => 'June',
                    'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'school_hours' => '7:30 AM - 3:30 PM'
                ]),
                'is_active' => true,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],
            [
                'name' => 'Riverside Academy',
                'code' => 'RA003',
                'address' => '789 River Road, Riverside, CA 92501',
                'phone' => '+1-555-0301',
                'email' => 'contact@riverside-academy.edu',
                'website' => 'https://riverside-academy.edu',
                'logo' => null,
                'established_year' => 1998,
                'principal_name' => 'Ms. Maria Rodriguez',
                'principal_email' => 'principal@riverside-academy.edu',
                'principal_phone' => '+1-555-0302',
                'description' => 'A private academy providing personalized education from kindergarten through 12th grade.',
                'board_affiliation' => 'California Association of Independent Schools',
                'school_type' => 'all',
                'registration_number' => 'REG-RA-003',
                'tax_id' => 'TAX-33-3456789',
                'settings' => json_encode([
                    'academic_year_start' => 'August',
                    'academic_year_end' => 'May',
                    'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'school_hours' => '8:15 AM - 3:15 PM'
                ]),
                'is_active' => true,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ],
            [
                'name' => 'Oakwood Middle School',
                'code' => 'OMS004',
                'address' => '321 Pine Street, Oakwood, TX 75001',
                'phone' => '+1-555-0401',
                'email' => 'office@oakwood-middle.edu',
                'website' => 'https://oakwood-middle.edu',
                'logo' => null,
                'established_year' => 1975,
                'principal_name' => 'Dr. James Wilson',
                'principal_email' => 'principal@oakwood-middle.edu',
                'principal_phone' => '+1-555-0402',
                'description' => 'A middle school dedicated to preparing students for high school success through innovative teaching methods.',
                'board_affiliation' => 'Oakwood Independent School District',
                'school_type' => 'secondary',
                'registration_number' => 'REG-OMS-004',
                'tax_id' => 'TAX-75-4567890',
                'settings' => json_encode([
                    'academic_year_start' => 'August',
                    'academic_year_end' => 'June',
                    'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'school_hours' => '8:00 AM - 3:45 PM'
                ]),
                'is_active' => true,
                'created_at' => now()->subDays(45),
                'updated_at' => now()->subDays(45),
            ],
            [
                'name' => 'Sunset Elementary',
                'code' => 'SE005',
                'address' => '654 Sunset Boulevard, Los Angeles, CA 90028',
                'phone' => '+1-555-0501',
                'email' => 'info@sunset-elementary.edu',
                'website' => 'https://sunset-elementary.edu',
                'logo' => null,
                'established_year' => 1955,
                'principal_name' => 'Mrs. Lisa Thompson',
                'principal_email' => 'principal@sunset-elementary.edu',
                'principal_phone' => '+1-555-0502',
                'description' => 'A vibrant elementary school in the heart of Los Angeles, serving diverse communities.',
                'board_affiliation' => 'Los Angeles Unified School District',
                'school_type' => 'primary',
                'registration_number' => 'REG-SE-005',
                'tax_id' => 'TAX-95-5678901',
                'settings' => json_encode([
                    'academic_year_start' => 'September',
                    'academic_year_end' => 'June',
                    'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'school_hours' => '8:30 AM - 2:30 PM'
                ]),
                'is_active' => false, // Inactive school for testing
                'created_at' => now()->subDays(60),
                'updated_at' => now()->subDays(60),
            ],
            [
                'name' => 'Mountain View High School',
                'code' => 'MVHS006',
                'address' => '987 Mountain View Drive, Denver, CO 80202',
                'phone' => '+1-555-0601',
                'email' => 'admin@mountainview-high.edu',
                'website' => 'https://mountainview-high.edu',
                'logo' => null,
                'established_year' => 1980,
                'principal_name' => 'Dr. Michael Davis',
                'principal_email' => 'principal@mountainview-high.edu',
                'principal_phone' => '+1-555-0602',
                'description' => 'A high school with stunning mountain views, offering excellent STEM and arts programs.',
                'board_affiliation' => 'Denver Public Schools',
                'school_type' => 'higher_secondary',
                'registration_number' => 'REG-MVHS-006',
                'tax_id' => 'TAX-84-6789012',
                'settings' => json_encode([
                    'academic_year_start' => 'August',
                    'academic_year_end' => 'May',
                    'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'school_hours' => '7:45 AM - 3:15 PM'
                ]),
                'is_active' => true,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
        ];

        foreach ($schools as $school) {
            School::create($school);
        }

        $this->command->info('Schools seeded successfully!');
    }

    /**
     * Safely clear existing schools while handling foreign key constraints
     */
    private function clearExistingSchools(): void
    {
        try {
            // Check if there are any schools to delete
            $schoolCount = School::count();
            
            if ($schoolCount === 0) {
                $this->command->info('No existing schools to clear.');
                return;
            }
            
            $this->command->info("Clearing {$schoolCount} existing schools...");
            
            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Clear related tables that reference schools
            $relatedTables = [
                'academic_years',
                'users',
                'students', 
                'teachers',
                'classes',
                'subjects',
                'attendances',
                'exams',
                'fees',
                'books',
                'vehicles',
                'id_cards'
            ];
            
            foreach ($relatedTables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::table($table)->where('school_id', '>', 0)->delete();
                    $this->command->info("Cleared related data from {$table} table.");
                }
            }
            
            // Now safely truncate schools table
            DB::table('schools')->truncate();
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->command->info('Successfully cleared all existing schools and related data.');
            
        } catch (\Exception $e) {
            // Re-enable foreign key checks in case of error
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->command->warn('Could not truncate schools table due to foreign key constraints.');
            $this->command->info('Using soft delete approach instead...');
            
            // Fallback: Use soft delete approach
            School::query()->delete();
            $this->command->info('Soft deleted existing schools.');
        }
    }
}