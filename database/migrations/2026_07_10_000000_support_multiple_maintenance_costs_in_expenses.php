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
        Schema::table('maintenance_ticket_costs', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
        });

        Schema::table('maintenance_ticket_costs', function (Blueprint $table) {
            $table->dropUnique('maintenance_ticket_costs_ticket_id_unique');
            $table->string('payer', 30)->nullable()->after('currency');
            $table->string('payment_rule', 30)->nullable()->after('payer');
            $table->foreign('ticket_id')
                ->references('id')
                ->on('maintenance_tickets')
                ->cascadeOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('excluded_from_totals')->default(false)->after('amount')->index();
        });

        Schema::table('maintenance_ticket_costs', function (Blueprint $table) {
            $table->foreignId('expense_id')
                ->nullable()
                ->unique()
                ->after('ticket_id')
                ->constrained('expenses')
                ->nullOnDelete();
        });

        DB::table('maintenance_ticket_costs')
            ->join('maintenance_tickets', 'maintenance_tickets.id', '=', 'maintenance_ticket_costs.ticket_id')
            ->select([
                'maintenance_ticket_costs.id as cost_id',
                'maintenance_ticket_costs.final_cost',
                'maintenance_ticket_costs.notes',
                'maintenance_ticket_costs.created_at',
                'maintenance_tickets.id as ticket_id',
                'maintenance_tickets.property_id',
                'maintenance_tickets.reference',
                'maintenance_tickets.title',
                'maintenance_tickets.payer',
                'maintenance_tickets.payment_rule',
            ])
            ->orderBy('maintenance_ticket_costs.id')
            ->each(function ($row): void {
                $payer = in_array($row->payer, ['inquilino', 'administracion'], true)
                    ? $row->payer
                    : 'administracion';
                $reference = filled($row->reference)
                    ? $row->reference
                    : str_pad((string) $row->ticket_id, 8, '0', STR_PAD_LEFT);
                $timestamp = $row->created_at ?: now();
                $expenseId = DB::table('expenses')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'property_id' => $row->property_id,
                    'concept' => Str::limit("Mantenimiento {$reference}: {$row->title}", 190, ''),
                    'amount' => $row->final_cost,
                    'excluded_from_totals' => $payer === 'inquilino',
                    'due_date' => substr((string) $timestamp, 0, 10),
                    'description' => $row->notes,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                DB::table('maintenance_ticket_costs')
                    ->where('id', $row->cost_id)
                    ->update([
                        'expense_id' => $expenseId,
                        'payer' => $payer,
                        'payment_rule' => $row->payment_rule,
                    ]);
            });
    }

    public function down(): void
    {
        $generatedExpenseIds = DB::table('maintenance_ticket_costs')
            ->whereNotNull('expense_id')
            ->pluck('expense_id')
            ->all();

        DB::table('expenses')->whereIn('id', $generatedExpenseIds)->delete();

        Schema::table('maintenance_ticket_costs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
            $table->dropColumn(['payer', 'payment_rule']);
            $table->unique('ticket_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('excluded_from_totals');
        });
    }
};
