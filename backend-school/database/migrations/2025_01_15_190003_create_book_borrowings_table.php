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
        Schema::create('book_borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('borrower_type'); // student, teacher, staff
            $table->string('borrower_id'); // student_id or teacher_id or staff_id
            
            // Issue Details
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->time('issue_time');
            $table->time('return_time')->nullable();
            
            // Staff Details
            $table->foreignId('issued_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('returned_to')->nullable()->constrained('users')->onDelete('set null');
            
            // Status and Conditions
            $table->enum('status', ['issued', 'returned', 'overdue', 'lost', 'damaged', 'renewed'])->default('issued');
            $table->integer('renewal_count')->default(0);
            $table->integer('overdue_days')->default(0);
            
            // Fine and Penalties
            $table->decimal('fine_amount', 8, 2)->default(0);
            $table->decimal('fine_paid', 8, 2)->default(0);
            $table->decimal('fine_waived', 8, 2)->default(0);
            $table->decimal('fine_pending', 8, 2)->default(0);
            $table->boolean('fine_paid_status')->default(false);
            $table->date('fine_paid_date')->nullable();
            
            // Condition Assessment
            $table->enum('issue_condition', ['excellent', 'good', 'fair', 'poor'])->default('good');
            $table->enum('return_condition', ['excellent', 'good', 'fair', 'poor', 'damaged', 'lost'])->nullable();
            $table->text('issue_notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->text('damage_description')->nullable();
            $table->decimal('damage_charge', 8, 2)->default(0);
            
            // Renewal History
            $table->json('renewal_history')->nullable(); // Array of renewal dates and due dates
            $table->date('last_renewal_date')->nullable();
            
            // Reservation Information
            $table->boolean('was_reserved')->default(false);
            $table->date('reservation_date')->nullable();
            $table->integer('reservation_queue_position')->nullable();
            
            // Reminders and Notifications
            $table->integer('reminder_count')->default(0);
            $table->date('last_reminder_date')->nullable();
            $table->json('reminder_history')->nullable(); // Array of reminder dates and types
            
            // Digital Access (for ebooks)
            $table->boolean('digital_access_granted')->default(false);
            $table->timestamp('digital_access_start')->nullable();
            $table->timestamp('digital_access_end')->nullable();
            $table->integer('digital_download_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['borrower_type', 'borrower_id']);
            $table->index(['status', 'due_date']);
            $table->index(['issue_date', 'return_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_borrowings');
    }
};