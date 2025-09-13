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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('book_categories')->onDelete('restrict');
            
            // Book Identification
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('author');
            $table->string('co_authors')->nullable();
            $table->string('isbn')->nullable();
            $table->string('isbn13')->nullable();
            $table->string('barcode')->unique()->nullable();
            $table->string('accession_number')->unique();
            
            // Publication Details
            $table->string('publisher');
            $table->string('publication_place')->nullable();
            $table->integer('publication_year');
            $table->string('edition')->nullable();
            $table->integer('pages')->nullable();
            $table->string('language')->default('English');
            
            // Physical Details
            $table->enum('format', ['hardcover', 'paperback', 'ebook', 'audiobook', 'magazine', 'journal', 'reference'])->default('paperback');
            $table->string('dimensions')->nullable(); // e.g., "24 x 16 cm"
            $table->decimal('weight', 6, 2)->nullable(); // in grams
            $table->string('binding_type')->nullable();
            
            // Library Management
            $table->integer('total_copies');
            $table->integer('available_copies');
            $table->integer('issued_copies')->default(0);
            $table->integer('damaged_copies')->default(0);
            $table->integer('lost_copies')->default(0);
            
            // Pricing and Procurement
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->date('purchase_date')->nullable();
            $table->string('vendor')->nullable();
            $table->string('bill_number')->nullable();
            
            // Classification and Location
            $table->string('dewey_decimal')->nullable();
            $table->string('call_number')->nullable();
            $table->string('shelf_location')->nullable();
            $table->string('rack_number')->nullable();
            $table->integer('floor_number')->nullable();
            
            // Content Information
            $table->text('description')->nullable();
            $table->text('summary')->nullable();
            $table->json('keywords')->nullable(); // Array of keywords for search
            $table->json('subjects')->nullable(); // Array of subject areas
            $table->string('age_group')->nullable(); // e.g., "8-12 years"
            $table->enum('reading_level', ['beginner', 'intermediate', 'advanced', 'all'])->default('all');
            
            // Digital Information
            $table->string('cover_image')->nullable();
            $table->string('ebook_file')->nullable();
            $table->string('audiobook_file')->nullable();
            $table->text('external_links')->nullable(); // Links to online resources
            
            // Status and Condition
            $table->enum('status', ['available', 'issued', 'reserved', 'damaged', 'lost', 'under_repair', 'withdrawn'])->default('available');
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'damaged'])->default('good');
            $table->text('condition_notes')->nullable();
            
            // Borrowing Rules
            $table->integer('max_issue_days')->default(14);
            $table->integer('max_renewals')->default(2);
            $table->decimal('fine_per_day', 6, 2)->default(1.00);
            $table->boolean('is_reference_only')->default(false);
            $table->json('borrower_restrictions')->nullable(); // Which user types can borrow
            
            // Popularity and Usage
            $table->integer('total_issues')->default(0);
            $table->integer('current_reservations')->default(0);
            $table->decimal('popularity_score', 5, 2)->default(0);
            $table->date('last_issued_date')->nullable();
            
            // Administrative
            $table->foreignId('added_by')->constrained('users')->onDelete('restrict');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['school_id', 'accession_number']);
            $table->index(['title', 'author']);
            $table->index(['isbn', 'isbn13']);
            $table->index(['status', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};