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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->unique()->nullable();
            $table->string('database_name')->nullable();
            
            // Status and Subscription
            $table->enum('status', ['pending', 'active', 'suspended', 'cancelled'])->default('pending');
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans');
            $table->enum('subscription_status', ['active', 'expired', 'cancelled', 'suspended'])->default('active');
            $table->timestamp('subscription_expires_at')->nullable();
            
            // Billing Information
            $table->string('billing_email')->nullable();
            $table->text('billing_address')->nullable();
            
            // Contact Information
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            
            // Configuration and Features
            $table->json('settings')->nullable();
            $table->json('features_enabled')->nullable();
            
            // Usage Limits and Tracking
            $table->bigInteger('storage_used')->default(0); // in bytes
            $table->bigInteger('storage_limit')->default(5368709120); // 5GB default
            $table->integer('users_limit')->default(100);
            
            // Trial Information
            $table->boolean('is_trial')->default(false);
            $table->timestamp('trial_expires_at')->nullable();
            
            // Activity Tracking
            $table->timestamp('last_activity_at')->nullable();
            
            // Approval Tracking
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'subscription_status']);
            $table->index(['is_trial', 'trial_expires_at']);
            $table->index(['subscription_expires_at']);
            $table->index(['created_at']);
            $table->index(['last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};