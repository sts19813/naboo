<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expense_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('concept', 190);
            $table->decimal('amount', 12, 2);
            $table->string('frequency', 20);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'is_active']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('recurring_expense_item_id')
                ->nullable()
                ->after('property_id')
                ->constrained('recurring_expense_items')
                ->nullOnDelete();
            $table->date('recurrence_date')->nullable()->after('due_date');
            $table->unique(
                ['recurring_expense_item_id', 'recurrence_date'],
                'expenses_recurring_occurrence_unique',
            );
        });

        $timestamp = now();
        $startsOn = now()->startOfMonth()->toDateString();

        DB::table('properties')
            ->where('maintenance_fee', '>', 0)
            ->orderBy('id')
            ->get(['id', 'maintenance_fee', 'created_by'])
            ->each(function ($property) use ($timestamp, $startsOn): void {
                DB::table('recurring_expense_items')->insert([
                    'uuid' => (string) Str::uuid(),
                    'property_id' => $property->id,
                    'concept' => 'Cuota de mantenimiento',
                    'amount' => $property->maintenance_fee,
                    'frequency' => 'monthly',
                    'starts_on' => $startsOn,
                    'is_active' => true,
                    'created_by' => $property->created_by,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('maintenance_fee');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('maintenance_fee', 10, 2)->nullable()->after('monthly_rent_price');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_recurring_occurrence_unique');
            $table->dropConstrainedForeignId('recurring_expense_item_id');
            $table->dropColumn('recurrence_date');
        });

        Schema::dropIfExists('recurring_expense_items');
    }
};
