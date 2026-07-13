<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_ticket_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->string('event', 40);
            $table->string('channel', 20)->default('email');
            $table->string('recipient', 190)->nullable();
            $table->boolean('was_sent')->default(false);
            $table->dateTime('notified_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['ticket_id', 'event', 'created_at'], 'mnt_notif_tkt_evt_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_ticket_notifications');
    }
};
