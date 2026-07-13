<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyDocumentVersion;
use App\Models\PropertyType;
use App\Models\DossierDocumentRequirement;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DossierDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_special_permission_can_delete_dossier_file_and_log_it(): void
    {
        Storage::fake('public');

        Permission::query()->firstOrCreate(['name' => 'expedientes.eliminar_archivos', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('expedientes.eliminar_archivos');
        $property = $this->createPropertyFixture($user);
        $requirement = DossierDocumentRequirement::query()
            ->where('entity_type', 'property')
            ->orderBy('sort_order')
            ->firstOrFail();

        $document = PropertyDocument::query()->create([
            'property_id' => $property->id,
            'document_type' => $requirement->document_type,
            'label' => $requirement->label,
            'status' => PropertyDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'file_path' => 'properties/' . $property->id . '/documents/test-doc.pdf',
        ]);

        Storage::disk('public')->put('properties/' . $property->id . '/documents/test-doc.pdf', 'fake-file-content');

        $version = PropertyDocumentVersion::query()->create([
            'property_document_id' => $document->id,
            'version_number' => 1,
            'file_path' => 'properties/' . $property->id . '/documents/test-doc.pdf',
            'original_name' => 'test-doc.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)->delete(
            route('dossiers.properties.documents.versions.destroy', [$property, $document->document_type, $version]),
        )->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('properties/' . $property->id . '/documents/test-doc.pdf');
        $this->assertDatabaseMissing('property_document_versions', ['id' => $version->id]);
        $this->assertDatabaseHas('dossier_deleted_files', [
            'entity_type' => 'property',
            'entity_id' => $property->id,
            'document_group' => 'property',
            'document_id' => $document->id,
            'version_id' => $version->id,
            'original_name' => 'test-doc.pdf',
            'deleted_by_user_id' => $user->id,
            'file_deleted' => true,
        ]);
    }

    public function test_user_without_special_permission_cannot_delete_dossier_files(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);
        $requirement = DossierDocumentRequirement::query()
            ->where('entity_type', 'property')
            ->orderBy('sort_order')
            ->firstOrFail();
        $document = PropertyDocument::query()->create([
            'property_id' => $property->id,
            'document_type' => $requirement->document_type,
            'label' => $requirement->label,
            'status' => PropertyDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'file_path' => 'properties/' . $property->id . '/documents/no-delete.pdf',
        ]);
        $version = PropertyDocumentVersion::query()->create([
            'property_document_id' => $document->id,
            'version_number' => 1,
            'file_path' => 'properties/' . $property->id . '/documents/no-delete.pdf',
            'original_name' => 'no-delete.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)->delete(
            route('dossiers.properties.documents.versions.destroy', [$property, $document->document_type, $version]),
        )->assertForbidden();
    }

    private function createPropertyFixture(User $user): Property
    {
        $type = PropertyType::query()->create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::query()->create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        return Property::query()->create([
            'internal_name' => 'Casa Expediente',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Expediente 10',
            'status' => Property::STATUS_OCCUPIED,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);
    }
}
