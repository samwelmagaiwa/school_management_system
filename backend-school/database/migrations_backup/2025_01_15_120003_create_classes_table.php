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
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('grade');
            $table->string('section')->default('A');
            $table->integer('capacity')->default(40);
            $table->foreignId('class_teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('room_number')->nullable();
            $table->text('description')->nullable();
            $table->string('academic_year');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['school_id', 'status']);
            $table->index(['grade', 'section']);
            $table->index('academic_year');
            $table->unique(['school_id', 'grade', 'section', 'academic_year']);
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