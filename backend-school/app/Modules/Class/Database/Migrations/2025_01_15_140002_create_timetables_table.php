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
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->integer('period_number'); // 1, 2, 3, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room_number')->nullable();
            $table->enum('period_type', ['regular', 'lab', 'library', 'sports', 'break', 'assembly'])->default('regular');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            
            $table->unique(['class_id', 'day_of_week', 'period_number', 'effective_from'], 'class_day_period_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};