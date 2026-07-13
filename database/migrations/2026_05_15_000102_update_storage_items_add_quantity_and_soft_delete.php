<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('storage_items', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->after('condition');
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('storage_items', function (Blueprint $table) {
            $table->dropColumn('quantity');
            $table->dropSoftDeletes();
        });
    }
};
