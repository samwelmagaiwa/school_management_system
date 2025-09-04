<?php

namespace Database\Factories;

use App\Modules\User\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\User\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->optional()->address,
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'role' => $this->faker->randomElement(['Admin', 'Teacher', 'Student', 'Parent']),
            'school_id' => School::factory(),
            'profile_picture' => $this->faker->optional()->imageUrl(200, 200, 'people'),
            'status' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is a SuperAdmin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'SuperAdmin',
            'school_id' => null,
        ]);
    }

    /**
     * Indicate that the user is an Admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Admin',
        ]);
    }

    /**
     * Indicate that the user is a Teacher.
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Teacher',
        ]);
    }

    /**
     * Indicate that the user is a Student.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Student',
        ]);
    }

    /**
     * Indicate that the user is a Parent.
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Parent',
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}