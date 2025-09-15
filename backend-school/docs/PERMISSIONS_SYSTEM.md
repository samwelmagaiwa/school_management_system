# Comprehensive Permissions System Documentation

## Overview

This Laravel School Management System now includes a comprehensive, role-based permissions system that automatically analyzes your project structure and creates appropriate permissions for all users based on their roles.

## 🚀 Features

- **Auto-Discovery**: Automatically analyzes controllers and models to generate relevant permissions
- **Role-Based Access Control (RBAC)**: 7 pre-defined roles with specific permissions
- **Comprehensive Coverage**: 193 permissions across 21 modules
- **API Integration**: REST API endpoints for frontend integration
- **Command Line Tools**: Artisan commands for management and debugging
- **Database-Driven**: All permissions stored in database with pivot relationships
- **Backward Compatible**: Integrates seamlessly with existing User model

## 📊 System Statistics

- **Total Permissions**: 193
- **Total Modules**: 21
- **Total Roles**: 7
- **Controllers Analyzed**: 17
- **Models Detected**: 32

## 🎯 User Roles & Permissions

### SuperAdmin (All Permissions)
- **Total Permissions**: ALL (193)
- **Modules**: ALL (21)
- **Description**: Complete system access with all permissions

### Admin (26 Permissions)
- **Modules**: dashboard, user, school, student, teacher, class, subject, attendance, exam, fee, library, transport, hr, idcard, report
- **Key Permissions**: 
  - Full student/teacher/class/subject management
  - School configuration and settings
  - Academic operations (attendance, exams, fees)
  - Reports and analytics access
  - User management within school

### Teacher (23 Permissions)
- **Modules**: dashboard, student, class, subject, attendance, exam, library, report  
- **Key Permissions**:
  - View students and manage grades
  - Mark and manage attendance
  - Create and grade exams
  - Access to academic reports
  - Library services access

### Student (8 Permissions)
- **Modules**: dashboard, student, attendance, exam, fee, library, transport
- **Key Permissions**:
  - View personal academic records
  - View grades and attendance
  - Access exam results
  - View fee information
  - Basic library and transport access

### Parent (8 Permissions)
- **Modules**: dashboard, student, attendance, exam, fee, transport
- **Key Permissions**:
  - View children's academic information
  - Monitor attendance and grades
  - Access exam results
  - View and pay fees
  - Transport information access

### HR (12 Permissions)
- **Modules**: dashboard, user, teacher, hr, report
- **Key Permissions**:
  - Staff management
  - Payroll operations
  - Performance reviews
  - Administrative reports
  - User creation and management

### Accountant (6 Permissions)
- **Modules**: dashboard, student, fee, report
- **Key Permissions**:
  - Complete fee management
  - Financial reporting
  - Payment processing
  - Invoice generation

## 🏗️ Database Structure

### Tables Created

1. **roles** - Store role definitions
2. **permissions** - Store all system permissions  
3. **role_permissions** - Many-to-many relationship between roles and permissions

### Schema Details

```sql
-- Roles table
CREATE TABLE roles (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    is_system BOOLEAN DEFAULT FALSE,
    school_id BIGINT NULLABLE,
    permissions JSON,
    module_access JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Permissions table  
CREATE TABLE permissions (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    module VARCHAR(50),
    category VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Role-Permission pivot table
CREATE TABLE role_permissions (
    id BIGINT PRIMARY KEY,
    role_id BIGINT,
    permission_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(role_id, permission_id)
);
```

## 🔧 Installation & Setup

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Permissions
```bash
# Option 1: Using dedicated command
php artisan permissions:manage seed

# Option 2: Using seeder directly  
php artisan db:seed --class=ComprehensivePermissionsSeeder

# Option 3: Full database seed (includes permissions)
php artisan db:seed
```

### 3. Verify Installation
```bash
php artisan permissions:manage report
```

## 🎮 Artisan Commands

### Main Management Command
```bash
php artisan permissions:manage {action}
```

#### Available Actions:

**analyze** - Analyze project structure
```bash
php artisan permissions:manage analyze
```

**seed** - Create/update permissions and roles
```bash  
php artisan permissions:manage seed
```

