<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();
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

        Property::query()->create([
            'internal_name' => 'Casa Centro',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 1',
            'status' => Property::STATUS_AVAILABLE,
            'monthly_rent_price' => 12000,
            'facade_photo_path' => 'properties/test.jpg',
            'created_by' => $user->id,
            'advisor_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel ejecutivo')
            ->assertSee('Resumen de cobranza');
    }

    public function test_advisor_dashboard_defaults_to_assigned_properties_and_can_view_all(): void
    {
        $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $advisor = User::factory()->create();
        $advisor->assignRole($advisorRole);
        $creator = User::factory()->create();
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
            'internal_name' => 'Casa Dashboard Asignada',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 1',
            'status' => Property::STATUS_OCCUPIED,
            'monthly_rent_price' => 12000,
            'created_by' => $creator->id,
        ]);
        $assignedProperty->advisors()->attach($advisor->id);

        Property::query()->create([
            'internal_name' => 'Casa Dashboard General',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 2',
            'status' => Property::STATUS_OCCUPIED,
            'monthly_rent_price' => 15000,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($advisor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Casa Dashboard Asignada')
            ->assertDontSee('Casa Dashboard General');

        $this->actingAs($advisor)
            ->get(route('dashboard', ['property_scope' => 'all']))
            ->assertOk()
            ->assertSee('Casa Dashboard Asignada')
            ->assertSee('Casa Dashboard General');
    }

    public function test_collection_kpis_match_donut_values_for_selected_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00'));

        try {
            $user = User::factory()->create();
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
            $tenant = Tenant::query()->create([
                'full_name' => 'Cliente Dashboard',
                'phone_primary' => '5555555555',
            ]);

            $property = Property::query()->create([
                'internal_name' => 'Casa Cobranza',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 3',
                'status' => Property::STATUS_OCCUPIED,
                'tenant_id' => $tenant->id,
                'monthly_rent_price' => 4500,
                'created_by' => $user->id,
            ]);

            $partialCharge = Charge::query()->create([
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => '2026-06-05',
                'amount' => 1000,
                'paid_amount' => 400,
                'period_month' => 6,
                'period_year' => 2026,
                'concept' => 'Renta Junio parcial',
                'status' => Charge::STATUS_PARTIAL,
                'created_by' => $user->id,
            ]);

            ChargePayment::query()->create([
                'charge_id' => $partialCharge->id,
                'amount' => 400,
                'status' => ChargePayment::STATUS_SUCCEEDED,
                'paid_at' => Carbon::parse('2026-06-10 09:00:00'),
            ]);

            Charge::query()->create([
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => '2026-06-20',
                'amount' => 2000,
                'paid_amount' => 0,
                'period_month' => 6,
                'period_year' => 2026,
                'concept' => 'Renta Junio pendiente',
                'status' => Charge::STATUS_PENDING,
                'created_by' => $user->id,
            ]);

            Charge::query()->create([
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => '2026-06-01',
                'amount' => 1500,
                'paid_amount' => 0,
                'period_month' => 6,
                'period_year' => 2026,
                'concept' => 'Renta Junio vencida',
                'status' => Charge::STATUS_PENDING,
                'created_by' => $user->id,
            ]);

            $this->actingAs($user)
                ->get(route('dashboard', [
                    'preset' => 'custom',
                    'start_date' => '2026-06-01',
                    'end_date' => '2026-06-30',
                ]))
                ->assertOk()
                ->assertSee('Cobrado del periodo')
                ->assertSee('Pendiente por cobrar')
                ->assertSee('Cantidad vencida del periodo')
                ->assertSee('$400.00')
                ->assertSee('$2,000.00')
                ->assertSee('$2,100.00')
                ->assertSee('series: [400,2000,2100]', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_can_filter_properties_by_advisor(): void
    {
        $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $selectedAdvisor = User::factory()->create(['name' => 'Asesora Seleccionada']);
        $otherAdvisor = User::factory()->create(['name' => 'Asesor Alterno']);
        $selectedAdvisor->assignRole($advisorRole);
        $otherAdvisor->assignRole($advisorRole);
        $viewer = User::factory()->create();
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

        Property::query()->create([
            'internal_name' => 'Casa Asesor Filtrado',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 4',
            'status' => Property::STATUS_OCCUPIED,
            'monthly_rent_price' => 12000,
            'advisor_user_id' => $selectedAdvisor->id,
            'created_by' => $viewer->id,
        ]);

        Property::query()->create([
            'internal_name' => 'Casa Otro Asesor',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 5',
            'status' => Property::STATUS_OCCUPIED,
            'monthly_rent_price' => 14000,
            'advisor_user_id' => $otherAdvisor->id,
            'created_by' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('dashboard', ['advisor_user_id' => $selectedAdvisor->id]))
            ->assertOk()
            ->assertSee('Asesor')
            ->assertSee('Asesora Seleccionada')
            ->assertSee('Casa Asesor Filtrado')
            ->assertDontSee('Casa Otro Asesor');
    }

    public function test_dashboard_advisor_filter_only_lists_admins_and_advisors(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $tenantRole = Role::query()->create(['name' => 'inquilino', 'guard_name' => 'web']);

        $viewer = User::factory()->create();

        $admin = User::factory()->create(['name' => 'Admin Dashboard Selector']);
        $admin->assignRole($adminRole);

        $advisor = User::factory()->create(['name' => 'Asesor Dashboard Selector']);
        $advisor->assignRole($advisorRole);

        $tenant = User::factory()->create(['name' => 'Inquilino Dashboard Selector']);
        $tenant->assignRole($tenantRole);

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Admin Dashboard Selector')
            ->assertSee('Asesor Dashboard Selector')
            ->assertDontSee('Inquilino Dashboard Selector');
    }

    public function test_dashboard_lists_current_month_commissions_for_all_property_responsibles(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00'));

        try {
            $viewer = User::factory()->create();
            $advisor = User::factory()->create(['name' => 'Responsable Con Cobros']);
            $advisorWithoutPayments = User::factory()->create(['name' => 'Responsable Sin Cobros']);
            $userWithoutProperties = User::factory()->create(['name' => 'Usuario Sin Propiedades']);
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
            $tenant = Tenant::query()->create([
                'full_name' => 'Cliente de Comisiones',
                'phone_primary' => '5555555555',
            ]);

            $firstProperty = Property::query()->create([
                'internal_name' => 'Propiedad Comisión Uno',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 10',
                'status' => Property::STATUS_OCCUPIED,
                'tenant_id' => $tenant->id,
                'advisor_user_id' => $advisor->id,
                'created_by' => $viewer->id,
            ]);
            $secondProperty = Property::query()->create([
                'internal_name' => 'Propiedad Comisión Dos',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 20',
                'status' => Property::STATUS_OCCUPIED,
                'tenant_id' => $tenant->id,
                'advisor_user_id' => $advisor->id,
                'created_by' => $viewer->id,
            ]);
            Property::query()->create([
                'internal_name' => 'Propiedad Sin Cobros',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 30',
                'status' => Property::STATUS_OCCUPIED,
                'tenant_id' => $tenant->id,
                'advisor_user_id' => $advisorWithoutPayments->id,
                'created_by' => $viewer->id,
            ]);

            $firstCharge = Charge::query()->create([
                'property_id' => $firstProperty->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => '2026-06-05',
                'amount' => 10000,
                'paid_amount' => 10000,
                'period_month' => 6,
                'period_year' => 2026,
                'concept' => 'Renta junio propiedad uno',
                'status' => Charge::STATUS_PAID,
                'created_by' => $viewer->id,
            ]);
            $secondCharge = Charge::query()->create([
                'property_id' => $secondProperty->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => '2026-06-08',
                'amount' => 5000,
                'paid_amount' => 5000,
                'period_month' => 6,
                'period_year' => 2026,
                'concept' => 'Renta junio propiedad dos',
                'status' => Charge::STATUS_PAID,
                'created_by' => $viewer->id,
            ]);

            ChargePayment::query()->create([
                'charge_id' => $firstCharge->id,
                'amount' => 10000,
                'status' => ChargePayment::STATUS_SUCCEEDED,
                'paid_at' => Carbon::parse('2026-06-05 09:00:00'),
            ]);
            ChargePayment::query()->create([
                'charge_id' => $secondCharge->id,
                'amount' => 5000,
                'status' => ChargePayment::STATUS_SUCCEEDED,
                'paid_at' => Carbon::parse('2026-06-08 09:00:00'),
            ]);
            ChargePayment::query()->create([
                'charge_id' => $firstCharge->id,
                'amount' => 7000,
                'status' => ChargePayment::STATUS_SUCCEEDED,
                'paid_at' => Carbon::parse('2026-05-20 09:00:00'),
            ]);
            ChargePayment::query()->create([
                'charge_id' => $secondCharge->id,
                'amount' => 3000,
                'status' => ChargePayment::STATUS_FAILED,
                'paid_at' => Carbon::parse('2026-06-10 09:00:00'),
            ]);

            $response = $this->actingAs($viewer)
                ->get(route('dashboard', [
                    'preset' => 'custom',
                    'start_date' => '2026-05-01',
                    'end_date' => '2026-05-31',
                ]))
                ->assertOk()
                ->assertSee('Comisiones de asesores')
                ->assertSee('Responsable Con Cobros')
                ->assertSee('Responsable Sin Cobros')
                ->assertDontSee('Usuario Sin Propiedades');

            $response->assertViewHas('advisorCommissions', function (Collection $commissions) use ($advisor, $advisorWithoutPayments): bool {
                $withPayments = $commissions->first(fn (array $row) => $row['advisor']->is($advisor));
                $withoutPayments = $commissions->first(fn (array $row) => $row['advisor']->is($advisorWithoutPayments));

                return $withPayments['assigned_properties_count'] === 2
                    && $withPayments['collected_properties_count'] === 2
                    && $withPayments['collected_amount'] === 15000.0
                    && $withPayments['commission_amount'] === 1500.0
                    && $withoutPayments['assigned_properties_count'] === 1
                    && $withoutPayments['collected_properties_count'] === 0
                    && $withoutPayments['collected_amount'] === 0.0
                    && $withoutPayments['commission_amount'] === 0.0;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_property_control_requires_explicit_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('properties.control'))
            ->assertForbidden();
    }

    public function test_user_with_property_control_permission_can_view_module(): void
    {
        $user = User::factory()->create();

        Permission::findOrCreate('propiedades.control_ver', 'web');
        $user->givePermissionTo('propiedades.control_ver');

        $this->actingAs($user)
            ->get(route('properties.control'))
            ->assertOk()
            ->assertSee('Control de Alta de Propiedades');
    }
}
