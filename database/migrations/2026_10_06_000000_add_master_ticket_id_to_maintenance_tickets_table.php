<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table): void {
            $table->foreignId('master_ticket_id')
                ->nullable()
                ->after('current_provider_id')
                ->constrained('maintenance_tickets')
                ->nullOnDelete();

            $table->index(['master_ticket_id', 'status']);
        });
    }

    public function down(): void
    {
        if (DB::table('maintenance_tickets')->whereNotNull('master_ticket_id')->exists()) {
            throw new RuntimeException('No se pueden eliminar las relaciones de tickets agrupados existentes.');
        }
        Schema::table('maintenance_tickets', function (Blueprint $table): void {
            $table->dropIndex(['master_ticket_id', 'status']);
            $table->dropConstrainedForeignId('master_ticket_id');
        });
    }
};