**report** - Generate comprehensive system report
```bash
php artisan permissions:manage report

# With filters
php artisan permissions:manage report --module=student
php artisan permissions:manage report --role=Teacher
```

**user-check** - Check user permissions
```bash
php artisan permissions:manage user-check --user=1
php artisan permissions:manage user-check --user=1 --permission=student.view
```

**reset** - Reset entire permissions system
```bash
php artisan permissions:manage reset
```

## 🌐 API Endpoints

### Authentication Required
All endpoints require `auth:sanctum` middleware.

### User Permissions
```http
GET /api/v1/permissions/user
GET /api/v1/permissions/capabilities
POST /api/v1/permissions/check
POST /api/v1/permissions/bulk-check
```

### System Information
```http
GET /api/v1/permissions/all
GET /api/v1/permissions/module/{module}
GET /api/v1/roles/
GET /api/v1/roles/{role}/permissions
GET /api/v1/system/stats
```

### Example API Usage

#### Check User Permissions
```javascript
// Get user's permissions
const response = await fetch('/api/v1/permissions/user', {
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
});

// Check specific permission
const checkResponse = await fetch('/api/v1/permissions/check', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    permission: 'student.create'
  })
});

// Bulk check permissions
const bulkResponse = await fetch('/api/v1/permissions/bulk-check', {
  method: 'POST', 
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    permissions: ['student.view', 'student.create', 'student.edit']
  })
});
```

## 💻 Code Usage

### In Controllers
```php
use App\Services\ProjectPermissionService;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Check permission
        if (!$user->hasPermission('student.view')) {
            abort(403, 'Insufficient permissions');
        }
        
        // Or use service directly
        $permissionService = app(ProjectPermissionService::class);
        $permissionService->authorize($user, 'student.view');
        
        // Your logic here
    }
}
```

### In Models
```php
// Enhanced User model methods
$user = auth()->user();

// Check single permission
$canView = $user->hasPermission('student.view');

// Check multiple permissions
$canManageStudents = $user->hasAllPermissions([
    'student.view', 'student.create', 'student.edit'
]);

// Check any permission
$hasStudentAccess = $user->hasAnyPermission([
    'student.view', 'student.manage'
]);

// Get all permissions
$permissions = $user->getAllPermissions();

// Check module access
$canAccessDashboard = $user->canAccessModule('dashboard');
```

### In Blade Templates
```php
@can('student.create')
    <a href="{{ route('students.create') }}" class="btn btn-primary">
        Add Student
    </a>
@endcan

@if(auth()->user()->hasPermission('reports.export'))
    <button class="btn btn-success">Export Report</button>
@endif
```

### In Frontend (Vue.js Example)
```javascript
// In your Vuex store or component
export default {
  data() {
    return {
      userPermissions: [],
      userCapabilities: {}
    }
  },
  
  async mounted() {
    await this.loadUserPermissions();
  },
  
  methods: {
    async loadUserPermissions() {
      const response = await this.$http.get('/api/v1/permissions/capabilities');
      this.userCapabilities = response.data.data.flat_capabilities;
    },
    
    hasPermission(permission) {
      return this.userCapabilities[permission] === true;
    },
    
    canAccess(permission) {
      return this.hasPermission(permission);
    }
  }
}
```

```vue
<template>
  <div>
    <!-- Show button only if user has permission -->
    <button v-if="hasPermission('student.create')" @click="createStudent">
      Add Student
    </button>
    
    <!-- Conditional navigation -->
    <nav v-if="hasPermission('dashboard.access')">
      <router-link to="/dashboard">Dashboard</router-link>
    </nav>
  </div>
</template>
```

## 📋 Generated Permissions

### Module Overview
The system automatically generates permissions for these modules:

1. **Dashboard** (9 permissions) - Dashboard access and statistics
2. **Auth** (10 permissions) - Authentication operations
3. **User** (11 permissions) - User management 
4. **Role** (6 permissions) - Role and permission management
5. **School** (11 permissions) - School configuration
6. **Settings** (6 permissions) - System settings
7. **Report** (12 permissions) - Reports and analytics
8. **Attendance** (10 permissions) - Attendance management
9. **Class** (9 permissions) - Class organization
10. **Exam** (10 permissions) - Examination system
11. **Fee** (10 permissions) - Financial management
12. **IDCard** (10 permissions) - ID card generation
13. **Library** (10 permissions) - Library management
14. **Student** (12 permissions) - Student records
15. **Subject** (9 permissions) - Subject management
16. **SuperAdmin** (10 permissions) - System administration
17. **Teacher** (10 permissions) - Teacher management
18. **Transport** (10 permissions) - Transportation services
19. **QuickActionStudent** (6 permissions) - Quick student actions
20. **Debug** (6 permissions) - Debug utilities
21. **Log** (6 permissions) - System logging

