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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->nullable()->constrained('transport_routes')->onDelete('set null');
            
            // Vehicle Identification
            $table->string('vehicle_number')->unique();
            $table->string('registration_number')->unique();
            $table->string('chassis_number')->nullable();
            $table->string('engine_number')->nullable();
            
            // Vehicle Details
            $table->string('make'); // Tata, Ashok Leyland, etc.
            $table->string('model');
            $table->integer('manufacturing_year');
            $table->string('color');
            $table->enum('fuel_type', ['petrol', 'diesel', 'cng', 'electric', 'hybrid'])->default('diesel');
            $table->enum('vehicle_type', ['bus', 'van', 'car', 'auto_rickshaw', 'tempo'])->default('bus');
            
            // Capacity and Specifications
            $table->integer('seating_capacity');
            $table->integer('standing_capacity')->default(0);
            $table->integer('total_capacity');
            $table->decimal('fuel_tank_capacity', 6, 2)->nullable(); // in liters
            $table->decimal('mileage', 5, 2)->nullable(); // km per liter
            
            // Insurance and Legal
            $table->string('insurance_company')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->decimal('insurance_amount', 10, 2)->nullable();
            
            // Permits and Licenses
            $table->string('permit_number')->nullable();
            $table->date('permit_expiry_date')->nullable();
            $table->string('fitness_certificate_number')->nullable();
            $table->date('fitness_expiry_date')->nullable();
            $table->string('pollution_certificate_number')->nullable();
            $table->date('pollution_expiry_date')->nullable();
            
            // Maintenance and Service
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->integer('service_interval_km')->default(10000);
            $table->integer('current_odometer_reading')->default(0);
            $table->decimal('service_cost', 8, 2)->default(0);
            
            // GPS and Technology
            $table->boolean('has_gps_tracker')->default(false);
            $table->string('gps_device_id')->nullable();
            $table->boolean('has_cctv')->default(false);
            $table->boolean('has_speed_governor')->default(false);
            $table->boolean('has_first_aid_kit')->default(false);
            $table->boolean('has_fire_extinguisher')->default(false);
            
            // Operational Status
            $table->enum('status', ['active', 'inactive', 'under_maintenance', 'out_of_service', 'accident'])->default('active');
            $table->text('status_reason')->nullable();
            $table->date('status_change_date')->nullable();
            
            // Financial Information
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('current_value', 12, 2)->nullable();
            $table->decimal('monthly_emi', 8, 2)->default(0);
            $table->date('loan_end_date')->nullable();
            
            // Additional Information
            $table->text('features')->nullable(); // AC, Music System, etc.
            $table->text('notes')->nullable();
            $table->json('documents')->nullable(); // Array of document file paths
            $table->json('photos')->nullable(); // Array of vehicle photo paths
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['school_id', 'vehicle_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};