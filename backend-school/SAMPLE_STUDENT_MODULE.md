# Complete Student Module Example

This document provides a complete example of the Student module following Laravel best practices and standard structure.

## 1. Migration

**File:** `database/migrations/2025_01_15_120001_create_students_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->onDelete('set null');
            $table->string('admission_number')->unique();
            $table->string('roll_number')->nullable();
            $table->string('section')->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('blood_group')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('parent_name');
            $table->string('parent_phone');
            $table->string('parent_email')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('admission_date');
            $table->json('medical_info')->nullable();
            $table->json('emergency_contacts')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'class_id']);
            $table->index(['admission_number']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
```

## 2. Model

**File:** `app/Models/Student.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\StudentFactory;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return StudentFactory::new();
    }

    protected $fillable = [
        'user_id',
        'school_id',
        'class_id',
        'admission_number',
        'roll_number',
        'section',
        'date_of_birth',
        'gender',
        'blood_group',
        'phone',
        'address',
        'parent_name',
        'parent_phone',
        'parent_email',
        'parent_id',
        'admission_date',
        'medical_info',
        'emergency_contacts',
        'status'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'medical_info' => 'array',
        'emergency_contacts' => 'array',
        'status' => 'boolean'
    ];

    protected $dates = ['deleted_at'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereHas('user', function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
        })->orWhere('admission_number', 'like', "%{$search}%")
          ->orWhere('roll_number', 'like', "%{$search}%");
    }

    // Helper methods
    public static function generateAdmissionNumber($schoolId)
    {
        $year = date('Y');
        $school = School::find($schoolId);
        $schoolCode = $school ? $school->code : 'SCH';
        
        // Get the last admission number for this school and year
        $lastStudent = static::where('school_id', $schoolId)
            ->where('admission_number', 'like', "{$schoolCode}{$year}%")
            ->orderBy('admission_number', 'desc')
            ->first();

        if ($lastStudent) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastStudent->admission_number, -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $schoolCode . $year . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->user ? $this->user->full_name : '';
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    // Get attendance percentage
    public function getAttendancePercentage($startDate = null, $endDate = null)
    {
        $query = $this->attendance();
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $totalDays = $query->count();
        $presentDays = $query->whereIn('status', ['present', 'late'])->count();
        
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    }
}
```

## 3. Form Request

**File:** `app/Http/Requests/StoreStudentRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // User information
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            
            // Student specific information
            'school_id' => ['required', 'exists:schools,id'],
            'class_id' => ['nullable', 'exists:school_classes,id'],
            'admission_number' => ['nullable', 'string', 'unique:students,admission_number'],
            'roll_number' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:10'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            
            // Parent information
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'max:20'],
            'parent_email' => ['nullable', 'email'],
            'parent_id' => ['nullable', 'exists:users,id'],
            
            // Additional information
            'admission_date' => ['required', 'date'],
            'medical_info' => ['nullable', 'array'],
            'emergency_contacts' => ['nullable', 'array'],
            'status' => ['boolean']
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'date_of_birth' => 'date of birth',
            'parent_name' => 'parent name',
            'parent_phone' => 'parent phone',
            'parent_email' => 'parent email',
            'admission_date' => 'admission date',
            'class_id' => 'class',
            'school_id' => 'school'
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A student with this email address already exists.',
            'admission_number.unique' => 'This admission number is already in use.',
            'date_of_birth.before' => 'Date of birth must be a date before today.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->status === null) {
            $this->merge(['status' => true]);
        }
        
        // Generate admission number if not provided
        if (!$this->admission_number && $this->school_id) {
            $this->merge([
                'admission_number' => \App\Models\Student::generateAdmissionNumber($this->school_id)
            ]);
        }
    }
}
```

