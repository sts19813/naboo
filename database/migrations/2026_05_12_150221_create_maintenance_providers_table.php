<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_providers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('name', 190);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('specialty', 190)->nullable();
            $table->decimal('average_cost', 12, 2)->nullable();
            $table->decimal('rating', 4, 2)->nullable();
            $table->string('availability', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_providers');
    }
};
