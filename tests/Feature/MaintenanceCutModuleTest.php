<?php

namespace Tests\Feature;

use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceCutModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_pay_completed_tickets_with_aggregated_costs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']));
        $property = $this->createProperty($admin);

        $first = $this->createTicket($property, 'Cambio de bomba', 'completado');
        $first->costs()->create([
            'labor_cost' => 500,
            'material_cost' => 250,
            'final_cost' => 750,
            'currency' => 'MXN',
        ]);
        $first->costs()->create([
            'labor_cost' => 100,
            'material_cost' => 50,
            'final_cost' => 150,
            'currency' => 'MXN',
        ]);

        $second = $this->createTicket($property, 'Reparación eléctrica', 'completado');
        $second->costs()->create([
            'labor_cost' => 300,
            'material_cost' => 700,
            'final_cost' => 1000,
            'currency' => 'MXN',
        ]);

        $this->actingAs($admin)
            ->get(route('maintenance-cuts.index'))
            ->assertOk()
            ->assertSee('Corte de mantenimiento')
            ->assertSee('Cambio de bomba')
            ->assertSee('$900.00')
            ->assertSee('$1,000.00');

        $this->actingAs($admin)
            ->post(route('maintenance-cuts.store'), ['ticket_ids' => [$first->id, $second->id]])
            ->assertRedirect(route('maintenance-cuts.index'));

        $this->assertDatabaseHas('maintenance_cuts', [
            'paid_by_user_id' => $admin->id,
            'ticket_count' => 2,
            'labor_total' => 900,
            'material_total' => 1000,
            'grand_total' => 1900,
        ]);
        $this->assertDatabaseHas('maintenance_cut_items', [
            'ticket_id' => $first->id,
            'labor_total' => 600,
            'material_total' => 300,
            'grand_total' => 900,
        ]);
        $this->assertDatabaseHas('maintenance_cut_items', [
            'ticket_id' => $second->id,
            'grand_total' => 1000,
        ]);

        $this->actingAs($admin)
            ->get(route('maintenance.index', ['tab' => 'completados']))
            ->assertOk()
            ->assertSee('Pagado');

        $this->actingAs($admin)
            ->get(route('maintenance.show', $first))
            ->assertOk()
            ->assertSee('Estos costos quedaron cerrados')
            ->assertDontSee('Agregar costo');

        $this->actingAs($admin)
            ->put(route('maintenance.costs', $first), [
                'labor_cost' => 1,
                'material_cost' => 1,
                'payer' => 'administracion',
            ])
            ->assertStatus(409);

        $this->assertCount(2, $first->fresh()->costs);
    }

    public function test_only_completed_unpaid_tickets_can_be_paid_and_only_by_administrators(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']));
        $regularUser = User::factory()->create();
        $property = $this->createProperty($admin);
        $pending = $this->createTicket($property, 'Trabajo pendiente', 'en_proceso');

        $this->actingAs($regularUser)
            ->get(route('maintenance-cuts.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('maintenance-cuts.store'), ['ticket_ids' => [$pending->id]])
            ->assertSessionHasErrors('ticket_ids');

        $this->assertDatabaseCount('maintenance_cuts', 0);
        $this->assertDatabaseCount('maintenance_cut_items', 0);
    }

    private function createTicket(Property $property, string $title, string $status): MaintenanceTicket
    {
        return MaintenanceTicket::create([
            'property_id' => $property->id,
            'category' => 'plomeria',
            'priority' => 'media',
            'status' => $status,
            'title' => $title,
            'description' => 'Descripción de prueba',
            'reported_at' => now()->subDays(3),
            'completed_at' => $status === 'completado' ? now()->subDay() : null,
        ]);
    }

    private function createProperty(User $user): Property
    {
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa-corte', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro-corte', 'is_active' => true]);

        return Property::create([
            'internal_name' => 'Casa Corte',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle de prueba 100',
            'status' => Property::STATUS_OCCUPIED,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);
    }
}
