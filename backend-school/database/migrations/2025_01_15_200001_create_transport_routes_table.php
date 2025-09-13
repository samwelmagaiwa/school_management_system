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
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            
            // Route Details
            $table->string('start_point');
            $table->string('end_point');
            $table->decimal('total_distance', 8, 2); // in kilometers
            $table->integer('estimated_duration'); // in minutes
            $table->json('stops'); // Array of stop details with coordinates
            $table->json('coordinates')->nullable(); // GPS coordinates for route mapping
            
            // Timing
            $table->time('morning_start_time');
            $table->time('morning_end_time');
            $table->time('evening_start_time');
            $table->time('evening_end_time');
            
            // Operational Details
            $table->enum('route_type', ['pickup_only', 'drop_only', 'both'])->default('both');
            $table->json('operating_days'); // Array of days [monday, tuesday, etc.]
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            
            // Capacity and Pricing
            $table->integer('max_students')->default(50);
            $table->integer('current_students')->default(0);
            $table->decimal('monthly_fee', 8, 2)->default(0);
            $table->decimal('annual_fee', 10, 2)->default(0);
            
            // Safety and Compliance
            $table->text('safety_instructions')->nullable();
            $table->json('emergency_contacts')->nullable();
            $table->boolean('has_attendant')->default(false);
            $table->boolean('gps_tracking_enabled')->default(false);
            
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
        Schema::dropIfExists('transport_routes');
    }
};