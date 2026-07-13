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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('internal_name');
            $table->string('internal_reference')->nullable();
            $table->foreignId('property_type_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('zone_id')->constrained()->cascadeOnUpdate();
            $table->string('full_address');
            $table->string('complex_name')->nullable();
            $table->string('official_number')->nullable();
            $table->string('unit_number')->nullable();
            $table->string('facade_photo_path')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('current_tenant_name')->nullable();
            $table->date('contract_expires_at')->nullable();
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};

