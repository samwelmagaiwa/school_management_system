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
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            
            // Schedule Details
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            
            // Venue Information
            $table->string('room_number')->nullable();
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            $table->integer('seating_capacity')->nullable();
            
            // Exam Configuration
            $table->integer('total_marks');
            $table->integer('pass_marks');
            $table->enum('exam_type', ['theory', 'practical', 'oral', 'online'])->default('theory');
            
            // Supervision
            $table->json('invigilators')->nullable(); // Array of teacher IDs
            $table->foreignId('chief_invigilator')->nullable()->constrained('teachers')->onDelete('set null');
            
            // Instructions and Notes
            $table->text('special_instructions')->nullable();
            $table->text('materials_allowed')->nullable();
            $table->text('materials_prohibited')->nullable();
            
            // Status
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->text('status_reason')->nullable();
            
            $table->timestamps();
            
            $table->unique(['exam_id', 'class_id', 'subject_id'], 'exam_class_subject_unique');
            $table->index(['exam_date', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_schedules');
    }
};