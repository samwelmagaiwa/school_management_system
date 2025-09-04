<?php

namespace Tests\Feature;

use App\Modules\User\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test school
        $this->school = School::factory()->create();
        
        // Create test users
        $this->superAdmin = User::factory()->create([
            'role' => 'SuperAdmin',
            'school_id' => null,
        ]);
        
        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->superAdmin->email,
            'password' => 'password', // Default factory password
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'role',
                        ],
                        'token',
                    ],
                ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->superAdmin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_get_profile()
    {
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                    ],
                ]);
    }

    public function test_user_can_logout()
    {
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Logout successful',
                ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }
}