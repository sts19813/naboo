<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('rfc', 20)->nullable();
            $table->string('curp', 20)->nullable();
            $table->string('owner_type')->default('individual');
            $table->string('bank_name')->nullable();
            $table->string('clabe', 18)->nullable();
            $table->string('account_holder')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('owner_property', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['owner_id', 'property_id']);
        });

        if (Schema::hasTable('property_owners')) {
            $legacyRows = DB::table('property_owners')->orderBy('id')->get();
            $ownerIndex = [];

            foreach ($legacyRows as $legacyRow) {
                $key = mb_strtolower(trim(($legacyRow->name ?? '') . '|' . ($legacyRow->phone ?? '') . '|' . ($legacyRow->email ?? '')));

                if (!isset($ownerIndex[$key])) {
                    $ownerId = DB::table('owners')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'name' => $legacyRow->name,
                        'phone' => $legacyRow->phone,
                        'email' => $legacyRow->email,
                        'owner_type' => $legacyRow->owner_type ?? 'individual',
                        'bank_name' => $legacyRow->bank_name,
                        'clabe' => $legacyRow->clabe,
                        'account_holder' => $legacyRow->account_holder,
                        'payment_method' => $legacyRow->payment_method,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $ownerIndex[$key] = $ownerId;
                }

                DB::table('owner_property')->insertOrIgnore([
                    'owner_id' => $ownerIndex[$key],
                    'property_id' => $legacyRow->property_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_property');
        Schema::dropIfExists('owners');
    }
};

