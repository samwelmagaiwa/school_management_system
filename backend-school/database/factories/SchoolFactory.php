<?php

namespace Database\Factories;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\School\Models\School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' School',
            'code' => strtoupper($this->faker->unique()->lexify('SCH???')),
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'website' => $this->faker->optional()->url,
            'logo' => $this->faker->optional()->imageUrl(200, 200, 'business'),
            'established_year' => $this->faker->numberBetween(1950, date('Y') - 1),
            'principal_name' => $this->faker->name,
            'principal_email' => $this->faker->optional()->safeEmail,
            'principal_phone' => $this->faker->optional()->phoneNumber,
            'description' => $this->faker->optional()->paragraph,
            'board_affiliation' => $this->faker->randomElement(['CBSE', 'ICSE', 'State Board', 'IB']),
            'school_type' => $this->faker->randomElement(['primary', 'secondary', 'higher_secondary', 'all']),
            'registration_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{8}'),
            'tax_id' => $this->faker->optional()->regexify('[0-9]{11}'),
            'settings' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the school is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}