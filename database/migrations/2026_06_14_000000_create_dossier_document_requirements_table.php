<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossier_document_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 30);
            $table->string('document_type', 120);
            $table->string('label', 190);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['entity_type', 'document_type'], 'dossier_req_entity_type_unique');
            $table->index(['entity_type', 'is_active', 'sort_order'], 'dossier_req_entity_active_order_idx');
        });

        $now = now();
        $defaults = [
            ['entity_type' => 'property', 'document_type' => 'contract', 'label' => 'Contrato', 'sort_order' => 10],
            ['entity_type' => 'property', 'document_type' => 'property_tax', 'label' => 'Predial', 'sort_order' => 20],
            ['entity_type' => 'tenant', 'document_type' => 'official_id', 'label' => 'Identificacion', 'sort_order' => 10],
            ['entity_type' => 'tenant', 'document_type' => 'curp', 'label' => 'CURP', 'sort_order' => 20],
            ['entity_type' => 'owner', 'document_type' => 'passport', 'label' => 'Pasaporte', 'sort_order' => 10],
            ['entity_type' => 'owner', 'document_type' => 'ine', 'label' => 'INE', 'sort_order' => 20],
            ['entity_type' => 'owner', 'document_type' => 'rfc', 'label' => 'RFC', 'sort_order' => 30],
        ];

        DB::table('dossier_document_requirements')->insert(array_map(
            fn (array $row): array => $row + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            $defaults,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_document_requirements');
    }
};
