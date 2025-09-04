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
        Schema::create('student_transport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->constrained('transport_routes')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            
            // Pickup and Drop Details
            $table->string('pickup_stop');
            $table->string('drop_stop');
            $table->time('pickup_time');
            $table->time('drop_time');
            $table->json('pickup_coordinates')->nullable(); // GPS coordinates
            $table->json('drop_coordinates')->nullable(); // GPS coordinates
            
            // Service Type
            $table->enum('service_type', ['pickup_only', 'drop_only', 'both'])->default('both');
            $table->json('service_days'); // Array of days [monday, tuesday, etc.]
            
            // Fee and Payment
            $table->decimal('monthly_fee', 8, 2);
            $table->decimal('annual_fee', 10, 2);
            $table->decimal('security_deposit', 8, 2)->default(0);
            $table->boolean('fee_paid')->default(false);
            $table->date('fee_paid_until')->nullable();
            
            // Emergency and Contact Information
            $table->string('parent_contact_pickup')->nullable();
            $table->string('parent_contact_drop')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            
            // Status and Dates
            $table->enum('status', ['active', 'inactive', 'suspended', 'cancelled'])->default('active');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('status_reason')->nullable();
            
            // Seat Assignment
            $table->string('seat_number')->nullable();
            $table->boolean('has_assigned_seat')->default(false);
            
            // Tracking and Safety
            $table->boolean('sms_alerts_enabled')->default(true);
            $table->boolean('gps_tracking_enabled')->default(true);
            $table->string('rfid_card_number')->nullable();
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->json('documents')->nullable(); // Array of document file paths
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['student_id', 'academic_year_id'], 'student_academic_year_unique');
            $table->index(['vehicle_id', 'route_id']);
            $table->index(['status', 'service_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_transport');
    }
};