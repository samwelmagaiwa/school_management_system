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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('academic_year_id');
            
            // Attendance Details
            $table->date('attendance_date');
            $table->integer('period_number')->nullable(); // For period-wise attendance
            $table->time('period_start_time')->nullable();
            $table->time('period_end_time')->nullable();
            
            // Status
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'sick', 'excused'])->default('present');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->integer('late_minutes')->default(0);
            
            // Additional Information
            $table->text('remarks')->nullable();
            $table->text('absence_reason')->nullable();
            $table->boolean('is_excused')->default(false);
            $table->text('excuse_reason')->nullable();
            $table->unsignedBigInteger('excused_by')->nullable();
            
            // Entry Details
            $table->unsignedBigInteger('marked_by');
            $table->timestamp('marked_at')->nullable();
            $table->enum('entry_method', ['manual', 'biometric', 'rfid', 'mobile_app', 'bulk_import'])->default('manual');
            
            // Verification
            $table->boolean('is_verified')->default(false);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            // Modification Tracking
            $table->boolean('is_modified')->default(false);
            $table->json('modification_history')->nullable(); // Track changes
            $table->unsignedBigInteger('last_modified_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['student_id', 'attendance_date', 'period_number'], 'student_date_period_unique');
            $table->index(['class_id', 'attendance_date']);
            $table->index(['teacher_id', 'attendance_date']);
            $table->index(['status', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};