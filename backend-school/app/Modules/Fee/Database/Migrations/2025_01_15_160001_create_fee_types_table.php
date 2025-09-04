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
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            
            // Fee Configuration
            $table->enum('frequency', ['one_time', 'monthly', 'quarterly', 'half_yearly', 'yearly'])->default('monthly');
            $table->enum('category', ['tuition', 'transport', 'library', 'laboratory', 'sports', 'examination', 'development', 'miscellaneous'])->default('tuition');
            $table->decimal('default_amount', 10, 2)->default(0);
            
            // Applicability
            $table->json('applicable_classes')->nullable(); // Array of class IDs or levels
            $table->json('applicable_streams')->nullable(); // Array of streams
            $table->boolean('is_compulsory')->default(true);
            $table->boolean('is_refundable')->default(false);
            
            // Payment Configuration
            $table->boolean('allow_partial_payment')->default(false);
            $table->integer('installments_allowed')->default(1);
            $table->decimal('late_fee_amount', 8, 2)->default(0);
            $table->integer('grace_period_days')->default(0);
            
            // Discount Configuration
            $table->boolean('discount_applicable')->default(true);
            $table->decimal('max_discount_percent', 5, 2)->default(0);
            $table->decimal('max_discount_amount', 10, 2)->default(0);
            
            // Status and Display
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
        Schema::dropIfExists('fee_types');
    }
};