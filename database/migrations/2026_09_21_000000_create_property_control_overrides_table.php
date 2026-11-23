<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_control_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('check_key', 80);
            $table->foreignId('marked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'check_key'], 'property_control_override_unique');
            $table->index(['check_key', 'property_id'], 'property_control_override_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_control_overrides');
    }
};
