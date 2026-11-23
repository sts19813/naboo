<?php
namespace Tests\Feature;

use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Owner;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_currencies_are_separated_and_manual_payments_use_property_currency(): void
    {
        $user = User::factory()->create();
        $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('administrador', 'web'));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $tenant = Tenant::create(['full_name' => 'Cliente', 'phone_primary' => '9991112233', 'is_active' => true]);
        $mxn = Property::create(['internal_name' => 'Casa MXN', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_OCCUPIED, 'tenant_id' => $tenant->id, 'billing_currency' => 'mxn']);
        $usd = Property::create(['internal_name' => 'Casa USD', 'property_type_id' => $type->id, 'full_address' => 'Calle 2', 'status' => Property::STATUS_OCCUPIED, 'tenant_id' => $tenant->id, 'billing_currency' => 'usd']);
        $make = fn ($property, $concept, $amount) => Charge::create(['property_id' => $property->id, 'tenant_id' => $tenant->id, 'type' => Charge::TYPE_RENT, 'due_date' => now(), 'amount' => $amount, 'period_month' => now()->month, 'period_year' => now()->year, 'concept' => $concept, 'status' => Charge::STATUS_PENDING]);
        $make($mxn, 'Cobro en pesos', 10000);
        $future = $make($mxn, 'Renta futura', 20000);
        $future->update(['due_date' => now()->addMonthsNoOverflow(2)->startOfMonth()]);
        $dollarCharge = $make($usd, 'Cobro en dólares', 500);
        $this->actingAs($user)->get(route('charges.index'))->assertOk()->assertSee('Cobro en pesos')->assertDontSee('Cobro en dólares')->assertSee('Moneda: MXN');
        $this->actingAs($user)->get(route('charges.index', ['currency' => 'usd']))->assertOk()->assertSee('Cobro en dólares')->assertDontSee('Cobro en pesos')->assertSee('Moneda: USD');
        $this->actingAs($user)->get(route('charges.index'))->assertViewHas('thisMonthCharges', fn ($items) => $items->pluck('id')->contains($future->id) === false);
        $this->actingAs($user)->get(route('charges.index', ['currency' => 'usd']))->assertDontSee('pending-validation-tab');
        $pending = $dollarCharge->payments()->create(['amount' => 25, 'currency' => 'usd', 'status' => ChargePayment::STATUS_PENDING_VALIDATION, 'source' => ChargePayment::SOURCE_PUBLIC_TRANSFER, 'payment_method' => ChargePayment::METHOD_TRANSFER]);
        $this->actingAs($user)->get(route('charges.index', ['currency' => 'usd']))->assertSee('pending-validation-tab')->assertSee('Por validar');
        $this->actingAs($user)->get(route('charges.index', ['currency' => 'mxn']))->assertDontSee('pending-validation-tab');
        $this->actingAs($user)->post(route('charges.payments.store', $dollarCharge), ['amount' => 100, 'payment_date' => now()->toDateString(), 'payment_method' => ChargePayment::METHOD_CASH])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('charge_payments', ['charge_id' => $dollarCharge->id, 'currency' => 'usd', 'amount' => 100]);
        $this->get(route('charges.public.show', $dollarCharge->payment_token))->assertOk()->assertSee('USD $400.00');
        config(['services.stripe.secret' => 'sk_test_example']);
        $this->get(route('charges.public.show', $dollarCharge->payment_token))->assertDontSee('Pagar con Stripe');
        config(['services.stripe.secret' => 'sk_live_example']);
        $this->get(route('charges.public.show', $dollarCharge->payment_token))->assertSee('Pagar con Stripe');
        $this->actingAs($user)->get(route('dashboard', ['billing_currency' => 'usd']))->assertOk()->assertSee('Casa USD')->assertDontSee('Casa MXN')->assertSee('Los gastos se registran en MXN');
        $this->actingAs($user)->get(route('dashboard', ['billing_currency' => 'mxn']))->assertOk()->assertSee('Casa MXN')->assertDontSee('Casa USD');
        $this->actingAs($user)->get(route('dashboard', ['property_id' => $usd->id]))->assertOk()->assertSee('Casa USD')->assertSee('Los gastos se registran en MXN');
        $result = app(\App\Services\Copilot\CopilotToolRegistry::class)->execute('list_charges', [], $user);
        $this->assertNull($result['total_amount']);
        $this->assertEquals(30000, $result['totals_by_currency']['MXN']['total_amount']);
        $this->assertEquals(500, $result['totals_by_currency']['USD']['total_amount']);
    }

    public function test_property_currency_cannot_change_after_a_charge_exists(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $tenant = Tenant::create(['full_name' => 'Cliente', 'phone_primary' => '9991112233']);
        $owner = Owner::create(['name' => 'Propietario', 'phone' => '9991112233', 'is_active' => true]);
        $property = Property::create(['internal_name' => 'Casa', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE, 'tenant_id' => $tenant->id, 'facade_photo_path' => 'properties/facade.jpg', 'billing_currency' => 'mxn']);
        $property->owners()->attach($owner->id);
        $charge = Charge::create(['property_id' => $property->id, 'tenant_id' => $tenant->id, 'type' => Charge::TYPE_RENT, 'due_date' => now(), 'amount' => 500, 'period_month' => now()->month, 'period_year' => now()->year, 'concept' => 'Renta anterior', 'status' => Charge::STATUS_PENDING]);
        $payload = ['internal_name' => 'Casa', 'property_type_id' => $type->id, 'zone_text' => 'Centro', 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE, 'owner_ids' => [$owner->id], 'billing_currency' => 'usd'];
        $this->actingAs($user)->put(route('properties.update', $property), $payload)->assertSessionHasErrors('billing_currency');
        $this->assertSame('mxn', $property->fresh()->billing_currency);
        $this->assertDatabaseHas('charges', ['id' => $charge->id, 'amount' => 500]);
    }

    public function test_stripe_payment_in_a_different_currency_is_not_applied_to_charge(): void
    {
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $tenant = Tenant::create(['full_name' => 'Cliente', 'phone_primary' => '9991112233']);
        $property = Property::create(['internal_name' => 'Casa USD', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE, 'billing_currency' => 'usd']);
        $charge = Charge::create(['property_id' => $property->id, 'tenant_id' => $tenant->id, 'type' => Charge::TYPE_RENT, 'due_date' => now(), 'amount' => 500, 'period_month' => now()->month, 'period_year' => now()->year, 'concept' => 'Renta', 'status' => Charge::STATUS_PENDING]);
        config(['services.stripe.webhook_secret' => '']);
        $this->postJson('/stripe/webhook', ['type' => 'checkout.session.completed', 'id' => 'evt-test', 'data' => ['object' => ['id' => 'cs-test', 'payment_status' => 'paid', 'amount_total' => 50000, 'currency' => 'mxn', 'metadata' => ['charge_id' => $charge->id]]]])->assertStatus(500);
        $this->assertDatabaseMissing('charge_payments', ['charge_id' => $charge->id]);
        $this->assertEquals(0, (float) $charge->fresh()->paid_amount);
    }
}
