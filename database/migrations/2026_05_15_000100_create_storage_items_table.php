<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('storage_items', function (Blueprint $table) {
            $table->id();
            $table->string('product_type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->enum('condition', ['bueno', 'regular', 'malo'])->default('bueno');
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('storage_items');
    }
};
