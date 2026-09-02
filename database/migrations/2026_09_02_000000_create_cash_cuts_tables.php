<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_cuts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('advisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('advisor_name');
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('received_by_name');
            $table->unsignedInteger('payment_count')->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['advisor_user_id', 'received_at']);
        });

        Schema::create('cash_cut_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_cut_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_payment_id')->nullable()->unique()->constrained('charge_payments')->nullOnDelete();
            $table->uuid('charge_uuid')->nullable();
            $table->string('charge_concept', 190);
            $table->string('property_name')->nullable();
            $table->string('tenant_name')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('mxn');
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_cut_items');
        Schema::dropIfExists('cash_cuts');
    }
};
