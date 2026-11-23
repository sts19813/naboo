<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::table('charge_payments')->whereNull('currency')->orWhereRaw("LOWER(currency) <> ?", ['mxn'])->exists()) {
            throw new RuntimeException('Hay pagos históricos distintos de MXN; deben revisarse antes de asignar moneda a las propiedades.');
        }
        Schema::table('properties', fn (Blueprint $table) => $table->string('billing_currency', 3)->default('mxn')->index());
    }

    public function down(): void
    {
        if (DB::table('properties')->where('billing_currency', '!=', 'mxn')->exists()) {
            throw new RuntimeException('No se puede eliminar la moneda de propiedades que no usan MXN.');
        }
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropIndex(['billing_currency']);
            $table->dropColumn('billing_currency');
        });
    }
};
