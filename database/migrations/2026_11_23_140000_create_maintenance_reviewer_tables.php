<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_reviewer_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('range_type', 20)->default('relative');
            $table->unsignedInteger('days_before')->nullable();
            $table->unsignedInteger('days_after')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });
        Schema::create('maintenance_ticket_validations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->foreignId('validated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('validated_at');
            $table->timestamps();
        });
        $role = \Spatie\Permission\Models\Role::findOrCreate('revisor de tickets', 'web');
        foreach (['mantenimiento.consultar_tickets', 'mantenimiento.validar_tickets'] as $name) {
            $role->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($name, 'web'));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void
    {
        if (DB::table('maintenance_ticket_validations')->exists() || DB::table('maintenance_reviewer_accesses')->exists()) {
            throw new RuntimeException('No se pueden eliminar revisiones ni configuraciones existentes.');
        }
        Schema::dropIfExists('maintenance_ticket_validations');
        Schema::dropIfExists('maintenance_reviewer_accesses');
    }
};
