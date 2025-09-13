# Laravel 12.x School Management System - Restructure Guide

## 🔄 Project Restructuring Overview

This document outlines the complete restructuring of the School Management System from a modular architecture to standard Laravel structure, following Laravel 12.x best practices.

## 📁 New Directory Structure

```
backend-school/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/                    # API Version 1 Controllers
│   │   │           ├── AuthController.php
│   │   │           ├── StudentController.php
│   │   │           ├── TeacherController.php
│   │   │           ├── SchoolController.php
│   │   │           ├── SubjectController.php
│   │   │           ├── ClassController.php
│   │   │           ├── AttendanceController.php
│   │   │           ├── ExamController.php
│   │   │           ├── FeeController.php
│   │   │           ├── LibraryController.php
│   │   │           ├── TransportController.php
│   │   │           ├── IdCardController.php
│   │   │           └── DashboardController.php
│   │   ├── Requests/                      # Form Request Classes
│   │   │   ├── LoginRequest.php
│   │   │   ├── RegisterRequest.php
│   │   │   ├── StoreStudentRequest.php
│   │   │   ├── UpdateStudentRequest.php
│   │   │   └── ...
│   │   └── Resources/                     # API Resources
│   │       ├── StudentResource.php
│   │       ├── TeacherResource.php
│   │       └── ...
│   ├── Models/                           # Eloquent Models
│   │   ├── User.php
│   │   ├── Student.php
│   │   ├── Teacher.php
│   │   ├── School.php
│   │   ├── SchoolClass.php
│   │   ├── Subject.php
│   │   ├── Attendance.php
│   │   └── ...
│   └── Services/                         # Business Logic Services
│       ├── StudentService.php
│       ├── TeacherService.php
│       ├── DashboardService.php
│       └── ...
├── database/
│   ├── migrations/                       # All Database Migrations
│   │   ├── 2025_01_15_110003_create_users_table.php
│   │   ├── 2025_01_15_120001_create_schools_table.php
│   │   ├── 2025_01_15_120002_create_subjects_table.php
│   │   ├── 2025_01_15_120003_create_classes_table.php
│   │   ├── 2025_01_15_120004_create_students_table.php
│   │   ├── 2025_01_15_120005_create_teachers_table.php
│   │   ├── 2025_01_15_120006_create_teacher_subjects_table.php
│   │   ├── 2025_01_15_120007_create_class_subjects_table.php
│   │   └── 2025_01_15_120008_create_attendance_table.php
│   └── seeders/                          # Database Seeders
│       ├── DatabaseSeeder.php
│       └── SchoolManagementSeeder.php
└── routes/
    └── api.php                           # Consolidated API Routes
```

## 🎯 Key Changes Made

### 1. **Controller Structure**
- **Before**: `app/Modules/{Module}/Controllers/{Controller}.php`
- **After**: `app/Http/Controllers/Api/V1/{Controller}.php`
- **Namespace**: `App\Http\Controllers\Api\V1`

### 2. **Model Structure**
- **Before**: `app/Modules/{Module}/Models/{Model}.php`
- **After**: `app/Models/{Model}.php`
- **Namespace**: `App\Models`

### 3. **Request Structure**
- **Before**: `app/Modules/{Module}/Requests/{Request}.php`
- **After**: `app/Http/Requests/{Request}.php`
- **Namespace**: `App\Http\Requests`

### 4. **Service Structure**
- **Before**: `app/Modules/{Module}/Services/{Service}.php`
- **After**: `app/Services/{Service}.php`
- **Namespace**: `App\Services`

### 5. **Migration Structure**
- **Before**: `app/Modules/{Module}/Database/Migrations/{migration}.php`
- **After**: `database/migrations/{migration}.php`

### 6. **Route Structure**
- **Before**: Multiple route files in modules
- **After**: Single `routes/api.php` with versioned API routes

## 📋 Complete Student Module Example

