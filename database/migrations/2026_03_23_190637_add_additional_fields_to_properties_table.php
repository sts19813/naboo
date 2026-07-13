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
        Schema::table('properties', function (Blueprint $table) {
            $table->string('zone_text')->nullable()->after('zone_id');
            $table->text('details')->nullable()->after('unit_number');
            $table->text('description')->nullable()->after('details');
            $table->text('rental_requirements')->nullable()->after('description');
            $table->text('amenities')->nullable()->after('rental_requirements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['zone_text', 'details', 'description', 'rental_requirements', 'amenities']);
        });
    }
};
