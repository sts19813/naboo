<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_inventory_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_inventory_area_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_inventory_photos');
    }
};

