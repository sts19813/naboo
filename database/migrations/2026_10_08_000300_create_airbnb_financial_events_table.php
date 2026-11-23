<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airbnb_financial_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->string('external_event_id', 120)->unique();
            $table->string('event_type', 60);
            $table->string('external_reservation_id', 120)->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('MXN');
            $table->date('occurred_on')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airbnb_financial_events');
    }
};
