<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdvisorTaskModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_advisor_tasks_only_show_items_for_assigned_properties(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 09:00:00'));

        try {
            $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
            $advisor = User::factory()->create(['name' => 'Asesora Pendientes']);
            $advisor->assignRole($advisorRole);
            $creator = User::factory()->create();
            $tenant = Tenant::query()->create([
                'full_name' => 'Inquilino Visible',
                'phone_primary' => '5555555555',
            ]);
            $hiddenTenant = Tenant::query()->create([
                'full_name' => 'Inquilino Oculto',
                'phone_primary' => '5555555556',
            ]);
            $type = PropertyType::query()->create([
                'name' => 'Casa',
                'slug' => 'casa',
                'is_active' => true,
            ]);
            $zone = Zone::query()->create([
                'name' => 'Centro',
                'slug' => 'centro',
                'is_active' => true,
            ]);

            $assignedProperty = Property::query()->create([
                'internal_name' => 'Casa Pendientes Visible',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle visible',
                'status' => Property::STATUS_OCCUPIED,
                'tenant_id' => $tenant->id,
                'monthly_rent_price' => 12000,
                'facade_photo_path' => 'properties/facades/casa-pendientes-visible.jpg',
                'contract_expires_at' => now()->addDays(6)->toDateString(),
                'created_by' => $creator->id,
            ]);
            $assignedProperty->advisors()->attach($advisor->id);

            $hiddenProperty = Property::query()->create([
                'internal_name' => 'Casa Pendientes Oculta',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle oculta',
                'status' => Property::STATUS_OCCUPIED,
                'tenant_id' => $hiddenTenant->id,
                'monthly_rent_price' => 14000,
                'created_by' => $creator->id,
            ]);

            Charge::query()->create([
                'property_id' => $assignedProperty->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => '2026-06-20',
                'amount' => 12000,
                'paid_amount' => 0,
                'period_month' => 6,
                'period_year' => 2026,
                'concept' => 'Renta Visible',
                'status' => Charge::STATUS_PENDING,
                'created_by' => $creator->id,
            ]);

            Charge::query()->create([
                'property_id' => $assignedProperty->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => '2026-05-10',
                'amount' => 555,
                'paid_amount' => 0,
                'period_month' => 5,
                'period_year' => 2026,
                'concept' => 'Renta Antigua Visible',
                'status' => Charge::STATUS_PENDING,
                'created_by' => $creator->id,
            ]);

            Charge::query()->create([
                'property_id' => $assignedProperty->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => '2026-06-24',
                'amount' => 777,
                'paid_amount' => 0,
                'period_month' => 6,
                'period_year' => 2026,
                'concept' => 'Renta Hoy Visible',
                'status' => Charge::STATUS_PENDING,
                'created_by' => $creator->id,
            ]);

            Charge::query()->create([
                'property_id' => $hiddenProperty->id,
                'tenant_id' => $hiddenTenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => '2026-06-20',
                'amount' => 14000,
                'paid_amount' => 0,
                'period_month' => 6,
                'period_year' => 2026,
                'concept' => 'Renta Oculta',
                'status' => Charge::STATUS_PENDING,
                'created_by' => $creator->id,
            ]);

            MaintenanceTicket::query()->create([
                'property_id' => $assignedProperty->id,
                'reported_by_user_id' => $creator->id,
                'reported_by_role' => 'administrador',
                'reported_by_name' => $creator->name,
                'category' => 'plomeria',
                'priority' => 'alta',
                'status' => 'programado',
                'title' => 'Visita Visible',
                'description' => 'Revisar fuga',
                'reported_at' => now(),
                'scheduled_visit_at' => now()->addDay(),
            ]);

            MaintenanceTicket::query()->create([
                'property_id' => $assignedProperty->id,
                'reported_by_user_id' => $creator->id,
                'reported_by_role' => 'administrador',
                'reported_by_name' => $creator->name,
                'category' => 'plomeria',
                'priority' => 'alta',
                'status' => 'programado',
                'title' => 'Visita Atrasada Hoy',
                'description' => 'Visita vencida el mismo dia',
                'reported_at' => now(),
                'scheduled_visit_at' => now()->subHour(),
            ]);

            MaintenanceTicket::query()->create([
                'property_id' => $hiddenProperty->id,
                'reported_by_user_id' => $creator->id,
                'reported_by_role' => 'administrador',
                'reported_by_name' => $creator->name,
                'category' => 'electricidad',
                'priority' => 'alta',
                'status' => 'programado',
                'title' => 'Visita Oculta',
                'description' => 'Revisar centro de carga',
                'reported_at' => now(),
                'scheduled_visit_at' => now()->addDay(),
            ]);

            PropertyDocument::query()->create([
                'property_id' => $assignedProperty->id,
                'document_type' => 'insurance',
                'label' => 'Poliza Visible',
                'status' => PropertyDocument::STATUS_APPROVED,
                'expires_at' => now()->addDays(5)->toDateString(),
            ]);

            $response = $this->actingAs($advisor)
                ->get(route('advisor.tasks.index'))
                ->assertOk()
                ->assertSee('Mis pendientes')
                ->assertSee('Nombre de la propiedad')
                ->assertSee('Tipo del asunto')
                ->assertSee('Tiempo')
                ->assertSee('Fecha')
                ->assertSee('Acciones')
                ->assertSee('Abrir')
                ->assertSee('Casa Pendientes Visible')
                ->assertSee('Cobranza')
                ->assertSee('Ticket de mantenimiento')
                ->assertSee('/storage/properties/facades/casa-pendientes-visible.jpg', false)
                ->assertSee('$777.00')
                ->assertSee('$12,000.00')
                ->assertSee('$555.00')
                ->assertSee('Cobro vencido')
                ->assertSee('Hace 4 días')
                ->assertSee('20 jun. 2026')
                ->assertSee('Visita Atrasada Hoy')
                ->assertSee('Urgente')
                ->assertDontSee('Visita Visible')
                ->assertDontSee('Poliza Visible')
                ->assertDontSee('Casa Pendientes Oculta')
                ->assertDontSee('Renta Oculta')
                ->assertDontSee('Visita Oculta');

            $todayResponse = $this->actingAs($advisor)
                ->get(route('advisor.tasks.index', ['range' => 'today']));

            $todayResponse
                ->assertOk()
                ->assertSee('Mis pendientes')
                ->assertSee('$777.00')
                ->assertSee('$12,000.00')
                ->assertSee('$555.00')
                ->assertSee('Visita Atrasada Hoy')
                ->assertDontSee('Visita Visible')
                ->assertDontSee('Poliza Visible');

            $this->actingAs($advisor)
                ->get(route('advisor.tasks.index', ['range' => 'current_week']))
                ->assertOk()
                ->assertSee('$555.00')
                ->assertSee('$12,000.00')
                ->assertSee('Visita Visible')
                ->assertDontSee('Poliza Visible');

            $monthResponse = $this->actingAs($advisor)
                ->get(route('advisor.tasks.index', ['range' => 'current_month']));

            $monthResponse
                ->assertOk()
                ->assertSee('$555.00')
                ->assertSee('Visita Visible')
                ->assertSee('Poliza Visible')
                ->assertSee('Vencimiento de documento')
                ->assertSee('Documento por vencer: Poliza Visible')
                ->assertSee('Vencimiento de contrato')
                ->assertSee('Contrato por vencer')
                ->assertSee('En 6 días')
                ->assertSee('30 jun. 2026')
                ->assertDontSee('Visita Oculta');
        } finally {
            Carbon::setTestNow();
        }
    }
}
