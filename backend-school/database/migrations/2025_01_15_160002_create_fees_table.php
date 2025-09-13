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
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('fee_type_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->string('fee_number')->unique();
            
            // Amount Details
            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('late_fee', 8, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_amount', 10, 2);
            
            // Due Dates
            $table->date('due_date');
            $table->date('late_fee_applicable_from')->nullable();
            
            // Payment Status
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('paid_date')->nullable();
            $table->timestamp('last_payment_date')->nullable();
            
            // Discount Information
            $table->string('discount_type')->nullable(); // scholarship, sibling, early_bird, etc.
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->text('discount_reason')->nullable();
            $table->unsignedBigInteger('discount_approved_by')->nullable();
            
            // Additional Information
            $table->text('remarks')->nullable();
            $table->json('payment_history')->nullable(); // Array of payment records
            $table->boolean('is_refunded')->default(false);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->date('refund_date')->nullable();
            $table->text('refund_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['due_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};