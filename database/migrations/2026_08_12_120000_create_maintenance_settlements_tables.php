<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->string('settlement_status', 24)
                ->default('pendiente')
                ->after('status')
                ->index();
            $table->timestamp('settled_at')->nullable()->after('settlement_status');
        });

        Schema::create('maintenance_settlements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('liquidado')->index();
            $table->unsignedInteger('total_tickets')->default(0);
            $table->decimal('total_labor_cost', 12, 2)->default(0);
            $table->decimal('total_material_cost', 12, 2)->default(0);
            $table->decimal('total_advance_cost', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('MXN');
            $table->text('notes')->nullable();
            $table->timestamp('settled_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('maintenance_settlement_ticket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_settlement_id')
                ->constrained('maintenance_settlements')
                ->cascadeOnDelete();
            $table->foreignId('maintenance_ticket_id')
                ->constrained('maintenance_tickets')
                ->cascadeOnDelete();
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('material_cost', 12, 2)->default(0);
            $table->decimal('advance_cost', 12, 2)->default(0);
            $table->decimal('final_cost', 12, 2)->default(0);
            $table->timestamps();

            $table->unique('maintenance_ticket_id');
            $table->index(['maintenance_settlement_id', 'maintenance_ticket_id'], 'maintenance_settlement_ticket_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_settlement_ticket');
        Schema::dropIfExists('maintenance_settlements');

        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->dropIndex(['settlement_status']);
            $table->dropColumn(['settlement_status', 'settled_at']);
        });
    }
};
