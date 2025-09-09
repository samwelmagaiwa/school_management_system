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
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('domain')->unique()->nullable();
            $table->string('subdomain')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('US');
            $table->string('postal_code')->nullable();
            $table->string('logo')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'cancelled'])->default('pending');
            $table->boolean('is_trial')->default(false);
            $table->date('trial_ends_at')->nullable();
            $table->unsignedBigInteger('subscription_plan_id')->nullable();
            $table->datetime('subscription_starts_at')->nullable();
            $table->datetime('subscription_ends_at')->nullable();
            $table->enum('subscription_status', ['active', 'expired', 'cancelled', 'suspended'])->default('active');
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Note: Foreign key constraints will be added after all tables are created
            $table->index(['status']);
            $table->index(['subscription_plan_id']);
            $table->index(['created_by']);
            $table->index(['subscription_status']);
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
