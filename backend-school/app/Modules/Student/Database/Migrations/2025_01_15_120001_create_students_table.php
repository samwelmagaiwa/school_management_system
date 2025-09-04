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
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('school_id');
            $table->string('student_id')->unique();
            $table->string('roll_number')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('section')->nullable();
            $table->date('admission_date');
            $table->string('admission_number')->nullable();
            $table->enum('admission_type', ['new', 'transfer', 'readmission'])->default('new');
            
            // Personal Information
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('blood_group')->nullable();
            $table->string('nationality')->default('Indian');
            $table->string('religion')->nullable();
            $table->string('caste')->nullable();
            $table->string('category')->nullable(); // General, OBC, SC, ST, etc.
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('phone')->nullable();
            
            // Parent/Guardian Information
            $table->string('father_name');
            $table->string('father_occupation')->nullable();
            $table->string('father_phone')->nullable();
            $table->string('father_email')->nullable();
            $table->string('mother_name');
            $table->string('mother_occupation')->nullable();
            $table->string('mother_phone')->nullable();
            $table->string('mother_email')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_email')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');
            $table->string('emergency_contact_relation');
            
            // Academic Information
            $table->string('previous_school')->nullable();
            $table->string('previous_class')->nullable();
            $table->decimal('previous_percentage', 5, 2)->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->text('special_needs')->nullable();
            
            // Transport Information
            $table->boolean('uses_transport')->default(false);
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('pickup_point')->nullable();
            $table->string('drop_point')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'transferred', 'graduated', 'dropped'])->default('active');
            $table->date('status_date')->nullable();
            $table->text('status_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['school_id', 'student_id']);
            $table->unique(['school_id', 'admission_number']);
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