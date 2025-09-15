<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SafeSchoolSeeder::class, // Use safe version to avoid foreign key issues
            ComprehensivePermissionsSeeder::class, // Initialize permissions system
            SchoolManagementSeeder::class,
            UserSeeder::class,
        ]);
    }
}