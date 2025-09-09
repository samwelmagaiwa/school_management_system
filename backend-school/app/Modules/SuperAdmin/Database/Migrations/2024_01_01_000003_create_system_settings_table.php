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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->enum('type', [
                'string', 'integer', 'boolean', 'array', 'json', 
                'file', 'color', 'email', 'url', 'select', 'multiselect'
            ])->default('string');
            $table->enum('category', [
                'general', 'branding', 'security', 'email', 'sms', 
                'payment', 'academic', 'features', 'limits', 'integrations'
            ])->default('general');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Can be accessed by frontend
            $table->json('validation_rules')->nullable(); // Validation rules for the setting
            $table->json('options')->nullable(); // For select/multiselect types
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['category', 'sort_order']);
            $table->index(['is_public']);
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};