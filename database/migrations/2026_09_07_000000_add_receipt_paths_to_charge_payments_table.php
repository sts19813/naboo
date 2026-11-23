<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charge_payments', function (Blueprint $table) {
            $table->json('receipt_paths')->nullable()->after('receipt_path');
        });
    }

    public function down(): void
    {
        Schema::table('charge_payments', function (Blueprint $table) {
            $table->dropColumn('receipt_paths');
        });
    }
};
