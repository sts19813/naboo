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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashCutModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_receive_cash_payments_and_keep_the_historical_snapshot(): void
    {
        $admin = $this->userWithRole('Administradora Receptora', 'administrador');
        $advisor = $this->userWithRole('Asesor de Cobranza', 'asesores');
        $charge = $this->createCharge($advisor, 'Renta septiembre 2026');
        $first = $this->createPayment($charge, $advisor, ChargePayment::METHOD_CASH, 7500);
        $second = $this->createPayment($charge, $advisor, ChargePayment::METHOD_CASH, 2500);

        $this->actingAs($admin)
            ->get(route('charges.index'))
            ->assertOk()
            ->assertSee(route('cash-cuts.index'), false);

        $this->actingAs($admin)
            ->get(route('cash-cuts.index'))
            ->assertOk()
            ->assertSee('Corte de efectivo')
            ->assertSee('Renta septiembre 2026')
            ->assertSee('Asesor de Cobranza')
            ->assertSee('$7,500.00')
            ->assertSee('window.Swal.fire', false)
            ->assertSee('Confirmar recepción')
            ->assertDontSee('window.confirm', false);

        $this->actingAs($admin)
            ->post(route('cash-cuts.store'), ['payment_ids' => [$first->id, $second->id]])
            ->assertRedirect(route('cash-cuts.index', ['tab' => 'historial']));

        $this->assertDatabaseHas('cash_cuts', [
            'advisor_user_id' => $advisor->id,
            'advisor_name' => 'Asesor de Cobranza',
            'received_by_user_id' => $admin->id,
            'received_by_name' => 'Administradora Receptora',
            'payment_count' => 2,
            'grand_total' => 10000,
        ]);
        $this->assertDatabaseHas('cash_cut_items', [
            'charge_payment_id' => $first->id,
            'charge_concept' => 'Renta septiembre 2026',
            'property_name' => 'Departamento Corte',
            'tenant_name' => 'Inquilina Corte',
            'amount' => 7500,
        ]);

        $this->actingAs($admin)
            ->get(route('cash-cuts.index'))
            ->assertOk()
            ->assertSee('CORTE-EF-000001')
            ->assertSee('Recibido')
            ->assertSee('$10,000.00');

        $charge->delete();

        $this->assertDatabaseHas('cash_cut_items', [
            'charge_payment_id' => null,
            'charge_concept' => 'Renta septiembre 2026',
        ]);
        $this->actingAs($admin)
            ->get(route('cash-cuts.index', ['tab' => 'historial']))
            ->assertOk()
            ->assertSee('Renta septiembre 2026')
            ->assertSee('Departamento Corte');
    }

    public function test_only_successful_cash_payments_are_pending_for_a_cut(): void
    {
        $admin = $this->userWithRole('Administradora', 'administrador');
        $advisor = $this->userWithRole('Asesor', 'asesores');
        $cashCharge = $this->createCharge($advisor, 'Pago visible en efectivo');
        $cardCharge = $this->createCharge($advisor, 'Pago con tarjeta oculto');
        $pendingCharge = $this->createCharge($advisor, 'Efectivo sin validar oculto');
        $cash = $this->createPayment($cashCharge, $advisor, ChargePayment::METHOD_CASH, 1800);
        $this->createPayment($cardCharge, $advisor, ChargePayment::METHOD_CARD, 900);
        $this->createPayment($pendingCharge, $advisor, ChargePayment::METHOD_CASH, 400, ChargePayment::STATUS_PENDING_VALIDATION);

        $this->actingAs($admin)
            ->get(route('cash-cuts.index'))
            ->assertOk()
            ->assertSee('Pago visible en efectivo')
            ->assertDontSee('Pago con tarjeta oculto')
            ->assertDontSee('Efectivo sin validar oculto');

        $this->actingAs($admin)
            ->post(route('cash-cuts.store'), ['payment_ids' => [$cash->id]])
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($admin)
            ->post(route('cash-cuts.store'), ['payment_ids' => [$cash->id]])
            ->assertSessionHasErrors('payment_ids');

        $this->assertDatabaseCount('cash_cuts', 1);
        $this->assertDatabaseCount('cash_cut_items', 1);
    }

    public function test_payments_from_different_advisors_cannot_share_a_cut(): void
    {
        $admin = $this->userWithRole('Administradora', 'administrador');
        $firstAdvisor = $this->userWithRole('Asesora Uno', 'asesores');
        $secondAdvisor = $this->userWithRole('Asesor Dos', 'asesores');
        $first = $this->createPayment($this->createCharge($firstAdvisor, 'Renta uno'), $firstAdvisor, ChargePayment::METHOD_CASH, 1000);
        $second = $this->createPayment($this->createCharge($secondAdvisor, 'Renta dos'), $secondAdvisor, ChargePayment::METHOD_CASH, 2000);

        $this->actingAs($admin)
            ->post(route('cash-cuts.store'), ['payment_ids' => [$first->id, $second->id]])
            ->assertSessionHasErrors('payment_ids');

        $this->assertDatabaseCount('cash_cuts', 0);
    }

    public function test_cash_cut_is_only_visible_and_actionable_by_administrators(): void
    {
        $advisor = $this->userWithRole('Asesor sin acceso', 'asesores');
        $charge = $this->createCharge($advisor, 'Renta restringida');
        $payment = $this->createPayment($charge, $advisor, ChargePayment::METHOD_CASH, 1500);

        $this->actingAs($advisor)
            ->get(route('charges.index'))
            ->assertOk()
            ->assertDontSee(route('cash-cuts.index'), false);

        $this->actingAs($advisor)->get(route('cash-cuts.index'))->assertForbidden();
        $this->actingAs($advisor)
            ->post(route('cash-cuts.store'), ['payment_ids' => [$payment->id]])
            ->assertForbidden();

        $this->assertDatabaseCount('cash_cuts', 0);
    }

    private function userWithRole(string $name, string $roleName): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole($role);

        return $user;
    }

    private function createCharge(User $creator, string $concept): Charge
    {
        $type = PropertyType::firstOrCreate(
            ['slug' => 'departamento-corte'],
            ['name' => 'Departamento', 'is_active' => true],
        );
        $zone = Zone::firstOrCreate(
            ['slug' => 'zona-corte'],
            ['name' => 'Zona Corte', 'is_active' => true],
        );
        $tenant = Tenant::create([
            'full_name' => 'Inquilina Corte',
            'phone_primary' => '9990000000',
            'is_active' => true,
        ]);
        $property = Property::create([
            'internal_name' => 'Departamento Corte',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle del Corte 100',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
            'onboarding_step' => 5,
            'created_by' => $creator->id,
        ]);

        return Charge::create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'due_date' => now()->toDateString(),
            'amount' => 10000,
            'paid_amount' => 0,
            'period_month' => now()->month,
            'period_year' => now()->year,
            'concept' => $concept,
            'status' => Charge::STATUS_PENDING,
            'created_by' => $creator->id,
        ]);
    }

    private function createPayment(
        Charge $charge,
        User $advisor,
        string $method,
        float $amount,
        string $status = ChargePayment::STATUS_SUCCEEDED,
    ): ChargePayment {
        return ChargePayment::create([
            'charge_id' => $charge->id,
            'amount' => $amount,
            'currency' => 'mxn',
            'status' => $status,
            'source' => ChargePayment::SOURCE_ADMIN,
            'payment_method' => $method,
            'payment_date' => now()->toDateString(),
            'paid_at' => $status === ChargePayment::STATUS_SUCCEEDED ? now() : null,
            'registered_by' => $advisor->id,
        ]);
    }
}
