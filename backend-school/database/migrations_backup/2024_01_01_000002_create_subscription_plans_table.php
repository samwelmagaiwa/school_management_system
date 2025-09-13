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
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->integer('max_users')->default(0); // 0 = unlimited
            $table->integer('max_students')->default(0); // 0 = unlimited
            $table->integer('max_storage_gb')->default(1); // in GB
            $table->json('features')->nullable(); // Available features
            $table->json('limits')->nullable(); // Various limits
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('trial_days')->default(30);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active']);
            $table->index(['is_popular']);
            $table->index(['sort_order']);
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
