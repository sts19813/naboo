<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_ticket_costs', function (Blueprint $table): void {
            $table->unsignedBigInteger('origin_ticket_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (DB::table('maintenance_ticket_costs')->whereNotNull('origin_ticket_id')->exists()) {
            throw new RuntimeException('No se puede eliminar el origen de costos agrupados existentes.');
        }
        Schema::table('maintenance_ticket_costs', function (Blueprint $table): void {
            $table->dropIndex(['origin_ticket_id']);
            $table->dropColumn('origin_ticket_id');
        });
    }
};
