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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('school_id');
            $table->string('payroll_number')->unique();
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->date('pay_date')->nullable();
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('gross_salary', 10, 2);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2);
            $table->decimal('hours_worked', 6, 2)->nullable();
            $table->decimal('overtime_hours', 6, 2)->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->enum('payment_method', ['bank_transfer', 'cash', 'cheque'])->default('bank_transfer');
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};