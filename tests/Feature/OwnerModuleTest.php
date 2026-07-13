<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\OwnerDocument;
use App\Models\OwnerDocumentVersion;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwnerModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('owners.index'));

        $response->assertOk();
        $response->assertSee('Propietarios');
    }

    public function test_owner_show_is_displayed(): void
    {
        $user = User::factory()->create();
        $owner = $this->createOwnerFixture();

        $response = $this
            ->actingAs($user)
            ->get(route('owners.show', $owner));

        $response->assertOk();
        $response->assertSee($owner->name);
        $response->assertSee('Editar');
        $response->assertSee('Expediente');
    }

    public function test_owner_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('owners.store'), [
                'name' => 'Carlos Mendoza',
                'phone' => '9991234567',
                'email' => 'carlos@example.com',
                'owner_type' => Owner::OWNER_INDIVIDUAL,
                'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
                'bank_name' => 'BBVA',
                'clabe' => '012180015312345678',
            ]);

        $response->assertRedirect(route('owners.index'));
        $this->assertDatabaseHas('owners', ['email' => 'carlos@example.com']);
    }

    public function test_owner_can_be_updated(): void
    {
        $user = User::factory()->create();
        $owner = Owner::create([
            'name' => 'Sofia Herrera',
            'phone' => '9997654321',
            'email' => 'sofia@example.com',
            'owner_type' => Owner::OWNER_INDIVIDUAL,
            'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('owners.update', $owner), [
                'name' => 'Sofia Herrera del Valle',
                'phone' => '9997654321',
                'email' => 'sofia@example.com',
                'owner_type' => Owner::OWNER_INDIVIDUAL,
                'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            ]);

        $response->assertRedirect(route('owners.index'));
        $this->assertDatabaseHas('owners', ['id' => $owner->id, 'name' => 'Sofia Herrera del Valle']);
    }

    public function test_user_without_permission_cannot_delete_owner(): void
    {
        $user = User::factory()->create();
        $owner = $this->createOwnerFixture();

        $this->actingAs($user)
            ->delete(route('owners.destroy', $owner))
            ->assertForbidden();

        $this->assertDatabaseHas('owners', ['id' => $owner->id]);
    }

    public function test_advisor_with_permission_can_delete_owner_and_detach_properties_and_dossier(): void
    {
        Storage::fake('public');

        Permission::findOrCreate('propietarios.eliminar', 'web');
        $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $advisor = User::factory()->create();
        $advisor->assignRole($advisorRole);
        $advisor->givePermissionTo('propietarios.eliminar');

        $owner = $this->createOwnerFixture();
        $property = $this->createPropertyFixture($advisor);
        $property->owners()->attach($owner->id);

        $document = OwnerDocument::query()->create([
            'owner_id' => $owner->id,
            'document_type' => 'identificacion',
            'label' => 'Identificación oficial',
            'status' => OwnerDocument::STATUS_UPLOADED,
            'uploaded_at' => now(),
            'file_path' => 'owners/' . $owner->id . '/documents/ine.pdf',
        ]);

        Storage::disk('public')->put('owners/' . $owner->id . '/documents/ine.pdf', 'fake-file-content');

        $version = OwnerDocumentVersion::query()->create([
            'owner_document_id' => $document->id,
            'version_number' => 1,
            'file_path' => 'owners/' . $owner->id . '/documents/ine.pdf',
            'original_name' => 'ine.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'uploaded_by' => $advisor->id,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($advisor)
            ->delete(route('owners.destroy', $owner))
            ->assertRedirect(route('owners.index'));

        $this->assertDatabaseMissing('owners', ['id' => $owner->id]);
        $this->assertDatabaseHas('properties', ['id' => $property->id]);
        $this->assertDatabaseMissing('owner_property', [
            'owner_id' => $owner->id,
            'property_id' => $property->id,
        ]);
        $this->assertDatabaseMissing('owner_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('owner_document_versions', ['id' => $version->id]);
        Storage::disk('public')->assertMissing('owners/' . $owner->id . '/documents/ine.pdf');
        $this->assertDatabaseHas('dossier_deleted_files', [
            'entity_type' => 'owner',
            'entity_id' => $owner->id,
            'document_group' => 'owner',
            'document_id' => $document->id,
            'version_id' => $version->id,
            'original_name' => 'ine.pdf',
            'deleted_by_user_id' => $advisor->id,
            'file_deleted' => true,
        ]);
    }

    private function createOwnerFixture(): Owner
    {
        return Owner::query()->create([
            'name' => 'Sofia Herrera',
            'phone' => '9997654321',
            'email' => 'sofia@example.com',
            'owner_type' => Owner::OWNER_INDIVIDUAL,
            'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            'is_active' => true,
        ]);
    }

    private function createPropertyFixture(User $user): Property
    {
        $type = PropertyType::query()->create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::query()->create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        return Property::query()->create([
            'internal_name' => 'Casa con propietario',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 1 #101',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);
    }
}
