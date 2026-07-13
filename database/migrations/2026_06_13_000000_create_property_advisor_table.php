<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_advisor', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['property_id', 'user_id']);
        });

        DB::table('properties')
            ->whereNotNull('advisor_user_id')
            ->orderBy('id')
            ->select(['id', 'advisor_user_id'])
            ->chunkById(200, function ($properties): void {
                $now = now();
                $rows = $properties
                    ->map(fn ($property) => [
                        'property_id' => $property->id,
                        'user_id' => $property->advisor_user_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                DB::table('property_advisor')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_advisor');
    }
};