**File:** `app/Http/Requests/UpdateStudentRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $studentId = $this->route('student')->id;
        $userId = $this->route('student')->user_id;

        return [
            // User information
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($userId)],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            
            // Student specific information
            'school_id' => ['sometimes', 'exists:schools,id'],
            'class_id' => ['nullable', 'exists:school_classes,id'],
            'admission_number' => ['sometimes', 'string', Rule::unique('students')->ignore($studentId)],
            'roll_number' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:10'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'gender' => ['sometimes', Rule::in(['male', 'female', 'other'])],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            
            // Parent information
            'parent_name' => ['sometimes', 'string', 'max:255'],
            'parent_phone' => ['sometimes', 'string', 'max:20'],
            'parent_email' => ['nullable', 'email'],
            'parent_id' => ['nullable', 'exists:users,id'],
            
            // Additional information
            'admission_date' => ['sometimes', 'date'],
            'medical_info' => ['nullable', 'array'],
            'emergency_contacts' => ['nullable', 'array'],
            'status' => ['boolean']
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'date_of_birth' => 'date of birth',
            'parent_name' => 'parent name',
            'parent_phone' => 'parent phone',
            'parent_email' => 'parent email',
            'admission_date' => 'admission date',
            'class_id' => 'class',
            'school_id' => 'school'
        ];
    }
}
```

## 4. Controller

**File:** `app/Http/Controllers/Api/V1/StudentController.php`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->middleware('auth:sanctum');
        $this->studentService = $studentService;
    }

    /**
     * Display a listing of students.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);
        
        $students = $this->studentService->getAllStudents($request->all());

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Store a newly created student.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $this->authorize('create', Student::class);
        
        try {
            DB::beginTransaction();
            
            $student = $this->studentService->createStudent($request->validated());
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student created successfully',
                'data' => $student
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);
        
        $student->load(['user', 'school', 'schoolClass', 'parent']);
        
        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    /**
     * Update the specified student.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);
        
        try {
            DB::beginTransaction();
            
            $student = $this->studentService->updateStudent($student, $request->validated());
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
                'data' => $student
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Student $student): JsonResponse
    {
        $this->authorize('delete', $student);
        
        try {
            DB::beginTransaction();
            
            $this->studentService->deleteStudent($student);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete student',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

## 5. Route Entry

**File:** `routes/api.php` (relevant section)

```php
// Students - Full CRUD with apiResource
Route::apiResource('students', StudentController::class)->names([
    'index' => 'students.index',
    'store' => 'students.store',
    'show' => 'students.show',
    'update' => 'students.update',
    'destroy' => 'students.destroy',
]);

Route::prefix('students')->name('students.')->group(function () {
    Route::get('statistics', [StudentController::class, 'statistics'])->name('statistics');
    Route::post('{student}/promote', [StudentController::class, 'promote'])->name('promote');
    Route::post('{student}/transfer', [StudentController::class, 'transfer'])->name('transfer');
    Route::post('bulk-status', [StudentController::class, 'bulkUpdateStatus'])->name('bulk-status');
    Route::get('export', [StudentController::class, 'export'])->name('export');
});
```

## API Endpoints

The Student module provides the following API endpoints:

### CRUD Operations
- `GET /api/v1/students` - List all students
- `POST /api/v1/students` - Create a new student
- `GET /api/v1/students/{id}` - Get a specific student
- `PUT/PATCH /api/v1/students/{id}` - Update a student
- `DELETE /api/v1/students/{id}` - Delete a student

### Additional Operations
- `GET /api/v1/students/statistics` - Get student statistics
- `POST /api/v1/students/{id}/promote` - Promote student to next class
- `POST /api/v1/students/{id}/transfer` - Transfer student to different school
- `POST /api/v1/students/bulk-status` - Bulk update student status
- `GET /api/v1/students/export` - Export students data

## Key Features

1. **Laravel Best Practices**: Follows all Laravel conventions and best practices
2. **Thin Controllers**: Controllers are kept thin with business logic in services
3. **Form Requests**: Proper validation using Form Request classes
4. **Resource Routes**: Uses apiResource for standard CRUD operations
5. **Sanctum Authentication**: Secure API authentication
6. **Proper Relationships**: Well-defined Eloquent relationships
7. **Scopes and Accessors**: Useful query scopes and model accessors
8. **Error Handling**: Comprehensive error handling with transactions
9. **Standard Structure**: Follows standard Laravel directory structure

This example can be replicated for all other modules (Teachers, Schools, Classes, etc.) following the same patterns and conventions.
