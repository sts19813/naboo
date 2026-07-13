<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_inventory_items', function (Blueprint $table) {
            $table->text('entry_checklist')->nullable()->after('notes');
            $table->text('exit_checklist')->nullable()->after('entry_checklist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_inventory_items', function (Blueprint $table) {
            $table->dropColumn(['entry_checklist', 'exit_checklist']);
        });
    }
};