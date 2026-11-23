<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MaintenanceGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_grouped_tickets_preserve_cost_origins_and_close_together(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('administrador', 'web'));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $firstProperty = $this->property($type, 'Casa uno', $admin);
        $secondProperty = $this->property($type, 'Casa dos', $admin);
        $provider = MaintenanceProvider::create(['type' => 'tecnico_interno', 'name' => 'Técnico', 'email' => 'tecnico@example.test', 'is_active' => true]);
        $first = $this->ticket($firstProperty, $provider, 'Ticket uno', 'en_proceso');
        $second = $this->ticket($secondProperty, $provider, 'Ticket dos', 'en_proceso');
        $firstExpense = Expense::create(['property_id' => $firstProperty->id, 'concept' => 'Costo uno', 'amount' => 100, 'due_date' => now()]);
        $secondExpense = Expense::create(['property_id' => $secondProperty->id, 'concept' => 'Costo dos', 'amount' => 200, 'due_date' => now()]);
        $costOne = $first->costs()->create(['expense_id' => $firstExpense->id, 'labor_cost' => 100, 'material_cost' => 0, 'final_cost' => 100, 'currency' => 'MXN']);
        $costTwo = $second->costs()->create(['expense_id' => $secondExpense->id, 'labor_cost' => 200, 'material_cost' => 0, 'final_cost' => 200, 'currency' => 'MXN']);

        $this->actingAs($admin)->post(route('maintenance.group'), ['title' => 'Agrupación semanal', 'ticket_ids' => [$first->id, $second->id]])->assertSessionHasNoErrors();
        $master = MaintenanceTicket::where('title', 'Agrupación semanal')->firstOrFail();
        $this->assertSame($master->id, $first->fresh()->master_ticket_id);
        $this->assertSame($master->id, $second->fresh()->master_ticket_id);
        $this->assertDatabaseHas('maintenance_ticket_costs', ['id' => $costOne->id, 'ticket_id' => $master->id, 'origin_ticket_id' => $first->id, 'expense_id' => $firstExpense->id]);
        $this->assertDatabaseHas('maintenance_ticket_costs', ['id' => $costTwo->id, 'ticket_id' => $master->id, 'origin_ticket_id' => $second->id, 'expense_id' => $secondExpense->id]);
        $admin->givePermissionTo(Permission::findOrCreate('propiedades.eliminar', 'web'));
        $this->actingAs($admin)->delete(route('properties.destroy', $secondProperty))->assertSessionHasErrors('property');
        $this->assertDatabaseHas('properties', ['id' => $secondProperty->id]);
        $this->assertDatabaseHas('maintenance_ticket_costs', ['id' => $costTwo->id, 'origin_ticket_id' => $second->id]);
        $this->actingAs($admin)->get(route('maintenance.show', $master))->assertOk()->assertSee('Ticket uno')->assertSee('Ticket dos')->assertSee('Origen:');
        $this->actingAs($admin)->patch(route('maintenance.status', $first), ['status' => 'completado'])->assertStatus(409);
        $this->actingAs($admin)->patch(route('maintenance.status', $master), ['status' => 'completado'])->assertRedirect();
        $this->assertSame('completado', $first->fresh()->status);
        $this->assertSame('completado', $second->fresh()->status);
        $this->actingAs($admin)->patch(route('maintenance.status', $master), ['status' => 'cancelado'])->assertStatus(409);
        $this->actingAs($admin)->get(route('maintenance-cuts.index'))->assertOk()->assertSee('Casa uno')->assertSee('Casa dos')->assertSee('data-property-ids="'.$firstProperty->id.','.$secondProperty->id.'"', false);
        $this->actingAs($admin)->post(route('maintenance-cuts.store'), ['ticket_ids' => [$first->id]])->assertSessionHasErrors('ticket_ids');
        $this->actingAs($admin)->post(route('maintenance-cuts.store'), ['ticket_ids' => [$master->id]])->assertSessionHasNoErrors();
        $this->actingAs($admin)->get(route('maintenance-cuts.index', ['tab' => 'historial']))->assertOk()->assertSee('Ticket uno')->assertSee('Ticket dos')->assertSee('Casa dos');
        $this->assertNotNull($firstExpense->fresh()->paid_at);
        $this->assertNotNull($secondExpense->fresh()->paid_at);
    }

    public function test_grouping_rejects_already_paid_expenses(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('administrador', 'web'));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $property = $this->property($type, 'Casa', $admin);
        $provider = MaintenanceProvider::create(['type' => 'tecnico_interno', 'name' => 'Uno', 'email' => 'uno@example.test', 'is_active' => true]);
        $first = $this->ticket($property, $provider, 'Uno', 'completado');
        $second = $this->ticket($property, $provider, 'Dos', 'completado');
        $expense = Expense::create(['property_id' => $property->id, 'concept' => 'Pagado', 'amount' => 100, 'due_date' => now(), 'paid_at' => now()]);
        $cost = $first->costs()->create(['expense_id' => $expense->id, 'labor_cost' => 100, 'material_cost' => 0, 'final_cost' => 100, 'currency' => 'MXN']);
        $this->actingAs($admin)->post(route('maintenance.group'), ['title' => 'Inválido', 'ticket_ids' => [$first->id, $second->id]])->assertSessionHasErrors('ticket_ids');
        $this->assertDatabaseHas('maintenance_ticket_costs', ['id' => $cost->id, 'ticket_id' => $first->id, 'origin_ticket_id' => null]);
    }

    public function test_moving_a_ticket_moves_its_unpaid_expense_and_preserves_paid_expenses(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('administrador', 'web'));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $first = $this->property($type, 'Casa uno', $admin);
        $second = $this->property($type, 'Casa dos', $admin);
        $provider = MaintenanceProvider::create(['type' => 'tecnico_interno', 'name' => 'Técnico', 'email' => 'tecnico@example.test', 'is_active' => true]);
        $ticket = $this->ticket($first, $provider, 'Trabajo', 'en_proceso');
        $expense = Expense::create(['property_id' => $first->id, 'concept' => 'Costo', 'amount' => 100, 'due_date' => now()]);
        $ticket->costs()->create(['expense_id' => $expense->id, 'labor_cost' => 100, 'material_cost' => 0, 'final_cost' => 100, 'currency' => 'MXN']);
        $this->actingAs($admin)->patch(route('maintenance.meta', $ticket), ['property_id' => $second->id])->assertRedirect();
        $this->assertSame($second->id, $ticket->fresh()->property_id);
        $this->assertSame($second->id, $expense->fresh()->property_id);
        $expense->update(['paid_at' => now()]);
        $this->actingAs($admin)->patch(route('maintenance.meta', $ticket), ['property_id' => $first->id])->assertStatus(409);
        $this->assertSame($second->id, $ticket->fresh()->property_id);
        $this->assertSame($second->id, $expense->fresh()->property_id);
    }

    public function test_ticket_groups_paginate_independently(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('administrador', 'web'));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $property = $this->property($type, 'Casa', $admin);
        $provider = MaintenanceProvider::create(['type' => 'tecnico_interno', 'name' => 'Técnico', 'email' => 'tecnico@example.test', 'is_active' => true]);
        for ($i = 0; $i < 16; $i++) {
            $this->ticket($property, $provider, 'Pendiente '.$i, 'pendiente');
            $this->ticket($property, $provider, 'Trabajo '.$i, 'en_proceso');
        }
        $response = $this->actingAs($admin)->get(route('maintenance.index', ['attention_page' => 2]));
        $response->assertOk()->assertViewHas('ticketBuckets', fn ($groups) => $groups['attention']->currentPage() === 2 && $groups['attention']->count() === 1 && $groups['work']->currentPage() === 1 && $groups['work']->count() === 15);
        $response->assertSee('attention_page', false)->assertSee('work_page', false);
    }

    public function test_grouping_rejects_different_providers_and_does_not_move_costs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('administrador', 'web'));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $property = $this->property($type, 'Casa', $admin);
        $one = MaintenanceProvider::create(['type' => 'tecnico_interno', 'name' => 'Uno', 'email' => 'uno@example.test', 'is_active' => true]);
        $two = MaintenanceProvider::create(['type' => 'tecnico_interno', 'name' => 'Dos', 'email' => 'dos@example.test', 'is_active' => true]);
        $first = $this->ticket($property, $one, 'Uno', 'completado');
        $second = $this->ticket($property, $two, 'Dos', 'completado');
        $cost = $first->costs()->create(['labor_cost' => 100, 'material_cost' => 0, 'final_cost' => 100, 'currency' => 'MXN']);
        $this->actingAs($admin)->post(route('maintenance.group'), ['title' => 'Inválido', 'ticket_ids' => [$first->id, $second->id]])->assertSessionHasErrors('ticket_ids');
        $this->assertDatabaseHas('maintenance_ticket_costs', ['id' => $cost->id, 'ticket_id' => $first->id, 'origin_ticket_id' => null]);
        $this->assertNull($first->fresh()->master_ticket_id);
    }

    private function property(PropertyType $type, string $name, User $creator): Property
    {
        return Property::create(['internal_name' => $name, 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE, 'created_by' => $creator->id]);
    }

    private function ticket(Property $property, MaintenanceProvider $provider, string $title, string $status): MaintenanceTicket
    {
        return MaintenanceTicket::create(['property_id' => $property->id, 'current_provider_id' => $provider->id, 'reported_by_role' => 'administrador', 'reported_by_name' => 'Admin', 'category' => 'plomeria', 'priority' => 'media', 'status' => $status, 'title' => $title, 'exact_location' => 'Cocina', 'description' => 'Trabajo', 'reported_at' => now()]);
    }
}
