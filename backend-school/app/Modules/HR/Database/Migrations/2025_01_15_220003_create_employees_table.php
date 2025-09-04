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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('school_id');
            $table->string('employee_id')->unique();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'temporary', 'intern']);
            $table->enum('employment_status', ['active', 'inactive', 'terminated', 'on_leave', 'suspended']);
            $table->string('work_schedule')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('phone');
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');
            $table->text('address');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('national_id')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->json('qualifications')->nullable();
            $table->json('certifications')->nullable();
            $table->json('skills')->nullable();
            $table->text('notes')->nullable();
            $table->text('status_change_reason')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};