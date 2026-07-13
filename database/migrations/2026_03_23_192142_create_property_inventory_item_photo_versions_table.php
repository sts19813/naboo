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
        Schema::create('property_inventory_item_photo_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_inventory_item_photo_id')->references('id')->on('property_inventory_item_photos')->onDelete('cascade')->name('pii_photo_versions_photo_fk');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->references('id')->on('users')->onUpdate('cascade')->name('pii_photo_versions_user_fk');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_inventory_item_photo_versions');
    }
};
