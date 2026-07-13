<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('storage_item_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('storage_item_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->text('note')->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->foreign('storage_item_id')->references('id')->on('storage_items')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('storage_item_logs');
    }
};
