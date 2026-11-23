<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_logbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'created_at'], 'property_logbook_entries_property_created_index');
        });

        Schema::create('property_logbook_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_logbook_entry_id')
                ->constrained('property_logbook_entries')
                ->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name', 190);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_logbook_attachments');
        Schema::dropIfExists('property_logbook_entries');
    }
};
