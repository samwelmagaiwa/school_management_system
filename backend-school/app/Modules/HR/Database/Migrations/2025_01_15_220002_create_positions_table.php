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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->string('title');
            $table->string('code');
            $table->text('description')->nullable();
            $table->enum('level', ['entry', 'junior', 'senior', 'lead', 'manager', 'director', 'executive']);
            $table->decimal('min_salary', 10, 2)->nullable();
            $table->decimal('max_salary', 10, 2)->nullable();
            $table->json('required_qualifications')->nullable();
            $table->json('required_skills')->nullable();
            $table->json('responsibilities')->nullable();
            $table->unsignedBigInteger('reports_to_position_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};