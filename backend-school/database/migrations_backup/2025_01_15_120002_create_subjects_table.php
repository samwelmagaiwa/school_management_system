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
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->integer('grade_level')->nullable();
            $table->integer('credits')->default(1);
            $table->enum('subject_type', ['core', 'elective', 'extra_curricular'])->default('core');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['school_id', 'status']);
            $table->index('grade_level');
            $table->index('subject_type');
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