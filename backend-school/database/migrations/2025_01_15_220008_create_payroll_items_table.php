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
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->enum('type', ['earning', 'deduction']);
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('quantity', 8, 2)->nullable();
            $table->decimal('rate', 8, 2)->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};