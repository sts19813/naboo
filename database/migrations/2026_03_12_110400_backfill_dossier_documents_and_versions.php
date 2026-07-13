<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private const PROPERTY_DOCUMENTS = [
        'title_deed' => 'Escritura o constancia registral',
        'property_tax' => 'Predial',
        'cfe_receipt' => 'Recibo CFE',
        'water_receipt' => 'Recibo de agua',
        'cadastral_id' => 'Cedula catastral',
    ];

    private const TENANT_DOCUMENTS = [
        'official_id' => 'Identificacion oficial',
        'proof_of_income' => 'Comprobante de ingresos',
        'proof_of_address' => 'Comprobante de domicilio',
        'employment_letter' => 'Carta laboral',
        'bank_statements' => 'Estados de cuenta',
        'references' => 'Referencias',
        'signed_application' => 'Solicitud firmada',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('property_documents')
            ->whereNotNull('file_path')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($now): void {
                foreach ($rows as $row) {
                    $exists = DB::table('property_document_versions')
                        ->where('property_document_id', $row->id)
                        ->where('version_number', 1)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('property_document_versions')->insert([
                        'property_document_id' => $row->id,
                        'version_number' => 1,
                        'file_path' => $row->file_path,
                        'original_name' => basename($row->file_path),
                        'mime_type' => null,
                        'file_size' => null,
                        'uploaded_by' => null,
                        'uploaded_at' => $row->uploaded_at,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

        DB::table('properties')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($now): void {
                foreach ($rows as $row) {
                    foreach (self::PROPERTY_DOCUMENTS as $documentType => $label) {
                        $existingDocument = DB::table('property_documents')
                            ->where('property_id', $row->id)
                            ->where('document_type', $documentType)
                            ->first();

                        if ($existingDocument) {
                            DB::table('property_documents')
                                ->where('id', $existingDocument->id)
                                ->update([
                                    'label' => $label,
                                    'updated_at' => $now,
                                ]);
                            continue;
                        }

                        DB::table('property_documents')->insert([
                            'property_id' => $row->id,
                            'document_type' => $documentType,
                            'label' => $label,
                            'file_path' => null,
                            'status' => 'pending',
                            'uploaded_at' => null,
                            'expires_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            });

        DB::table('tenants')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($now): void {
                foreach ($rows as $row) {
                    foreach (self::TENANT_DOCUMENTS as $documentType => $label) {
                        $exists = DB::table('tenant_documents')
                            ->where('tenant_id', $row->id)
                            ->where('document_type', $documentType)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        DB::table('tenant_documents')->insert([
                            'tenant_id' => $row->id,
                            'document_type' => $documentType,
                            'label' => $label,
                            'file_path' => null,
                            'status' => 'pending',
                            'uploaded_at' => null,
                            'expires_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op. This migration backfills data only.
    }
};
