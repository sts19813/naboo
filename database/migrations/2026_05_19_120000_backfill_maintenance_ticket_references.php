<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('maintenance_tickets')
            ->orderBy('id')
            ->select('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('maintenance_tickets')
                        ->where('id', $row->id)
                        ->update([
                            'reference' => str_pad((string) $row->id, 8, '0', STR_PAD_LEFT),
                        ]);
                }
            });
    }

    public function down(): void
    {
    }
};
