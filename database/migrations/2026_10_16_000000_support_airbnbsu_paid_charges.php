<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charges', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->change();
            $table->string('customer_name', 190)->nullable()->after('tenant_id');
            $table->string('source', 24)->default('manual')->after('status');
            $table->index('source');
        });

        Schema::table('airbnb_financial_events', function (Blueprint $table): void {
            $table->string('concept', 190)->nullable()->after('event_type');
            $table->string('customer_name', 190)->nullable()->after('concept');
            $table->string('payment_method', 40)->nullable()->after('customer_name');
            $table->foreignId('charge_id')->nullable()->after('payload')->constrained()->nullOnDelete();
            $table->foreignId('charge_payment_id')->nullable()->after('charge_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('charges')->whereNull('tenant_id')->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan cargos sin inquilino.');
        }

        Schema::table('airbnb_financial_events', function (Blueprint $table): void {
            $table->dropForeign(['charge_id']);
            $table->dropForeign(['charge_payment_id']);
            $table->dropColumn(['concept', 'customer_name', 'payment_method', 'charge_id', 'charge_payment_id']);
        });

        Schema::table('charges', function (Blueprint $table): void {
            $table->dropIndex(['source']);
            $table->dropColumn(['customer_name', 'source']);
            $table->foreignId('tenant_id')->nullable(false)->change();
        });
    }
};
