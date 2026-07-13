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
            $table->decimal('monthly_rent_price', 10, 2)->nullable()->after('unit_number');
            $table->decimal('maintenance_fee', 10, 2)->nullable()->after('monthly_rent_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['monthly_rent_price', 'maintenance_fee']);
        });
    }
};
