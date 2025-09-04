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
        Schema::create('id_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('card_number')->unique();
            
            // Polymorphic relationship for student or teacher
            $table->morphs('cardable'); // cardable_type and cardable_id (automatically indexed)
            
            // Card Details
            $table->enum('card_type', ['student', 'teacher', 'staff', 'visitor', 'temporary'])->default('student');
            $table->string('template_name')->default('default');
            $table->enum('card_size', ['cr80', 'cr79', 'custom'])->default('cr80'); // Standard credit card size
            $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
            
            // Validity and Status
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['active', 'expired', 'lost', 'damaged', 'cancelled', 'replaced'])->default('active');
            
            // Security Features
            $table->string('qr_code_data')->nullable(); // QR code content
            $table->string('barcode_data')->nullable(); // Barcode content
            $table->string('rfid_number')->nullable(); // RFID tag number
            $table->string('magnetic_stripe_data')->nullable(); // Magnetic stripe data
            $table->string('security_code')->nullable(); // Additional security code
            
            // Photo and Design
            $table->string('photo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->json('design_elements')->nullable(); // Custom design elements
            $table->string('background_image')->nullable();
            $table->string('logo_path')->nullable();
            
            // Printing Information
            $table->boolean('is_printed')->default(false);
            $table->timestamp('printed_at')->nullable();
            $table->unsignedBigInteger('printed_by')->nullable();
            $table->integer('print_count')->default(0);
            $table->string('printer_name')->nullable();
            
            // Access Control
            $table->json('access_permissions')->nullable(); // Array of access areas/permissions
            $table->json('restricted_areas')->nullable(); // Array of restricted areas
            $table->time('access_start_time')->nullable();
            $table->time('access_end_time')->nullable();
            $table->json('access_days')->nullable(); // Array of allowed days
            
            // Emergency Information
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('blood_group')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            
            // Additional Information
            $table->json('custom_fields')->nullable(); // Additional custom data
            $table->text('notes')->nullable();
            $table->string('replacement_reason')->nullable();
            $table->unsignedBigInteger('replaced_by_card_id')->nullable();
            
            // Audit Information
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // File Paths
            $table->string('front_design_path')->nullable(); // Generated front design
            $table->string('back_design_path')->nullable(); // Generated back design
            $table->string('pdf_path')->nullable(); // Generated PDF file
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['school_id', 'card_number']);
            // Note: morphs('cardable') already creates index on cardable_type, cardable_id
            $table->index(['status', 'is_active']);
            $table->index(['issue_date', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_cards');
    }
};