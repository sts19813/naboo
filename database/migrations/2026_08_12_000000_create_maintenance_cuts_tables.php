<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_cuts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('ticket_count')->default(0);
            $table->decimal('labor_total', 12, 2)->default(0);
            $table->decimal('material_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->timestamp('paid_at');
            $table->timestamps();
        });

        Schema::create('maintenance_cut_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('maintenance_cut_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->unique()->constrained('maintenance_tickets')->restrictOnDelete();
            $table->decimal('labor_total', 12, 2)->default(0);
            $table->decimal('material_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_cut_items');
        Schema::dropIfExists('maintenance_cuts');
    }
};
