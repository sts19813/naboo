<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->decimal('map_latitude', 10, 7)->nullable()->after('map_url');
            $table->decimal('map_longitude', 10, 7)->nullable()->after('map_latitude');
            $table->text('map_resolved_url')->nullable()->after('map_longitude');
            $table->timestamp('map_coordinates_resolved_at')->nullable()->after('map_resolved_url');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn([
                'map_latitude',
                'map_longitude',
                'map_resolved_url',
                'map_coordinates_resolved_at',
            ]);
        });
    }
};
