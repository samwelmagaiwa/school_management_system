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
        Schema::create('class_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('academic_year_id');
            
            // Subject-specific configuration for this class
            $table->integer('periods_per_week')->default(5);
            $table->integer('total_periods')->nullable();
            $table->boolean('is_compulsory')->default(true);
            $table->integer('weightage')->default(100); // For grade calculation
            
            // Assessment configuration
            $table->integer('max_marks')->nullable(); // Override subject default
            $table->integer('pass_marks')->nullable(); // Override subject default
            $table->boolean('include_in_result')->default(true);
            $table->boolean('include_in_grade')->default(true);
            
            // Schedule information
            $table->time('preferred_start_time')->nullable();
            $table->time('preferred_end_time')->nullable();
            $table->json('preferred_days')->nullable(); // Array of preferred days
            
            $table->timestamps();
            
            $table->unique(['class_id', 'subject_id', 'academic_year_id'], 'class_subject_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_subjects');
    }
};