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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            
            // Exam Configuration
            $table->enum('type', ['unit_test', 'mid_term', 'final', 'quarterly', 'half_yearly', 'annual', 'entrance', 'competitive'])->default('unit_test');
            $table->enum('pattern', ['written', 'oral', 'practical', 'online', 'mixed'])->default('written');
            $table->integer('duration_minutes')->default(180); // 3 hours default
            
            // Scheduling
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            
            // Applicability
            $table->json('applicable_classes'); // Array of class IDs
            $table->json('applicable_subjects')->nullable(); // Array of subject IDs, null means all subjects
            
            // Grading Configuration
            $table->integer('total_marks')->default(100);
            $table->integer('pass_marks')->default(35);
            $table->enum('grading_system', ['marks', 'grades', 'both'])->default('marks');
            $table->json('grade_scale')->nullable(); // Grade boundaries
            
            // Result Configuration
            $table->boolean('include_in_final_result')->default(true);
            $table->decimal('weightage_percentage', 5, 2)->default(100.00);
            $table->boolean('show_result_to_students')->default(false);
            $table->boolean('show_result_to_parents')->default(false);
            $table->date('result_publish_date')->nullable();
            
            // Status and Workflow
            $table->enum('status', ['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'])->default('draft');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            
            // Additional Information
            $table->text('instructions')->nullable();
            $table->text('syllabus_covered')->nullable();
            $table->json('exam_centers')->nullable(); // Array of exam centers
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['school_id', 'code', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};