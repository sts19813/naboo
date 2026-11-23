<?php

namespace Tests\Feature;

use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceProviderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_advisor_can_choose_an_active_provider_from_property_ticket_form(): void
    {
        Mail::fake();
        $advisor = User::factory()->create();
        $advisor->assignRole(Role::findOrCreate('asesores', 'web'));
        $creator = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $property = Property::create(['internal_name' => 'Casa asesor', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE, 'created_by' => $creator->id]);
        $property->advisors()->attach($advisor->id);
        $active = MaintenanceProvider::create(['type' => 'proveedor', 'name' => 'Proveedor activo', 'email' => 'activo@example.test', 'is_active' => true]);
        $inactive = MaintenanceProvider::create(['type' => 'proveedor', 'name' => 'Proveedor inactivo', 'email' => 'inactivo@example.test', 'is_active' => false]);

        $this->actingAs($advisor)->get(route('properties.show', $property))->assertOk()
            ->assertSee('createPropertyMaintenanceTicketModal', false)->assertSee('Técnico o proveedor asignado')
            ->assertSee('Proveedor activo')->assertDontSee('Proveedor inactivo');
        $payload = ['property_id' => $property->id, 'category' => 'plomeria', 'priority' => 'media',
            'title' => 'Ticket elegido', 'exact_location' => 'Cocina', 'description' => 'Reparación',
            'reported_at' => now()->toDateTimeString(), 'provider_id' => $active->id];
        $this->actingAs($advisor)->post(route('maintenance.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('maintenance_tickets', ['property_id' => $property->id, 'title' => 'Ticket elegido', 'current_provider_id' => $active->id]);
        $payload['provider_id'] = $inactive->id;
        $payload['title'] = 'Ticket inválido';
        $this->actingAs($advisor)->post(route('maintenance.store'), $payload)->assertSessionHasErrorsIn('createMaintenanceTicket', 'provider_id');
        $this->assertDatabaseMissing('maintenance_tickets', ['title' => 'Ticket inválido']);
    }

    public function test_supplier_only_sees_assigned_tickets_and_can_complete_them(): void
    {
        Mail::fake();
        $supplier = User::factory()->create();
        $supplier->assignRole(Role::findOrCreate('proveedor', 'web'));
        $provider = MaintenanceProvider::create(['type' => 'proveedor', 'name' => 'Proveedor', 'email' => $supplier->email, 'user_id' => $supplier->id, 'is_active' => true]);
        $otherProvider = MaintenanceProvider::create(['type' => 'proveedor', 'name' => 'Otro proveedor', 'email' => 'otro@example.com', 'is_active' => true]);
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $property = Property::create(['internal_name' => 'Casa', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE]);
        $ticket = $this->ticket($property, $provider, 'Ticket propio');
        $otherTicket = $this->ticket($property, $otherProvider, 'Ticket ajeno');

        $this->actingAs($supplier)->get(route('maintenance.index'))->assertOk()->assertSee('Ticket propio')->assertDontSee('Ticket ajeno')->assertDontSee('Nuevo ticket');
        $this->actingAs($supplier)->get(route('maintenance.show', $otherTicket))->assertForbidden();
        $this->actingAs($supplier)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($supplier)->get(route('properties.index'))->assertForbidden();
        $this->actingAs($supplier)->get(route('storage_items.index'))->assertForbidden();
        $this->actingAs($supplier)->put(route('maintenance.costs', $ticket), ['labor_cost' => 10, 'material_cost' => 5, 'payer' => 'administracion'])->assertRedirect();
        $this->actingAs($supplier)->patch(route('maintenance.status', $ticket), ['status' => 'cancelado'])->assertForbidden();
        $this->actingAs($supplier)->patch(route('maintenance.status', $ticket), ['status' => 'completado'])->assertRedirect();
        $this->assertSame('completado', $ticket->fresh()->status);
        $this->actingAs($supplier)->put(route('maintenance.costs', $ticket), ['labor_cost' => 10, 'material_cost' => 5, 'payer' => 'administracion'])->assertStatus(409);
    }

    public function test_technician_cannot_complete_ticket_or_create_new_ticket(): void
    {
        $technician = User::factory()->create();
        $technician->assignRole(Role::findOrCreate('tecnico', 'web'));
        $provider = MaintenanceProvider::create(['type' => 'tecnico_interno', 'name' => 'Técnico', 'email' => $technician->email, 'user_id' => $technician->id, 'is_active' => true]);
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $property = Property::create(['internal_name' => 'Casa', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE]);
        $ticket = $this->ticket($property, $provider, 'Trabajo técnico');
        $this->actingAs($technician)->get(route('maintenance.show', $ticket))->assertOk()->assertDontSee('Marcar terminado')->assertDontSee('<option value="completado"', false);
        $this->actingAs($technician)->patch(route('maintenance.status', $ticket), ['status' => 'completado'])->assertForbidden();
        $this->actingAs($technician)->get(route('maintenance.index'))->assertDontSee('Nuevo ticket');
        $this->actingAs($technician)->post(route('maintenance.store'), [
            'property_id' => $property->id, 'category' => 'plomeria', 'priority' => 'media',
            'title' => 'Ticket no permitido', 'exact_location' => 'Cocina',
            'description' => 'Trabajo', 'reported_at' => now()->toDateTimeString(),
        ])->assertForbidden();
        $this->assertSame('en_proceso', $ticket->fresh()->status);
    }

    public function test_supplier_login_ignores_previous_dashboard_destination(): void
    {
        $supplier = User::factory()->create();
        $supplier->assignRole(Role::findOrCreate('proveedor', 'web'));
        $this->withSession(['url.intended' => route('dashboard')])->post('/login', ['email' => $supplier->email, 'password' => 'password'])
            ->assertRedirect(route('maintenance.index'));
    }

    private function ticket(Property $property, MaintenanceProvider $provider, string $title): MaintenanceTicket
    {
        return MaintenanceTicket::create(['property_id' => $property->id, 'current_provider_id' => $provider->id, 'reported_by_role' => 'administrador', 'reported_by_name' => 'Administrador', 'category' => 'plomeria', 'priority' => 'media', 'status' => 'en_proceso', 'title' => $title, 'exact_location' => 'Cocina', 'description' => 'Reparación', 'reported_at' => now()]);
    }
}
