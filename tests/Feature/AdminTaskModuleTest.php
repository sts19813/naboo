<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTaskModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pending_items_by_advisor_and_technician(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-06 09:00:00'));

        try {
            $adminRole = Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
            $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
            $technicianRole = Role::query()->create(['name' => 'tecnico', 'guard_name' => 'web']);

            $admin = User::factory()->create(['name' => 'Administradora']);
            $admin->assignRole($adminRole);
            $advisor = User::factory()->create(['name' => 'Asesora Uno']);
            $advisor->assignRole($advisorRole);
            $technician = User::factory()->create(['name' => 'Técnico Uno']);
            $technician->assignRole($technicianRole);

            $type = PropertyType::query()->create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
            $zone = Zone::query()->create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);
            $tenant = Tenant::query()->create([
                'full_name' => 'Inquilina Administrativa',
                'phone_primary' => '9991112233',
            ]);
            $property = Property::query()->create([
                'internal_name' => 'Casa Administrativa',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle Centro 100',
                'status' => Property::STATUS_OCCUPIED,
                'tenant_id' => $tenant->id,
                'created_by' => $admin->id,
            ]);
            $property->advisors()->attach($advisor->id);

            $charge = Charge::query()->create([
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => now()->toDateString(),
                'amount' => 2500,
                'paid_amount' => 0,
                'period_month' => 7,
                'period_year' => 2026,
                'concept' => 'Renta administrativa',
                'status' => Charge::STATUS_PENDING,
                'created_by' => $admin->id,
            ]);

            $provider = MaintenanceProvider::query()->create([
                'user_id' => $technician->id,
                'type' => 'tecnico_interno',
                'name' => $technician->name,
                'email' => $technician->email,
                'is_active' => true,
            ]);

            $ticket = MaintenanceTicket::query()->create([
                'property_id' => $property->id,
                'reported_by_user_id' => $admin->id,
                'current_provider_id' => $provider->id,
                'reported_by_role' => 'administrador',
                'reported_by_name' => $admin->name,
                'category' => 'electricidad',
                'priority' => 'alta',
                'status' => 'asignado',
                'title' => 'Revisar tablero eléctrico',
                'description' => 'Validar interruptores',
                'reported_at' => now(),
                'assigned_at' => now(),
            ]);

            $advisorResponse = $this->actingAs($admin)
                ->get(route('admin.tasks.index', ['user_id' => $advisor->id]));

            $advisorResponse
                ->assertOk()
                ->assertSee('Pendientes administrativos')
                ->assertSee('Pendientes por usuario')
                ->assertSee('Asesora Uno')
                ->assertSee('Técnico Uno')
                ->assertSee('Asesor')
                ->assertSee('Técnico')
                ->assertSee('Casa Administrativa')
                ->assertSee('$2,500.00')
                ->assertSee('Acciones')
                ->assertSee('Abrir')
                ->assertSee(route('charges.show', $charge), false)
                ->assertDontSee('Revisar tablero eléctrico');

            $technicianResponse = $this->actingAs($admin)
                ->get(route('admin.tasks.index', ['user_id' => $technician->id]));

            $technicianResponse
                ->assertOk()
                ->assertSee('Revisar tablero eléctrico')
                ->assertSee('Ticket por programar')
                ->assertSee('Sin programar')
                ->assertSee(route('maintenance.show', $ticket), false)
                ->assertDontSee('$2,500.00');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_non_admin_cannot_open_administrative_pending_items(): void
    {
        $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $advisor = User::factory()->create();
        $advisor->assignRole($advisorRole);

        $this->actingAs($advisor)
            ->get(route('admin.tasks.index'))
            ->assertForbidden();
    }
}
