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
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('academic_year_id');
            $table->integer('month');
            $table->integer('year');
            
            // Summary Statistics
            $table->integer('total_working_days');
            $table->integer('total_present_days');
            $table->integer('total_absent_days');
            $table->integer('total_late_days');
            $table->integer('total_half_days');
            $table->integer('total_sick_days');
            $table->integer('total_excused_days');
            
            // Calculated Metrics
            $table->decimal('attendance_percentage', 5, 2);
            $table->decimal('punctuality_percentage', 5, 2)->nullable();
            $table->integer('consecutive_absent_days')->default(0);
            $table->integer('total_late_minutes')->default(0);
            
            // Status Flags
            $table->boolean('is_below_minimum')->default(false); // Below required attendance %
            $table->decimal('minimum_required_percentage', 5, 2)->default(75.00);
            $table->boolean('alert_sent')->default(false);
            $table->date('alert_sent_date')->nullable();
            
            // Additional Information
            $table->text('remarks')->nullable();
            $table->json('weekly_breakdown')->nullable(); // Week-wise attendance data
            
            // Calculation Details
            $table->timestamp('calculated_at')->nullable();
            $table->unsignedBigInteger('calculated_by')->nullable();
            
            $table->timestamps();
            
            $table->unique(['student_id', 'subject_id', 'month', 'year', 'academic_year_id'], 'student_subject_month_year_unique');
            $table->index(['attendance_percentage', 'is_below_minimum'], 'attendance_percentage_below_min_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
    }
};