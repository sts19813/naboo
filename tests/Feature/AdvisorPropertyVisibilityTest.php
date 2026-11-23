<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Expense;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdvisorPropertyVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_advisor_cannot_see_or_modify_another_advisors_properties_and_finances(): void
    {
        $advisor = User::factory()->create();
        $advisor->assignRole(Role::findOrCreate('asesores', 'web'));
        $creator = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $mine = Property::create(['internal_name' => 'Casa visible', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_OCCUPIED, 'created_by' => $creator->id]);
        $mine->advisors()->attach($advisor->id);
        $other = Property::create(['internal_name' => 'Casa privada', 'property_type_id' => $type->id, 'full_address' => 'Calle 2', 'status' => Property::STATUS_AVAILABLE, 'created_by' => $creator->id]);
        $expense = Expense::create(['property_id' => $other->id, 'concept' => 'Gasto privado', 'amount' => 400, 'due_date' => now(), 'created_by' => $creator->id]);
        $tenant = Tenant::create(['full_name' => 'Inquilino', 'phone_primary' => '5555555555']);
        $charge = Charge::create(['property_id' => $other->id, 'tenant_id' => $tenant->id, 'type' => Charge::TYPE_RENT, 'due_date' => now(), 'period_month' => 1, 'period_year' => 2026, 'amount' => 800, 'paid_amount' => 0, 'concept' => 'Cargo privado', 'status' => Charge::STATUS_PENDING, 'created_by' => $creator->id]);
        $ticket = MaintenanceTicket::create(['property_id' => $other->id, 'reported_by_user_id' => $creator->id, 'reported_by_role' => 'administrador', 'reported_by_name' => $creator->name, 'category' => 'plomeria', 'priority' => 'alta', 'status' => 'pendiente', 'title' => 'Ticket privado', 'exact_location' => 'Cocina', 'description' => 'Reparación', 'reported_at' => now()]);

        $this->actingAs($advisor)->get(route('properties.index'))->assertOk()->assertSee('Casa visible')->assertDontSee('Casa privada');
        $this->actingAs($advisor)->get(route('properties.show', $other))->assertForbidden();
        $this->actingAs($advisor)->get(route('charges.index'))->assertOk()->assertDontSee('Cargo privado');
        $this->actingAs($advisor)->get(route('charges.show', $charge))->assertForbidden();
        $this->actingAs($advisor)->get(route('expenses.index'))->assertOk()->assertDontSee('Gasto privado');
        $this->actingAs($advisor)->post(route('expenses.mark-paid', $expense))->assertForbidden();
        $this->actingAs($advisor)->post(route('expenses.store'), ['property_id' => $other->id])->assertForbidden();
        $this->actingAs($advisor)->get(route('maintenance.index'))->assertOk()->assertDontSee('Ticket privado');
        $this->actingAs($advisor)->get(route('maintenance.show', $ticket))->assertForbidden();
        $this->actingAs($advisor)->get(route('dashboard', ['property_id' => $other->id]))->assertForbidden();
        $this->actingAs($advisor)->get(route('dashboard', ['property_id' => $mine->id]))
            ->assertOk()->assertViewHas('selectedPropertyId', $mine->id)
            ->assertViewHas('propertySummaries', fn ($rows) => $rows->count() === 1 && $rows->first()['property']->is($mine));
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'property_id' => $other->id]);
    }
}
