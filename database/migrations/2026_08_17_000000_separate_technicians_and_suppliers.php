<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_providers', function (Blueprint $table): void {
            $table->string('category', 190)->nullable()->after('specialty');
        });

        DB::table('maintenance_providers')
            ->whereIn('type', ['empresa_externa', 'proveedor'])
            ->orderBy('id')
            ->eachById(function (object $provider): void {
                DB::table('maintenance_providers')
                    ->where('id', $provider->id)
                    ->update([
                        'type' => 'proveedor',
                        'category' => $provider->category ?: $provider->specialty,
                        'user_id' => null,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('maintenance_providers', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }
};
