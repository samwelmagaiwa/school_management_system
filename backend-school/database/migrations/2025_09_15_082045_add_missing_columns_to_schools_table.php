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
        Schema::table('schools', function (Blueprint $table) {
            // Add missing columns for the school form
            $table->enum('school_type', ['primary', 'secondary', 'higher_secondary', 'all'])->default('all')->after('code');
            $table->string('principal_email')->nullable()->after('principal_name');
            $table->string('principal_phone')->nullable()->after('principal_email');
            $table->integer('established_year')->nullable()->after('established_date');
            $table->string('board_affiliation')->nullable()->after('established_year'); // CBSE, ICSE, State Board, etc.
            $table->string('registration_number')->nullable()->after('board_affiliation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Drop the added columns
            $table->dropColumn([
                'school_type',
                'principal_email',
                'principal_phone',
                'established_year',
                'board_affiliation',
                'registration_number'
            ]);
        });
    }
};
