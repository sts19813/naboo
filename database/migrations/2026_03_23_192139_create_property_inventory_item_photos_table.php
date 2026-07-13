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
        Schema::dropIfExists('property_inventory_item_photos');
        Schema::create('property_inventory_item_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_inventory_item_id')->references('id')->on('property_inventory_items')->onDelete('cascade')->name('pii_photos_item_fk');
            $table->string('name');
            $table->string('status')->default('active');
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_inventory_item_photos');
    }
};
