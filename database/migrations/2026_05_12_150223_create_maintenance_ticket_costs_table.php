<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_ticket_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('material_cost', 12, 2)->default(0);
            $table->decimal('advance_cost', 12, 2)->default(0);
            $table->decimal('final_cost', 12, 2)->default(0);
            $table->string('currency', 10)->default('MXN');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_ticket_costs');
    }
};
