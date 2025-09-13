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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->integer('days_per_year');
            $table->integer('max_consecutive_days')->nullable();
            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_documentation')->default(false);
            $table->boolean('is_paid')->default(true);
            $table->boolean('carry_forward_allowed')->default(false);
            $table->integer('max_carry_forward_days')->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['school_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};