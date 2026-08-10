<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_providers', function (Blueprint $table): void {
            $table->boolean('is_responsible')->default(false)->after('is_active')->index();
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->foreignId('technician_provider_id')
                ->nullable()
                ->after('advisor_user_id')
                ->constrained('maintenance_providers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('technician_provider_id');
        });

        Schema::table('maintenance_providers', function (Blueprint $table): void {
            $table->dropColumn('is_responsible');
        });
    }
};
