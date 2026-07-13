<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossier_deleted_files', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 30);
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_name', 255)->nullable();
            $table->string('document_group', 30);
            $table->unsignedBigInteger('document_id');
            $table->string('document_type', 120);
            $table->string('document_label', 190)->nullable();
            $table->unsignedBigInteger('version_id')->nullable();
            $table->unsignedInteger('version_number')->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('file_deleted')->default(false);
            $table->text('delete_reason')->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['document_group', 'document_id']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_deleted_files');
    }
};