### Migration
```php
// database/migrations/2025_01_15_120004_create_students_table.php
Schema::create('students', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
    $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('set null');
    $table->string('admission_number')->unique();
    $table->integer('roll_number')->nullable();
    $table->string('section')->default('A');
    // ... other fields
    $table->timestamps();
    $table->softDeletes();
});
```

### Model
```php
// app/Models/Student.php
namespace App\Models;

class Student extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'user_id', 'school_id', 'class_id', 'admission_number',
        'roll_number', 'section', 'date_of_birth', 'gender',
        // ... other fields
    ];
    
    // Relationships
    public function user() { return $this->belongsTo(User::class); }
    public function school() { return $this->belongsTo(School::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    
    // Scopes
    public function scopeActive($query) { return $query->where('status', true); }
    public function scopeBySchool($query, $schoolId) { return $query->where('school_id', $schoolId); }
}
```

### Form Requests
```php
// app/Http/Requests/StoreStudentRequest.php
namespace App\Http\Requests;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(auth()->user()->role, ['SuperAdmin', 'Admin']);
    }
    
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:50|regex:/^[a-zA-Z\s]+$/',
            'last_name' => 'required|string|max:50|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email|max:100|unique:users,email',
            'school_id' => 'required_if:role,SuperAdmin|exists:schools,id',
            'class_id' => 'required|exists:classes,id',
            // ... other validation rules
        ];
    }
}
```

### Controller
```php
// app/Http/Controllers/Api/V1/StudentController.php
namespace App\Http\Controllers\Api\V1;

class StudentController extends Controller
{
    protected StudentService $studentService;
    
    public function __construct(StudentService $studentService)
    {
        $this->middleware('auth:sanctum');
        $this->studentService = $studentService;
    }
    
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Student::class);
        // Implementation...
    }
    
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $this->authorize('create', Student::class);
        // Implementation...
    }
    
    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);
        // Implementation...
    }
    
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);
        // Implementation...
    }
    
    public function destroy(Student $student): JsonResponse
    {
        $this->authorize('delete', $student);
        // Implementation...
    }
}
```

### Route Entry
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Students
    Route::apiResource('students', StudentController::class);
    Route::prefix('students')->group(function () {
        Route::get('statistics', [StudentController::class, 'statistics']);
        Route::post('{student}/promote', [StudentController::class, 'promote']);
        Route::post('{student}/transfer', [StudentController::class, 'transfer']);
        Route::post('bulk-status', [StudentController::class, 'bulkUpdateStatus']);
        Route::get('export', [StudentController::class, 'export']);
    });
});
```

## 🔗 API Endpoints Structure

### Authentication Endpoints
```
POST   /api/v1/auth/login
POST   /api/v1/auth/register
POST   /api/v1/auth/logout
GET    /api/v1/auth/user
POST   /api/v1/auth/refresh
```

### Resource Endpoints (Following RESTful conventions)
```
GET    /api/v1/students              # List all students
POST   /api/v1/students              # Create new student
GET    /api/v1/students/{id}         # Show specific student
PUT    /api/v1/students/{id}         # Update student
DELETE /api/v1/students/{id}         # Delete student

# Additional endpoints
GET    /api/v1/students/statistics
POST   /api/v1/students/{id}/promote
POST   /api/v1/students/{id}/transfer
POST   /api/v1/students/bulk-status
GET    /api/v1/students/export
```

## 🛠️ Migration Commands

### Run Migrations
```bash
# Fresh migration with seeding
php artisan migrate:fresh --seed

# Regular migration
php artisan migrate

# Rollback migrations
php artisan migrate:rollback
```

### Database Seeding
```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=SchoolManagementSeeder
```

## 🔧 Development Workflow

### 1. **Creating New Resources**
```bash
# Create model with migration
php artisan make:model ModelName -m

# Create controller
php artisan make:controller Api/V1/ModelNameController --api

