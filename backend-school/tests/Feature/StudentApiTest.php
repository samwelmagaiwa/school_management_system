<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Modules\Student\Models\Student;
use App\Modules\School\Models\School;
use Laravel\Sanctum\Sanctum;

class StudentApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test school
        $this->school = School::factory()->create();
        
        // Create test admin user
        $this->adminUser = User::factory()->create([
            'role' => 'Admin',
            'school_id' => $this->school->id
        ]);
        
        // Create test super admin user  
        $this->superAdminUser = User::factory()->create([
            'role' => 'SuperAdmin',
            'school_id' => null
        ]);
    }

    /** @test */
    public function admin_can_view_students_list()
    {
        Sanctum::actingAs($this->adminUser);
        
        // Create some test students
        Student::factory()->count(3)->create(['school_id' => $this->school->id]);
        
        $response = $this->getJson('/api/api/students');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data',
                        'links',
                        'meta'
                    ]
                ])
                ->assertJson(['success' => true]);
    }

    /** @test */
    public function admin_can_create_student()
    {
        Sanctum::actingAs($this->adminUser);
        
        $studentData = [
            'school_id' => $this->school->id,
            'student_id' => 'STU000123',
            'admission_date' => now()->format('Y-m-d'),
            'admission_number' => 'ADM2025001',
            'admission_type' => 'new',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'blood_group' => 'O+',
            'nationality' => 'Indian',
            'category' => 'General',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'state' => 'Test State',
            'postal_code' => '123456',
            'father_name' => 'John Doe Sr.',
            'father_phone' => '1234567890',
            'mother_name' => 'Jane Doe',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_phone' => '0987654321',
            'emergency_contact_relation' => 'Father',
            'status' => 'active',
            'user_data' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123'
            ]
        ];
        
        $response = $this->postJson('/api/api/students', $studentData);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ])
                ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'role' => 'Student'
        ]);
        
        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->id,
            'father_name' => 'John Doe Sr.'
        ]);
    }

    /** @test */
    public function super_admin_can_access_all_students()
    {
        Sanctum::actingAs($this->superAdminUser);
        
        // Create students in different schools
        $otherSchool = School::factory()->create();
        Student::factory()->create(['school_id' => $this->school->id]);
        Student::factory()->create(['school_id' => $otherSchool->id]);
        
        $response = $this->getJson('/api/api/students');
        
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
                
        // Should be able to see students from all schools
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function unauthorized_user_cannot_access_students()
    {
        $unauthorizedUser = User::factory()->create(['role' => 'Student']);
        Sanctum::actingAs($unauthorizedUser);
        
        $response = $this->getJson('/api/api/students');
        
        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]);
    }

    /** @test */
    public function admin_can_export_students()
    {
        Sanctum::actingAs($this->adminUser);
        
        $response = $this->getJson('/api/api/students/export?format=csv');
        
        $response->assertStatus(200);
        $this->assertEquals('application/octet-stream', $response->headers->get('content-type'));
    }

    /** @test */
    public function admin_can_get_student_statistics()
    {
        Sanctum::actingAs($this->adminUser);
        
        Student::factory()->count(5)->create(['school_id' => $this->school->id]);
        
        $response = $this->getJson('/api/api/students/statistics/overview');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_students',
                        'active_students',
                        'inactive_students'
                    ]
                ])
                ->assertJson(['success' => true]);
    }

    /** @test */
    public function validation_errors_on_invalid_student_data()
    {
        Sanctum::actingAs($this->adminUser);
        
        $invalidData = [
            'school_id' => 999, // Non-existent school
            'user_data' => [
                'email' => 'invalid-email', // Invalid email
                'first_name' => '', // Required field
            ]
        ];
        
        $response = $this->postJson('/api/api/students', $invalidData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'school_id',
                    'user_data.email',
                    'user_data.first_name'
                ]);
    }

    /** @test */
    public function admin_can_update_student()
    {
        Sanctum::actingAs($this->adminUser);
        
        $student = Student::factory()->create(['school_id' => $this->school->id]);
        
        $updateData = [
            'father_name' => 'Updated Father Name',
            'father_phone' => '9876543210'
        ];
        
        $response = $this->putJson("/api/api/students/{$student->id}", $updateData);
        
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'father_name' => 'Updated Father Name',
            'father_phone' => '9876543210'
        ]);
    }

    /** @test */
    public function admin_can_delete_student()
    {
        Sanctum::actingAs($this->adminUser);
        
        $student = Student::factory()->create(['school_id' => $this->school->id]);
        
        $response = $this->deleteJson("/api/api/students/{$student->id}");
        
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
        
        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }
}
