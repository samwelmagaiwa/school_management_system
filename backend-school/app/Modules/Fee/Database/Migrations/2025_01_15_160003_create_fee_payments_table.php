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
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_id');
            $table->unsignedBigInteger('student_id');
            $table->string('payment_number')->unique();
            $table->string('receipt_number')->unique();
            
            // Payment Details
            $table->decimal('amount', 10, 2);
            $table->decimal('late_fee', 8, 2)->default(0);
            $table->decimal('total_paid', 10, 2);
            $table->date('payment_date');
            $table->timestamp('payment_time')->nullable();
            
            // Payment Method
            $table->enum('payment_method', ['cash', 'cheque', 'bank_transfer', 'online', 'card', 'upi', 'wallet'])->default('cash');
            $table->string('transaction_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            
            // Payment Gateway Details (for online payments)
            $table->string('gateway_name')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->enum('gateway_status', ['pending', 'success', 'failed', 'cancelled'])->nullable();
            
            // Collection Details
            $table->unsignedBigInteger('collected_by');
            $table->timestamp('collected_at')->nullable();
            $table->string('collection_center')->nullable();
            
            // Status and Verification
            $table->enum('status', ['pending', 'verified', 'cancelled', 'refunded'])->default('verified');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            // Refund Information
            $table->boolean('is_refunded')->default(false);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->date('refund_date')->nullable();
            $table->text('refund_reason')->nullable();
            $table->unsignedBigInteger('refunded_by')->nullable();
            
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['student_id', 'payment_date']);
            $table->index(['payment_method', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};