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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            
            // Subject Classification
            $table->enum('type', ['core', 'elective', 'optional', 'extra_curricular'])->default('core');
            $table->enum('category', ['theory', 'practical', 'both'])->default('theory');
            $table->string('department')->nullable();
            
            // Academic Information
            $table->integer('credits')->default(1);
            $table->integer('theory_hours')->default(0);
            $table->integer('practical_hours')->default(0);
            $table->integer('total_hours')->default(0);
            $table->json('applicable_classes'); // Array of class levels [1,2,3,4,5,6,7,8,9,10,11,12]
            
            // Assessment Configuration
            $table->integer('max_marks')->default(100);
            $table->integer('pass_marks')->default(35);
            $table->boolean('has_practical')->default(false);
            $table->integer('practical_marks')->default(0);
            $table->boolean('has_internal_assessment')->default(false);
            $table->integer('internal_marks')->default(0);
            
            // Prerequisites and Requirements
            $table->json('prerequisites')->nullable(); // Array of subject IDs
            $table->text('learning_objectives')->nullable();
            $table->text('syllabus_outline')->nullable();
            $table->text('reference_books')->nullable();
            
            // Status and Metadata
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['school_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};