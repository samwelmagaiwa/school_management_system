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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('setup_fee', 10, 2)->default(0);
            
            // Plan Configuration
            $table->json('features')->nullable(); // List of included features
            $table->json('limits')->nullable(); // Custom limits and restrictions
            $table->json('modules_included')->nullable(); // Available modules
            
            // Plan Attributes
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('trial_days')->default(14);
            $table->enum('billing_cycle', ['monthly', 'yearly', 'lifetime'])->default('monthly');
            
            // Limits
            $table->integer('max_schools')->default(1); // -1 for unlimited
            $table->integer('max_users')->default(100); // -1 for unlimited
            $table->integer('max_storage_gb')->default(5); // -1 for unlimited
            
            // Support and Features
            $table->enum('support_level', ['basic', 'standard', 'premium', 'enterprise'])->default('basic');
            $table->boolean('custom_branding')->default(false);
            $table->boolean('api_access')->default(false);
            $table->enum('backup_frequency', ['none', 'weekly', 'daily', 'real-time'])->default('weekly');
            
            // Display Order
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_active', 'sort_order']);
            $table->index(['is_popular']);
            $table->index(['billing_cycle']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};