# Create form requests
php artisan make:request StoreModelNameRequest
php artisan make:request UpdateModelNameRequest

# Create resource
php artisan make:resource ModelNameResource

# Create service
# Manually create in app/Services/
```

### 2. **Testing API Endpoints**
```bash
# Health check
curl http://localhost:8000/api/health

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@school.com","password":"password"}'

# Access protected endpoint
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/v1/students
```

## 📊 Database Relationships

```
Users (1) ←→ (1) Students
Users (1) ←→ (1) Teachers
Schools (1) ←→ (∞) Users
Schools (1) ←→ (∞) Classes
Schools (1) ←→ (∞) Subjects
Classes (1) ←→ (∞) Students
Classes (∞) ←→ (∞) Subjects
Teachers (∞) ←→ (∞) Subjects
Students (1) ←→ (∞) Attendance
```

## 🎨 Frontend Integration

### Vue.js API Service Example
```javascript
// services/api.js
import axios from 'axios'

const API_BASE_URL = 'http://localhost:8000/api/v1'

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Add auth token to requests
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export const studentAPI = {
  getAll: (params) => apiClient.get('/students', { params }),
  getById: (id) => apiClient.get(`/students/${id}`),
  create: (data) => apiClient.post('/students', data),
  update: (id, data) => apiClient.put(`/students/${id}`, data),
  delete: (id) => apiClient.delete(`/students/${id}`),
  getStatistics: () => apiClient.get('/students/statistics'),
}
```

## 🔒 Security Features

### 1. **Authentication**
- Laravel Sanctum for API authentication
- Token-based authentication for SPA
- Role-based access control

### 2. **Authorization**
- Policy-based authorization
- Route middleware protection
- Resource-level permissions

### 3. **Validation**
- Form Request validation
- Custom validation rules
- Server-side validation for all inputs

## 🚀 Performance Optimizations

### 1. **Database**
- Proper indexing on frequently queried fields
- Eager loading for relationships
- Query optimization with scopes

### 2. **API**
- Resource transformations for consistent output
- Pagination for large datasets
- Caching for frequently accessed data

### 3. **Code Organization**
- Service layer for business logic
- Repository pattern for data access
- Clean separation of concerns

## 📝 Best Practices Implemented

1. **RESTful API Design**: Following REST conventions for all endpoints
2. **Consistent Naming**: StudlyCase for models, camelCase for variables, snake_case for database
3. **Error Handling**: Comprehensive error handling with meaningful messages
4. **Code Reusability**: Service classes for business logic, traits for common functionality
5. **Documentation**: Comprehensive inline documentation and API documentation
6. **Testing Ready**: Structure supports unit and feature testing
7. **Scalability**: Modular design allows easy addition of new features

## 🔄 Migration from Old Structure

### Automated Migration Steps
1. **Backup existing code**: Create backup of current modular structure
2. **Run migration script**: Execute provided migration commands
3. **Update imports**: Update all namespace imports in existing code
4. **Test functionality**: Verify all endpoints work correctly
5. **Update frontend**: Update API calls to use new versioned endpoints

### Manual Steps Required
1. **Update environment variables**: Ensure .env file is properly configured
2. **Run database migrations**: Execute new migration files
3. **Update deployment scripts**: Modify deployment to use new structure
4. **Update documentation**: Update any project-specific documentation

## 🆘 Troubleshooting

### Common Issues
1. **Namespace errors**: Ensure all imports use new namespaces
2. **Route not found**: Check route definitions in api.php
3. **Database errors**: Run migrations and check database connection
4. **Authentication issues**: Verify Sanctum configuration

### Debug Commands
```bash
# Clear all caches
php artisan optimize:clear

# Check routes
php artisan route:list

# Check database status
php artisan migrate:status

# Generate application key
php artisan key:generate
```

This restructured Laravel application now follows standard Laravel conventions while maintaining all the functionality of the original modular system, with improved maintainability, scalability, and developer experience.