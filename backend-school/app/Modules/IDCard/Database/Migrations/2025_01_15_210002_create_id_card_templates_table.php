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
        Schema::create('id_card_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            
            // Template Configuration
            $table->enum('card_type', ['student', 'teacher', 'staff', 'visitor', 'universal'])->default('student');
            $table->enum('card_size', ['cr80', 'cr79', 'custom'])->default('cr80');
            $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
            $table->decimal('width_mm', 6, 2)->default(85.60); // CR80 standard width
            $table->decimal('height_mm', 6, 2)->default(53.98); // CR80 standard height
            
            // Design Elements
            $table->json('front_design'); // JSON structure for front design
            $table->json('back_design'); // JSON structure for back design
            $table->string('background_color')->default('#FFFFFF');
            $table->string('text_color')->default('#000000');
            $table->string('accent_color')->default('#0066CC');
            
            // Layout Configuration
            $table->json('photo_settings'); // Photo position, size, border, etc.
            $table->json('logo_settings'); // Logo position, size, etc.
            $table->json('qr_code_settings')->nullable(); // QR code position, size, etc.
            $table->json('barcode_settings')->nullable(); // Barcode position, size, etc.
            
            // Text Fields Configuration
            $table->json('text_fields'); // Array of text field configurations
            $table->json('font_settings'); // Font family, sizes, styles
            
            // Security Features
            $table->boolean('include_qr_code')->default(true);
            $table->boolean('include_barcode')->default(false);
            $table->boolean('include_rfid')->default(false);
            $table->boolean('include_magnetic_stripe')->default(false);
            $table->string('qr_code_content_template')->nullable(); // Template for QR code content
            
            // Printing Settings
            $table->json('print_settings')->nullable(); // Print margins, bleed, etc.
            $table->decimal('print_margin_mm', 4, 2)->default(2.00);
            $table->boolean('include_crop_marks')->default(false);
            $table->boolean('include_bleed')->default(false);
            
            // Template Assets
            $table->string('background_image')->nullable();
            $table->string('watermark_image')->nullable();
            $table->json('additional_images')->nullable(); // Array of additional image paths
            
            // Validation Rules
            $table->json('validation_rules')->nullable(); // Rules for required fields
            $table->json('field_mappings')->nullable(); // Mapping of database fields to template fields
            
            // Status and Versioning
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('version')->default('1.0');
            $table->unsignedBigInteger('parent_template_id')->nullable();
            
            // Usage Statistics
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            
            // Audit Information
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['school_id', 'code']);
            $table->index(['card_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_card_templates');
    }
};