<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Services\ProjectPermissionService;
use Illuminate\Support\Facades\DB;

class ComprehensivePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Comprehensive Permissions Seeding...');
        
        try {
            DB::beginTransaction();
            
            $permissionService = new ProjectPermissionService();
            
            // Step 1: Analyze project structure
            $this->command->info('Step 1: Analyzing project structure...');
            $analysis = $permissionService->analyzeProjectStructure();
            
            $this->command->info(sprintf(
                'Found: %d controllers, %d models, %d modules', 
                count($analysis['controllers']), 
                count($analysis['models']), 
                count($analysis['permissions'])
            ));
            
            // Step 2: Seed permissions
            $this->command->info('Step 2: Creating permissions...');
            $permissions = $permissionService->seedPermissions();
            $this->command->info(sprintf('Created/Updated %d permissions', count($permissions)));
            
            // Step 3: Seed roles
            $this->command->info('Step 3: Creating roles...');
            $roles = $permissionService->seedRoles();
            $this->command->info(sprintf('Created/Updated %d roles', count($roles)));
            
            // Step 4: Assign permissions to roles
            $this->command->info('Step 4: Assigning permissions to roles...');
            $permissionService->assignPermissionsToRoles();
            $this->command->info('Permissions assigned to roles successfully');
            
            // Step 5: Update existing user roles
            $this->command->info('Step 5: Updating existing user roles...');
            $permissionService->updateUserRoles();
            $this->command->info('User roles updated successfully');
            
            // Step 6: Generate and display report
            $this->command->info('Step 6: Generating system report...');
            $report = $permissionService->generateSystemReport();
            
            $this->displayReport($report);
            
            DB::commit();
            $this->command->info('\n✅ Comprehensive permissions seeding completed successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error during permissions seeding: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Display comprehensive system report
     */
    private function displayReport(array $report): void
    {
        $this->command->info('\n📊 COMPREHENSIVE SYSTEM REPORT');
        $this->command->info('=' . str_repeat('=', 50));
        
        // Project Analysis
        $this->command->info('\n🔍 PROJECT ANALYSIS:');
        foreach ($report['project_analysis'] as $key => $value) {
            $this->command->info(sprintf('  • %s: %d', ucfirst(str_replace('_', ' ', $key)), $value));
        }
        
        // Database State
        $this->command->info('\n🗄️  DATABASE STATE:');
        foreach ($report['database_state'] as $key => $value) {
            $this->command->info(sprintf('  • %s: %d', ucfirst(str_replace('_', ' ', $key)), $value));
        }
        
        // Role Distribution
        $this->command->info('\n👥 USER ROLE DISTRIBUTION:');
        foreach ($report['role_distribution'] as $roleData) {
            $this->command->info(sprintf('  • %s: %d users', $roleData['role'], $roleData['count']));
        }
        
        // Modules
        $this->command->info('\n📦 DETECTED MODULES:');
        $modules = collect($report['modules'])->sort()->chunk(5);
        foreach ($modules as $chunk) {
            $this->command->info('  • ' . implode(', ', $chunk->toArray()));
        }
        
        // Role Permissions Summary
        $this->command->info('\n🔐 ROLE PERMISSIONS SUMMARY:');
        foreach ($report['role_permissions'] as $role => $data) {
            $permCount = $role === 'SuperAdmin' ? 'ALL' : count($data['permissions']);
            $modCount = $role === 'SuperAdmin' ? 'ALL' : count($data['modules']);
            $this->command->info(sprintf('  • %s: %s permissions, %s modules', 
                $role, $permCount, $modCount));
        }
        
        $this->command->info('\n' . str_repeat('=', 52));
    }
}
