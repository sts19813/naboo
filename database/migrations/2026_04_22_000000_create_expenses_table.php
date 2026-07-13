<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('concept', 190);
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->text('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('upcoming_notified_at')->nullable();
            $table->timestamp('overdue_notified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'due_date']);
            $table->index(['paid_at', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
