<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PropertyLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_users_with_archive_permission_can_archive_view_and_restore_properties(): void
    {
        $user = User::factory()->create();
        $property = $this->createProperty($user);
        $this->actingAs($user)->patch(route('properties.archive', $property))->assertForbidden();
        $this->actingAs($user)->delete(route('properties.destroy', $property))->assertForbidden();
        Permission::findOrCreate('propiedades.archivar', 'web');
        $user->givePermissionTo('propiedades.archivar');
        $this->actingAs($user)->patch(route('properties.archive', $property))->assertRedirect(route('properties.index'));
        $this->assertNotNull($property->fresh()->archived_at);
        $this->actingAs($user)->get(route('properties.index'))->assertDontSee($property->internal_name);
        $this->actingAs($user)->get(route('properties.index', ['view' => 'archived']))->assertOk()->assertSee('Propiedades archivadas')->assertSee($property->internal_name);
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser)->get(route('properties.index', ['view' => 'archived']))->assertForbidden();
        $this->actingAs($unauthorizedUser)->get(route('properties.show', $property))->assertForbidden();
        $this->actingAs($user)->patch(route('properties.restore', $property))->assertRedirect(route('properties.index', ['view' => 'archived']));
        $this->assertNull($property->fresh()->archived_at);
    }

    public function test_delete_is_blocked_by_pending_payments_and_then_removes_only_property_data(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $user = User::factory()->create();
        Permission::findOrCreate('propiedades.eliminar', 'web');
        $user->givePermissionTo('propiedades.eliminar');
        $tenant = Tenant::query()->create(['full_name' => 'Inquilino conservado', 'phone_primary' => '9991112233']);
        $owner = Owner::query()->create(['name' => 'Propietario conservado', 'phone' => '9992223344', 'is_active' => true]);
        $property = $this->createProperty($user, $tenant);
        $property->owners()->attach($owner->id);
        $charge = Charge::query()->create([
            'property_id' => $property->id, 'tenant_id' => $tenant->id, 'type' => Charge::TYPE_RENT,
            'due_date' => now()->toDateString(), 'amount' => 1200, 'paid_amount' => 0,
            'period_month' => now()->month, 'period_year' => now()->year, 'concept' => 'Renta pendiente',
            'status' => Charge::STATUS_PENDING, 'created_by' => $user->id,
        ]);
        $this->actingAs($user)->from(route('properties.show', $property))->delete(route('properties.destroy', $property))
            ->assertRedirect(route('properties.show', $property))->assertSessionHasErrors('property');
        $this->assertDatabaseHas('properties', ['id' => $property->id]);

        $charge->update(['status' => Charge::STATUS_PAID, 'paid_amount' => 1200, 'paid_at' => now()]);
        $receiptPath = "charges/{$charge->id}/payments/recibo.png";
        $payment = ChargePayment::query()->create(['charge_id' => $charge->id, 'amount' => 1200, 'status' => ChargePayment::STATUS_SUCCEEDED, 'receipt_path' => $receiptPath, 'paid_at' => now(), 'registered_by' => $user->id]);
        $documentPath = "properties/{$property->id}/documents/contrato.pdf";
        $documentId = DB::table('property_documents')->insertGetId(['property_id' => $property->id, 'document_type' => 'contrato', 'label' => 'Contrato', 'file_path' => $documentPath, 'status' => 'complete', 'created_at' => now(), 'updated_at' => now()]);
        $areaId = DB::table('property_inventory_areas')->insertGetId(['property_id' => $property->id, 'name' => 'Sala', 'created_at' => now(), 'updated_at' => now()]);
        $inventoryPath = "properties/{$property->id}/inventory/sala.png";
        DB::table('property_inventory_photos')->insert(['property_inventory_area_id' => $areaId, 'file_path' => $inventoryPath, 'display_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        Storage::disk('public')->put($documentPath, 'documento');
        Storage::disk('public')->put($inventoryPath, 'foto');
        Storage::disk('public')->put($receiptPath, 'recibo');
        $this->actingAs($user)->delete(route('properties.destroy', $property))->assertRedirect(route('properties.index'));
        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
        $this->assertDatabaseMissing('property_documents', ['id' => $documentId]);
        $this->assertDatabaseMissing('property_inventory_areas', ['id' => $areaId]);
        $this->assertDatabaseMissing('charges', ['id' => $charge->id]);
        $this->assertDatabaseMissing('charge_payments', ['id' => $payment->id]);
        $this->assertDatabaseHas('owners', ['id' => $owner->id]);
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
        Storage::disk('public')->assertMissing($documentPath);
        Storage::disk('public')->assertMissing($inventoryPath);
        Storage::disk('public')->assertMissing($receiptPath);
    }

    private function createProperty(User $user, ?Tenant $tenant = null): Property
    {
        $type = PropertyType::query()->firstOrCreate(['slug' => 'casa'], ['name' => 'Casa', 'is_active' => true]);

        return Property::query()->create([
            'internal_name' => 'Casa ciclo de vida', 'property_type_id' => $type->id,
            'full_address' => 'Calle de prueba 123', 'status' => $tenant ? Property::STATUS_OCCUPIED : Property::STATUS_AVAILABLE,
            'tenant_id' => $tenant?->id, 'current_tenant_name' => $tenant?->full_name, 'created_by' => $user->id,
        ]);
    }
}
