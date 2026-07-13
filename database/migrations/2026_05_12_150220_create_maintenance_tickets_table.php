<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('current_provider_id')->nullable();
            $table->string('reported_by_role', 30)->nullable();
            $table->string('reported_by_name', 190)->nullable();
            $table->string('category', 60);
            $table->string('priority', 20)->default('media');
            $table->string('status', 30)->default('pendiente');
            $table->string('title', 190);
            $table->string('reference', 190)->nullable();
            $table->string('exact_location', 255)->nullable();
            $table->text('description');
            $table->text('additional_notes')->nullable();
            $table->dateTime('reported_at');
            $table->dateTime('scheduled_visit_at')->nullable();
            $table->string('payer', 30)->nullable();
            $table->string('payment_rule', 30)->nullable();
            $table->text('payment_rule_notes')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();
            $table->index(['status', 'priority']);
            $table->index(['property_id', 'status']);
            $table->index(['current_provider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
    }
};
