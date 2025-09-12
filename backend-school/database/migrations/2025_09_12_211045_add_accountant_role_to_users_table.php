<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the role enum to include Accountant and HR
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('SuperAdmin','Admin','Teacher','Student','Parent','Accountant','HR') NOT NULL");
        
        // Also update status column to use boolean instead of enum
        DB::statement("ALTER TABLE users MODIFY COLUMN status BOOLEAN DEFAULT 1");
        
        // Update phone column name to match the model
        if (Schema::hasColumn('users', 'phone_number') && !Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('phone_number', 'phone');
            });
        }
        
        // Add soft deletes if not exists
        if (!Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('SuperAdmin','Admin','Teacher','Student','Parent') NOT NULL");
        
        // Revert status column
        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active','inactive','suspended') DEFAULT 'active'");
        
        // Revert phone column name
        if (Schema::hasColumn('users', 'phone') && !Schema::hasColumn('users', 'phone_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('phone', 'phone_number');
            });
        }
        
        // Remove soft deletes
        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
