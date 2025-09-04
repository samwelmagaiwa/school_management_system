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
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            
            // Marks Details
            $table->decimal('marks_obtained', 6, 2);
            $table->decimal('total_marks', 6, 2);
            $table->decimal('percentage', 5, 2);
            $table->string('grade')->nullable();
            $table->decimal('grade_points', 4, 2)->nullable();
            
            // Component-wise Marks (if applicable)
            $table->decimal('theory_marks', 6, 2)->nullable();
            $table->decimal('practical_marks', 6, 2)->nullable();
            $table->decimal('internal_marks', 6, 2)->nullable();
            $table->decimal('oral_marks', 6, 2)->nullable();
            
            // Status and Validation
            $table->enum('status', ['pass', 'fail', 'absent', 'disqualified', 'pending'])->default('pending');
            $table->boolean('is_absent')->default(false);
            $table->text('absence_reason')->nullable();
            
            // Entry and Verification
            $table->foreignId('entered_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('entered_at');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_verified')->default(false);
            
            // Additional Information
            $table->text('remarks')->nullable();
            $table->json('answer_sheet_details')->nullable(); // Answer sheet numbers, etc.
            $table->boolean('is_revaluation_requested')->default(false);
            $table->decimal('revaluation_marks', 6, 2)->nullable();
            $table->text('revaluation_remarks')->nullable();
            
            // Publication Status
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['exam_id', 'student_id', 'subject_id'], 'exam_student_subject_unique');
            $table->index(['student_id', 'exam_id']);
            $table->index(['status', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};