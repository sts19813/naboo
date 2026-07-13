<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 190)->unique();
            $table->string('location', 255)->nullable();
            $table->string('maps_url', 1000)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('storage_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_warehouse_id')->constrained('storage_warehouses')->cascadeOnDelete();
            $table->string('name', 190);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['storage_warehouse_id', 'name']);
        });

        Schema::table('storage_items', function (Blueprint $table) {
            $table->foreignId('storage_warehouse_id')->nullable()->after('product_type')->constrained('storage_warehouses')->nullOnDelete();
            $table->foreignId('storage_zone_id')->nullable()->after('storage_warehouse_id')->constrained('storage_zones')->nullOnDelete();
            $table->index(['storage_warehouse_id', 'storage_zone_id']);
        });

        $warehouseId = DB::table('storage_warehouses')->insertGetId([
            'name' => 'Almacén principal',
            'location' => null,
            'maps_url' => null,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zoneId = DB::table('storage_zones')->insertGetId([
            'storage_warehouse_id' => $warehouseId,
            'name' => 'Zona principal',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('storage_items')->update([
            'storage_warehouse_id' => $warehouseId,
            'storage_zone_id' => $zoneId,
        ]);
    }

    public function down(): void
    {
        Schema::table('storage_items', function (Blueprint $table) {
            $table->dropForeign(['storage_warehouse_id']);
            $table->dropForeign(['storage_zone_id']);
            $table->dropIndex(['storage_warehouse_id', 'storage_zone_id']);
            $table->dropColumn(['storage_warehouse_id', 'storage_zone_id']);
        });

        Schema::dropIfExists('storage_zones');
        Schema::dropIfExists('storage_warehouses');
    }
};
