<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_provider_property', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_provider_id')->constrained('maintenance_providers')->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['property_id', 'maintenance_provider_id'], 'provider_property_unique');
            $table->index(['maintenance_provider_id', 'property_id'], 'provider_property_lookup');
        });
    }

    public function down(): void
    {
        if (DB::table('maintenance_provider_property')->exists()) {
            throw new RuntimeException('No se pueden eliminar asignaciones de proveedores existentes.');
        }
        Schema::dropIfExists('maintenance_provider_property');
    }
};
