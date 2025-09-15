<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProjectPermissionService;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class ManagePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:manage 
                            {action : The action to perform (analyze|seed|report|user-check|reset)}
                            {--user= : User ID for user-check action}
                            {--permission= : Permission to check for user-check action}
                            {--role= : Role slug to filter by}
                            {--module= : Module to filter by}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprehensive permissions management for the school management system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $permissionService = new ProjectPermissionService();

        switch ($action) {
            case 'analyze':
                $this->analyzeProject($permissionService);
                break;
                
            case 'seed':
                $this->seedPermissions($permissionService);
                break;
                
            case 'report':
                $this->generateReport($permissionService);
                break;
                
            case 'user-check':
                $this->checkUserPermissions($permissionService);
                break;
                
            case 'reset':
                $this->resetPermissions($permissionService);
                break;
                
            default:
                $this->error('Invalid action. Available actions: analyze, seed, report, user-check, reset');
                return 1;
        }

        return 0;
    }

    /**
     * Analyze project structure
     */
    private function analyzeProject(ProjectPermissionService $service): void
    {
        $this->info('🔍 Analyzing Project Structure...');
        
        $analysis = $service->analyzeProjectStructure();
        
        $this->info('\n📊 ANALYSIS RESULTS');
        $this->info('=' . str_repeat('=', 40));
        
        $this->info(sprintf('Controllers Found: %d', count($analysis['controllers'])));
        $this->info(sprintf('Models Found: %d', count($analysis['models'])));
        $this->info(sprintf('Modules Identified: %d', count($analysis['permissions'])));
        
        // Show controllers
        if (!empty($analysis['controllers'])) {
            $this->info('\n🎮 Controllers:');
            foreach ($analysis['controllers'] as $name => $data) {
                $this->info(sprintf('  • %s (%s)', $name, $data['module']));
            }
        }
        
        // Show modules with permission counts
        if (!empty($analysis['permissions'])) {
            $this->info('\n📦 Modules & Permissions:');
            foreach ($analysis['permissions'] as $module => $data) {
                $this->info(sprintf('  • %s: %d permissions (%s)', 
                    ucfirst($module), 
                    count($data['permissions']), 
                    $data['description']
                ));
            }
        }
        
        $this->info('\n✅ Analysis complete!');
    }

    /**
     * Seed permissions and roles
     */
    private function seedPermissions(ProjectPermissionService $service): void
    {
        $this->info('🌱 Seeding Permissions and Roles...');
        
        if (!$this->confirm('This will create/update permissions and roles. Continue?')) {
            $this->info('Cancelled.');
            return;
        }
        
        $this->info('Creating permissions...');
        $permissions = $service->seedPermissions();
        $this->info(sprintf('✅ Created/Updated %d permissions', count($permissions)));
        
        $this->info('Creating roles...');
        $roles = $service->seedRoles();
        $this->info(sprintf('✅ Created/Updated %d roles', count($roles)));
        
        $this->info('Assigning permissions to roles...');
        $service->assignPermissionsToRoles();
        $this->info('✅ Permissions assigned successfully');
        
        $this->info('Updating user roles...');
        $service->updateUserRoles();
        $this->info('✅ User roles updated');
        
        $this->info('\n🎉 Permissions seeding completed successfully!');
    }

    /**
     * Generate comprehensive system report
     */
    private function generateReport(ProjectPermissionService $service): void
    {
        $this->info('📊 Generating System Report...');
        
        $report = $service->generateSystemReport();
        
        $this->displayComprehensiveReport($report);
        
        // Filter options
        if ($module = $this->option('module')) {
            $this->displayModuleDetails($module);
        }
        
        if ($role = $this->option('role')) {
            $this->displayRoleDetails($role, $service);
        }
    }

    /**
     * Check user permissions
     */
    private function checkUserPermissions(ProjectPermissionService $service): void
    {
        $userId = $this->option('user');
        $permission = $this->option('permission');
        
        if (!$userId) {
            $userId = $this->ask('Enter User ID');
        }
        
        $user = User::find($userId);
        if (!$user) {
            $this->error('User not found!');
            return;
        }
        
        $this->info(sprintf('\n👤 User: %s (%s)', $user->full_name, $user->email));
        $this->info(sprintf('🏇 Role: %s', $user->role));
        
        if ($permission) {
            // Check specific permission
            $hasPermission = $service->userHasPermission($user, $permission);
            $this->info(sprintf('\n🔐 Permission Check: %s', $permission));
            $this->info(sprintf('Result: %s', $hasPermission ? '✅ GRANTED' : '❌ DENIED'));
        } else {
            // Show all user permissions
            $permissions = $service->getPermissionsForRole($user->role);
            
            $this->info(sprintf('\n🔐 Total Permissions: %d', count($permissions)));
            
            if ($this->confirm('Display all permissions?')) {
                $grouped = collect($permissions)->groupBy('module');
                foreach ($grouped as $module => $perms) {
                    $this->info(sprintf('\n📦 %s:', ucfirst($module)));
                    foreach ($perms as $perm) {
                        $this->info(sprintf('  • %s', $perm['slug']));
                    }
                }
            }
        }
    }

    /**
     * Reset permissions system
     */
    private function resetPermissions(ProjectPermissionService $service): void
    {
        $this->error('⚠️  WARNING: This will delete all permissions and roles!');
        
        if (!$this->confirm('Are you absolutely sure? This cannot be undone!')) {
            $this->info('Cancelled.');
            return;
        }
        
        if (!$this->confirm('Type "RESET" to confirm:', false)) {
            $this->info('Cancelled.');
            return;
        }
        
        $this->info('Deleting permissions and roles...');
        
        Permission::truncate();
        Role::truncate();
        
        $this->info('✅ Permissions and roles deleted');
        
        if ($this->confirm('Re-seed permissions now?')) {
            $this->seedPermissions($service);
        }
    }

    /**
     * Display comprehensive report
     */
    private function displayComprehensiveReport(array $report): void
    {
        $this->info('\n📊 COMPREHENSIVE SYSTEM REPORT');
        $this->info('=' . str_repeat('=', 50));
        
        // Project Analysis
        $this->info('\n🔍 PROJECT ANALYSIS:');
        foreach ($report['project_analysis'] as $key => $value) {
            $this->info(sprintf('  • %s: %d', ucfirst(str_replace('_', ' ', $key)), $value));
        }
        
        // Database State
        $this->info('\n🗄️ DATABASE STATE:');
        foreach ($report['database_state'] as $key => $value) {
            $this->info(sprintf('  • %s: %d', ucfirst(str_replace('_', ' ', $key)), $value));
        }
        
        // Role Distribution
        $this->info('\n👥 USER ROLE DISTRIBUTION:');
        foreach ($report['role_distribution'] as $roleData) {
            $this->info(sprintf('  • %s: %d users', $roleData['role'], $roleData['count']));
        }
        
        // Modules
        $this->info('\n📦 DETECTED MODULES:');
        $modules = collect($report['modules'])->sort()->chunk(5);
        foreach ($modules as $chunk) {
            $this->info('  • ' . implode(', ', $chunk->toArray()));
        }
        
        // Role Permissions Summary
        $this->info('\n🔐 ROLE PERMISSIONS SUMMARY:');
        foreach ($report['role_permissions'] as $role => $data) {
            $permCount = $role === 'SuperAdmin' ? 'ALL' : count($data['permissions']);
            $modCount = $role === 'SuperAdmin' ? 'ALL' : count($data['modules']);
            $this->info(sprintf('  • %s: %s permissions, %s modules', 
                $role, $permCount, $modCount));
        }
        
        $this->info('\n' . str_repeat('=', 52));
    }

    /**
     * Display module details
     */
    private function displayModuleDetails(string $module): void
    {
        $permissions = Permission::where('module', $module)->get();
        
        if ($permissions->isEmpty()) {
            $this->error(sprintf('No permissions found for module: %s', $module));
            return;
        }
        
        $this->info(sprintf('\n📦 MODULE DETAILS: %s', strtoupper($module)));
        $this->info('=' . str_repeat('=', 40));
        
        foreach ($permissions as $permission) {
            $this->info(sprintf('  • %s (%s)', $permission->name, $permission->slug));
            if ($permission->description) {
                $this->info(sprintf('    %s', $permission->description));
            }
        }
    }

    /**
     * Display role details
     */
    private function displayRoleDetails(string $roleSlug, ProjectPermissionService $service): void
    {
        $role = Role::where('slug', $roleSlug)->first();
        
        if (!$role) {
            $this->error(sprintf('Role not found: %s', $roleSlug));
            return;
        }
        
        $permissions = $service->getPermissionsForRole($roleSlug);
        
        $this->info(sprintf('\n🏇 ROLE DETAILS: %s', strtoupper($roleSlug)));
        $this->info('=' . str_repeat('=', 40));
        $this->info(sprintf('Name: %s', $role->name));
        $this->info(sprintf('Description: %s', $role->description));
        $this->info(sprintf('System Role: %s', $role->is_system ? 'Yes' : 'No'));
        $this->info(sprintf('Total Permissions: %d', count($permissions)));
        
        if ($this->confirm('Display all permissions for this role?')) {
            $grouped = collect($permissions)->groupBy('module');
            foreach ($grouped as $module => $perms) {
                $this->info(sprintf('\n📦 %s:', ucfirst($module)));
                foreach ($perms as $perm) {
                    $this->info(sprintf('  • %s', $perm['slug']));
                }
            }
        }
    }
}
