<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('maintenance_providers', 'is_responsible')) {
            return;
        }

        Schema::table('maintenance_providers', function (Blueprint $table): void {
            $table->dropIndex(['is_responsible']);
            $table->dropColumn('is_responsible');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('maintenance_providers', 'is_responsible')) {
            return;
        }

        Schema::table('maintenance_providers', function (Blueprint $table): void {
            $table->boolean('is_responsible')->default(false)->after('is_active')->index();
        });
    }
};
