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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address');
            $table->string('phone');
            $table->string('email')->unique();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->integer('established_year');
            $table->string('principal_name');
            $table->string('principal_email')->nullable();
            $table->string('principal_phone')->nullable();
            $table->text('description')->nullable();
            $table->string('board_affiliation')->nullable(); // CBSE, ICSE, State Board, etc.
            $table->enum('school_type', ['primary', 'secondary', 'higher_secondary', 'all'])->default('all');
            $table->string('registration_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->json('settings')->nullable(); // School-specific settings
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
        Schema::dropIfExists('schools');
    }
};