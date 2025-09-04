<?php

namespace Database\Factories;

use App\Modules\Student\Models\Student;
use App\Modules\User\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Student\Models\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $school = School::factory()->create();
        
        return [
            'user_id' => User::factory()->student()->create(['school_id' => $school->id]),
            'school_id' => $school->id,
            'student_id' => $this->generateStudentId(),
            'roll_number' => $this->faker->numberBetween(1, 50),
            'class_id' => null, // Would be set based on available classes
            'section' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'admission_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'admission_number' => $this->generateAdmissionNumber($school->code),
            'admission_type' => $this->faker->randomElement(['new', 'transfer', 'readmission']),
            'date_of_birth' => $this->faker->dateTimeBetween('-18 years', '-5 years'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'blood_group' => $this->faker->optional()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'nationality' => 'Indian',
            'religion' => $this->faker->optional()->randomElement(['Hindu', 'Muslim', 'Christian', 'Sikh', 'Buddhist', 'Jain', 'Other']),
            'caste' => $this->faker->optional()->word,
            'category' => $this->faker->randomElement(['General', 'OBC', 'SC', 'ST']),
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'postal_code' => $this->faker->postcode,
            'phone' => $this->faker->optional()->phoneNumber,
            'father_name' => $this->faker->name('male'),
            'father_occupation' => $this->faker->optional()->jobTitle,
            'father_phone' => $this->faker->optional()->phoneNumber,
            'father_email' => $this->faker->optional()->safeEmail,
            'mother_name' => $this->faker->name('female'),
            'mother_occupation' => $this->faker->optional()->jobTitle,
            'mother_phone' => $this->faker->optional()->phoneNumber,
            'mother_email' => $this->faker->optional()->safeEmail,
            'guardian_name' => $this->faker->optional()->name,
            'guardian_relation' => $this->faker->optional()->randomElement(['Uncle', 'Aunt', 'Grandparent', 'Other']),
            'guardian_phone' => $this->faker->optional()->phoneNumber,
            'guardian_email' => $this->faker->optional()->safeEmail,
            'emergency_contact_name' => $this->faker->name,
            'emergency_contact_phone' => $this->faker->phoneNumber,
            'emergency_contact_relation' => $this->faker->randomElement(['Father', 'Mother', 'Guardian', 'Uncle', 'Aunt']),
            'previous_school' => $this->faker->optional()->company . ' School',
            'previous_class' => $this->faker->optional()->randomElement(['1st', '2nd', '3rd', '4th', '5th']),
            'previous_percentage' => $this->faker->optional()->randomFloat(2, 60, 95),
            'medical_conditions' => $this->faker->optional()->sentence,
            'allergies' => $this->faker->optional()->sentence,
            'special_needs' => $this->faker->optional()->sentence,
            'uses_transport' => $this->faker->boolean(30),
            'vehicle_id' => null,
            'pickup_point' => $this->faker->optional()->address,
            'drop_point' => $this->faker->optional()->address,
            'status' => 'active',
            'status_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status_reason' => null,
        ];
    }

    /**
     * Generate student ID
     */
    private function generateStudentId(): string
    {
        return 'STU' . $this->faker->unique()->numberBetween(100000, 999999);
    }

    /**
     * Generate admission number
     */
    private function generateAdmissionNumber(string $schoolCode): string
    {
        $year = date('Y');
        $sequence = $this->faker->unique()->numberBetween(1, 9999);
        
        return $schoolCode . $year . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Indicate that the student is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the student requires transport.
     */
    public function withTransport(): static
    {
        return $this->state(fn (array $attributes) => [
            'uses_transport' => true,
        ]);
    }

    /**
     * Indicate that the student has medical conditions.
     */
    public function withMedicalConditions(): static
    {
        return $this->state(fn (array $attributes) => [
            'medical_conditions' => $this->faker->paragraph,
            'blood_group' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
        ]);
    }
}