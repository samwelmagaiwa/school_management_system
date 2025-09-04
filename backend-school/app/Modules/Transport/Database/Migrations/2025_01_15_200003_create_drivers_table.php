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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->nullable()->constrained()->onDelete('set null');
            
            // Personal Information
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            
            // Identification Documents
            $table->string('aadhar_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('voter_id')->nullable();
            $table->string('passport_number')->nullable();
            
            // License Information
            $table->string('license_number')->unique();
            $table->enum('license_type', ['light_motor_vehicle', 'heavy_motor_vehicle', 'transport_vehicle', 'all'])->default('transport_vehicle');
            $table->date('license_issue_date');
            $table->date('license_expiry_date');
            $table->string('license_issuing_authority')->nullable();
            $table->json('license_categories')->nullable(); // Array of vehicle categories allowed
            
            // Professional Information
            $table->date('joining_date');
            $table->integer('experience_years');
            $table->decimal('salary', 8, 2)->nullable();
            $table->enum('employment_type', ['permanent', 'temporary', 'contract', 'part_time'])->default('permanent');
            $table->text('previous_experience')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');
            $table->string('emergency_contact_relation');
            
            // Health and Safety
            $table->string('blood_group')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->boolean('has_medical_certificate')->default(false);
            $table->date('medical_certificate_expiry')->nullable();
            $table->boolean('has_police_verification')->default(false);
            $table->date('police_verification_date')->nullable();
            
            // Training and Certifications
            $table->json('training_certificates')->nullable(); // Array of training details
            $table->date('last_training_date')->nullable();
            $table->date('next_training_due')->nullable();
            $table->boolean('defensive_driving_certified')->default(false);
            $table->boolean('first_aid_certified')->default(false);
            
            // Performance and Behavior
            $table->integer('total_violations')->default(0);
            $table->integer('total_accidents')->default(0);
            $table->decimal('performance_rating', 3, 2)->default(5.00); // Out of 5
            $table->date('last_violation_date')->nullable();
            $table->date('last_accident_date')->nullable();
            
            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc_code')->nullable();
            
            // Status and Availability
            $table->enum('status', ['active', 'inactive', 'on_leave', 'suspended', 'terminated'])->default('active');
            $table->text('status_reason')->nullable();
            $table->date('status_change_date')->nullable();
            $table->boolean('is_available')->default(true);
            $table->json('working_days')->nullable(); // Array of working days
            $table->time('shift_start_time')->nullable();
            $table->time('shift_end_time')->nullable();
            
            // Additional Information
            $table->text('skills')->nullable();
            $table->text('notes')->nullable();
            $table->json('documents')->nullable(); // Array of document file paths
            $table->string('photo')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['school_id', 'license_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};