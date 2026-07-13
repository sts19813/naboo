<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expense_item_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_expense_item_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('path');
            $table->string('type', 20);
            $table->string('mime_type', 120)->nullable();
            $table->string('original_name', 190)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index(['recurring_expense_item_id', 'type'], 'recurring_item_files_item_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_expense_item_files');
    }
};
