<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Modules\SuperAdmin\Models\Role;

class InitializeRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating default roles...');
        
        $roles = Role::createDefaultRoles();
        
        $this->command->info('Created ' . count($roles) . ' default roles:');
        
        foreach ($roles as $role) {
            $this->command->info("- {$role->name} ({$role->slug})");
        }
        
        $this->command->info('Default roles initialized successfully!');
    }
}
