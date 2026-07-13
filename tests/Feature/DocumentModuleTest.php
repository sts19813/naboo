<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyType;
use App\Models\DossierDocumentRequirement;
use App\Models\Tenant;
use App\Models\TenantDocument;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_index_displays_property_and_tenant_documents(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Montebello', 'slug' => 'montebello', 'is_active' => true]);
        $owner = Owner::create([
            'name' => 'Owner Test',
            'phone' => '9990000000',
            'owner_type' => Owner::OWNER_INDIVIDUAL,
            'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('properties.store'), [
            'internal_name' => 'Casa Docs 1',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Uno #1',
            'status' => Property::STATUS_AVAILABLE,
            'owner_ids' => [$owner->id],
            'facade_photo' => UploadedFile::fake()->image('facade.jpg'),
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('tenants.store'), [
            'full_name' => 'Inquilino Documentado',
            'phone_primary' => '9991112233',
            'email' => 'inquilino.documentado@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
        ])->assertSessionHasNoErrors();

        $property = Property::query()->where('internal_name', 'Casa Docs 1')->firstOrFail();
        $propertyDocumentType = DossierDocumentRequirement::query()
            ->where('entity_type', 'property')
            ->orderBy('sort_order')
            ->value('document_type');

        $this->actingAs($user)->post(route('dossiers.properties.documents.upload', [$property, $propertyDocumentType]), [
            'file' => UploadedFile::fake()->create('predial-general.pdf', 90, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $tenant = Tenant::query()->where('full_name', 'Inquilino Documentado')->firstOrFail();
        $tenantDocumentType = DossierDocumentRequirement::query()
            ->where('entity_type', 'tenant')
            ->orderBy('sort_order')
            ->value('document_type');

        $this->actingAs($user)->post(route('dossiers.tenants.documents.upload', [$tenant, $tenantDocumentType]), [
            'file' => UploadedFile::fake()->create('ine-general.pdf', 80, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $response = $this
            ->actingAs($user)
            ->get(route('documents.index'));

        $response->assertOk();
        $response->assertSee('Documentos actuales');
        $response->assertSee('predial-general.pdf');
        $response->assertSee('ine-general.pdf');
        $response->assertSee('Casa Docs 1');
        $response->assertSee('Inquilino Documentado');
    }

    public function test_property_document_uploads_create_versions(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);
        $owner = Owner::create([
            'name' => 'Owner Versiones',
            'phone' => '9994445566',
            'owner_type' => Owner::OWNER_INDIVIDUAL,
            'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            'is_active' => true,
        ]);
        $documentType = DossierDocumentRequirement::query()
            ->where('entity_type', 'property')
            ->orderBy('sort_order')
            ->value('document_type');

        $this->actingAs($user)->post(route('properties.store'), [
            'internal_name' => 'Casa Versionada',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Version #1',
            'status' => Property::STATUS_AVAILABLE,
            'owner_ids' => [$owner->id],
            'facade_photo' => UploadedFile::fake()->image('facade.jpg'),
            'documents' => [
                $documentType => UploadedFile::fake()->create('contrato-v1.pdf', 120, 'application/pdf'),
            ],
        ])->assertSessionHasNoErrors();

        $property = Property::query()->where('internal_name', 'Casa Versionada')->firstOrFail();
        $document = PropertyDocument::query()
            ->where('property_id', $property->id)
            ->where('document_type', $documentType)
            ->firstOrFail();

        $this->assertDatabaseHas('property_document_versions', [
            'property_document_id' => $document->id,
            'version_number' => 1,
        ]);

        $this->actingAs($user)->put(route('properties.update', $property), [
            'internal_name' => 'Casa Versionada',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Version #1',
            'status' => Property::STATUS_AVAILABLE,
            'owner_ids' => [$owner->id],
            'documents' => [
                $documentType => UploadedFile::fake()->create('contrato-v2.pdf', 140, 'application/pdf'),
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('property_document_versions', [
            'property_document_id' => $document->id,
            'version_number' => 2,
        ]);

        $this->assertDatabaseCount('property_document_versions', 2);
    }

    public function test_tenant_document_uploads_create_versions(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tenants.store'), [
            'full_name' => 'Tenant Versiones',
            'phone_primary' => '9997001122',
            'email' => 'tenant.versiones@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
        ])->assertSessionHasNoErrors();

        $tenant = Tenant::query()->where('full_name', 'Tenant Versiones')->firstOrFail();
        $documentType = DossierDocumentRequirement::query()
            ->where('entity_type', 'tenant')
            ->orderBy('sort_order')
            ->value('document_type');
        $document = TenantDocument::query()
            ->where('tenant_id', $tenant->id)
            ->where('document_type', $documentType)
            ->firstOrFail();

        $this->actingAs($user)->from(route('tenants.edit', $tenant))->post(
            route('dossiers.tenants.documents.upload', [$tenant, $documentType]),
            [
                'file' => UploadedFile::fake()->create('ine-v1.pdf', 80, 'application/pdf'),
            ],
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tenant_document_versions', [
            'tenant_document_id' => $document->id,
            'version_number' => 1,
        ]);

        $this->actingAs($user)->from(route('tenants.edit', $tenant))->post(
            route('dossiers.tenants.documents.upload', [$tenant, $documentType]),
            [
                'file' => UploadedFile::fake()->create('ine-v2.pdf', 95, 'application/pdf'),
            ],
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tenant_document_versions', [
            'tenant_document_id' => $document->id,
            'version_number' => 2,
        ]);

        $this->assertDatabaseCount('tenant_document_versions', 2);
    }

    public function test_custom_document_can_be_added_to_property_dossier(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Norte', 'slug' => 'norte', 'is_active' => true]);
        $owner = Owner::create([
            'name' => 'Owner Custom',
            'phone' => '9998887766',
            'owner_type' => Owner::OWNER_INDIVIDUAL,
            'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('properties.store'), [
            'internal_name' => 'Propiedad Custom',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Custom #2',
            'status' => Property::STATUS_AVAILABLE,
            'owner_ids' => [$owner->id],
            'facade_photo' => UploadedFile::fake()->image('facade.jpg'),
        ])->assertSessionHasNoErrors();

        $property = Property::query()->where('internal_name', 'Propiedad Custom')->firstOrFail();

        $this->actingAs($user)->from(route('dossiers.properties.show', $property))->post(
            route('dossiers.properties.documents.store', $property),
            [
                'label' => 'Contrato complementario',
                'file' => UploadedFile::fake()->create('contrato-extra.pdf', 70, 'application/pdf'),
            ],
        )->assertSessionHasNoErrors();

        $customDocument = PropertyDocument::query()
            ->where('property_id', $property->id)
            ->where('label', 'Contrato complementario')
            ->first();

        $this->assertNotNull($customDocument);
        $this->assertStringStartsWith('custom_', $customDocument->document_type);
        $this->assertDatabaseHas('property_document_versions', [
            'property_document_id' => $customDocument->id,
            'version_number' => 1,
        ]);
    }

    public function test_custom_document_can_be_added_to_tenant_dossier(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tenants.store'), [
            'full_name' => 'Tenant Custom',
            'phone_primary' => '9991212121',
            'email' => 'tenant.custom@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
        ])->assertSessionHasNoErrors();

        $tenant = Tenant::query()->where('full_name', 'Tenant Custom')->firstOrFail();

        $this->actingAs($user)->from(route('dossiers.tenants.show', $tenant))->post(
            route('dossiers.tenants.documents.store', $tenant),
            [
                'label' => 'Pagare firmado',
                'file' => UploadedFile::fake()->create('pagare.pdf', 40, 'application/pdf'),
            ],
        )->assertSessionHasNoErrors();

        $customDocument = TenantDocument::query()
            ->where('tenant_id', $tenant->id)
            ->where('label', 'Pagare firmado')
            ->first();

        $this->assertNotNull($customDocument);
        $this->assertStringStartsWith('custom_', $customDocument->document_type);
        $this->assertDatabaseHas('tenant_document_versions', [
            'tenant_document_id' => $customDocument->id,
            'version_number' => 1,
        ]);
    }

    public function test_tenant_document_metadata_can_be_updated(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tenants.store'), [
            'full_name' => 'Tenant Metadata',
            'phone_primary' => '9993434343',
            'email' => 'tenant.metadata@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
        ])->assertSessionHasNoErrors();

        $tenant = Tenant::query()->where('full_name', 'Tenant Metadata')->firstOrFail();
        $documentType = DossierDocumentRequirement::query()
            ->where('entity_type', 'tenant')
            ->orderBy('sort_order')
            ->value('document_type');

        $this->actingAs($user)->from(route('dossiers.tenants.show', $tenant))->post(
            route('dossiers.tenants.documents.upload', [$tenant, $documentType]),
            [
                'file' => UploadedFile::fake()->create('ine-original.pdf', 80, 'application/pdf'),
            ],
        )->assertSessionHasNoErrors();

        $document = TenantDocument::query()
            ->where('tenant_id', $tenant->id)
            ->where('document_type', $documentType)
            ->firstOrFail();

        $this->actingAs($user)->from(route('dossiers.tenants.show', $tenant))->patch(
            route('dossiers.tenants.documents.update', [$tenant, $documentType]),
            [
                'file_name' => 'INE ACTUALIZADA',
                'expires_at' => '2026-12-31',
            ],
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tenant_documents', [
            'id' => $document->id,
            'expires_at' => '2026-12-31 00:00:00',
        ]);

        $this->assertDatabaseHas('tenant_document_versions', [
            'tenant_document_id' => $document->id,
            'version_number' => 1,
            'original_name' => 'INE ACTUALIZADA.pdf',
        ]);
    }

    public function test_custom_tenant_document_uses_original_file_name_when_label_is_empty(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tenants.store'), [
            'full_name' => 'Tenant Label Empty',
            'phone_primary' => '9995454545',
            'email' => 'tenant.label.empty@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
        ])->assertSessionHasNoErrors();

        $tenant = Tenant::query()->where('full_name', 'Tenant Label Empty')->firstOrFail();

        $this->actingAs($user)->from(route('dossiers.tenants.show', $tenant))->post(
            route('dossiers.tenants.documents.store', $tenant),
            [
                'label' => '',
                'file' => UploadedFile::fake()->create('contrato original.pdf', 40, 'application/pdf'),
            ],
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tenant_documents', [
            'tenant_id' => $tenant->id,
            'label' => 'contrato original.pdf',
        ]);
    }
}
