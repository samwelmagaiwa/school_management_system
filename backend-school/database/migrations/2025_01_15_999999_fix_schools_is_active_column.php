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
        // Check if schools table exists and fix the is_active column issue
        if (Schema::hasTable('schools')) {
            $hasIsActive = Schema::hasColumn('schools', 'is_active');
            $hasStatus = Schema::hasColumn('schools', 'status');
            
            if (!$hasIsActive && $hasStatus) {
                // Rename status to is_active
                Schema::table('schools', function (Blueprint $table) {
                    $table->renameColumn('status', 'is_active');
                });
            } elseif (!$hasIsActive && !$hasStatus) {
                // Add is_active column
                Schema::table('schools', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('schools') && Schema::hasColumn('schools', 'is_active')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->renameColumn('is_active', 'status');
            });
        }
    }
};