### Permission Naming Convention
Permissions follow the pattern: `{module}.{action}`

**Standard Actions**: view, create, edit, update, delete, manage

**Module-Specific Actions**: 
- `student.promote`, `student.transfer`, `student.graduate`
- `attendance.mark`, `attendance.bulk_mark`
- `exam.schedule`, `exam.grade`, `exam.publish_results`
- `fee.collect`, `fee.generate_invoices`
- `library.issue_books`, `library.return_books`

## 🔧 Customization

### Adding New Permissions
1. Create new permissions manually:
```php
use App\Models\Permission;

Permission::create([
    'name' => 'Manage Alumni',
    'slug' => 'student.manage_alumni', 
    'description' => 'Manage alumni records',
    'module' => 'student',
    'category' => 'Specific'
]);
```

2. Assign to roles:
```php
use App\Models\Role;

$adminRole = Role::where('slug', 'Admin')->first();
$permission = Permission::where('slug', 'student.manage_alumni')->first();
$adminRole->rolePermissions()->attach($permission->id);
```

### Creating Custom Roles
```php
use App\Models\Role;

$customRole = Role::create([
    'name' => 'Custom Role',
    'slug' => 'custom',
    'description' => 'Custom role description',
    'is_system' => false,
    'permissions' => [
        'dashboard.access',
        'student.view',
        'report.academic'
    ],
    'module_access' => ['dashboard', 'student', 'report'],
    'is_active' => true
]);
```

## 🛡️ Security Features

### Built-in Protection
- **Role Validation**: All roles validated against predefined list
- **Permission Verification**: Permissions checked against database
- **School Context**: School-based data isolation for non-SuperAdmin users  
- **Activity Logging**: All permission checks logged
- **Middleware Protection**: Route-level permission enforcement

### Best Practices
1. **Always Check Permissions**: Never trust frontend-only restrictions
2. **Use Specific Permissions**: Prefer specific permissions over broad ones
3. **Regular Audits**: Use reporting tools to audit permissions
4. **Principle of Least Privilege**: Give users minimal required permissions
5. **Test Different Roles**: Test your application with different user roles

## 🔍 Debugging & Troubleshooting

### Common Issues

#### Permission Not Working
```bash
# Check if permission exists
php artisan permissions:manage user-check --user=1 --permission=student.view

# Regenerate permissions
php artisan permissions:manage seed

# Check user's role
php artisan tinker --execute="echo User::find(1)->role;"
```

#### Role Issues
```bash
# Check role configuration
php artisan permissions:manage report --role=Teacher

# Reset and recreate
php artisan permissions:manage reset
php artisan permissions:manage seed
```

### Debug Mode
Enable debug mode to see detailed permission checking:
```php
// In config/app.php
'debug' => true,

// Check logs
tail -f storage/logs/laravel.log
```

## 📈 Performance Considerations

### Optimization Tips
1. **Cache User Permissions**: Cache frequently checked permissions
2. **Database Indexing**: Indexes already added to key fields
3. **Eager Loading**: Load permissions with users when needed
4. **API Rate Limiting**: Permission API endpoints are rate-limited

### Monitoring
- Use the `/api/v1/system/stats` endpoint to monitor system health
- Regular permission audits via artisan commands
- Monitor slow queries in permission-related tables

## 🎉 Conclusion

This comprehensive permissions system provides enterprise-level access control for your school management system. It automatically adapts to your project structure, provides robust API access for frontend integration, and includes powerful command-line tools for management and debugging.

The system is designed to be both powerful and easy to use, with sensible defaults that work out of the box while remaining fully customizable for specific needs.

For questions or issues, use the artisan commands to analyze and debug the system, or refer to the API endpoints for integration guidance.
