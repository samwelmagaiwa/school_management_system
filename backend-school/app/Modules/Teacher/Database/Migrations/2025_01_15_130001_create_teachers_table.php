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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('school_id');
            $table->string('teacher_id')->unique();
            $table->string('employee_code')->nullable();
            
            // Personal Information
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('blood_group')->nullable();
            $table->string('nationality')->default('Indian');
            $table->string('religion')->nullable();
            $table->string('marital_status')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');
            $table->string('emergency_contact_relation');
            
            // Professional Information
            $table->date('joining_date');
            $table->string('designation');
            $table->string('department')->nullable();
            $table->string('specialization');
            $table->enum('employment_type', ['permanent', 'temporary', 'contract', 'part_time'])->default('permanent');
            $table->decimal('salary', 10, 2)->nullable();
            $table->integer('experience_years')->default(0);
            
            // Qualifications
            $table->json('qualifications'); // Array of qualifications
            $table->json('certifications')->nullable(); // Array of certifications
            $table->json('skills')->nullable(); // Array of skills
            
            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc_code')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('aadhar_number')->nullable();
            
            // Professional Details
            $table->text('teaching_experience')->nullable();
            $table->text('achievements')->nullable();
            $table->text('research_publications')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'on_leave', 'terminated'])->default('active');
            $table->date('status_date')->nullable();
            $table->text('status_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['school_id', 'teacher_id']);
            $table->unique(['school_id', 'employee_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};