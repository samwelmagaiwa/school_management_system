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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name'); // e.g., "Class 10"
            $table->string('section'); // e.g., "A", "B", "C"
            $table->string('class_code')->nullable(); // e.g., "10A"
            $table->integer('grade_level'); // 1, 2, 3, ... 12
            $table->unsignedBigInteger('class_teacher_id')->nullable();
            $table->unsignedBigInteger('academic_year_id');
            
            // Capacity and Room Information
            $table->integer('capacity')->default(40);
            $table->integer('current_strength')->default(0);
            $table->string('room_number')->nullable();
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            
            // Schedule Information
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('working_days')->nullable(); // Array of working days
            
            // Academic Information
            $table->enum('stream', ['science', 'commerce', 'arts', 'general'])->nullable(); // For higher classes
            $table->text('description')->nullable();
            $table->json('subjects')->nullable(); // Array of subject IDs
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['school_id', 'name', 'section', 'academic_year_id'], 'school_class_section_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};