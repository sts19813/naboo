<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->timestamp('airbnb_managed_at')->nullable()->after('technician_provider_id');
            $table->string('airbnb_external_property_id')->nullable()->unique()->after('airbnb_managed_at');
            $table->string('airbnb_external_property_code')->nullable()->after('airbnb_external_property_id');
            $table->string('airbnb_external_property_name')->nullable()->after('airbnb_external_property_code');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropUnique('properties_airbnb_external_property_id_unique');
            $table->dropColumn([
                'airbnb_managed_at',
                'airbnb_external_property_id',
                'airbnb_external_property_code',
                'airbnb_external_property_name',
            ]);
        });
    }
};
