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
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->boolean('is_primary')->default(false); // Primary subject teacher
            $table->integer('periods_per_week')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['teacher_id', 'subject_id', 'class_id', 'academic_year_id'], 'teacher_subject_class_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_subjects');
    }
};