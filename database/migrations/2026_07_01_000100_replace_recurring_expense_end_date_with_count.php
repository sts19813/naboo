<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_expense_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('occurrences_count')->default(12)->after('starts_on');
        });

        DB::table('recurring_expense_items')
            ->whereNotNull('ends_on')
            ->orderBy('id')
            ->get(['id', 'frequency', 'starts_on', 'ends_on'])
            ->each(function ($item): void {
                $startsOn = Carbon::parse($item->starts_on)->startOfDay();
                $endsOn = Carbon::parse($item->ends_on)->startOfDay();
                $count = $item->frequency === 'annual'
                    ? $startsOn->diffInYears($endsOn) + 1
                    : $startsOn->diffInMonths($endsOn) + 1;

                DB::table('recurring_expense_items')
                    ->where('id', $item->id)
                    ->update(['occurrences_count' => max(1, min(120, $count))]);
            });

        Schema::table('recurring_expense_items', function (Blueprint $table) {
            $table->dropColumn('ends_on');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_expense_items', function (Blueprint $table) {
            $table->date('ends_on')->nullable()->after('starts_on');
            $table->dropColumn('occurrences_count');
        });
    }
};
