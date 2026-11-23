<?php

namespace Tests\Feature;

use App\Models\AirbnbFinancialEvent;
use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\IntegrationClient;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuhomesAirbnbIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['features.airbnb_integration_enabled' => true]);
    }

    public function test_integration_is_unavailable_when_disabled(): void
    {
        config(['features.airbnb_integration_enabled' => false]);
        $this->withToken($this->airbnbToken())->getJson('/api/internal/airbnb/properties')->assertNotFound();
    }

    public function test_inactive_clients_are_denied_even_with_ability(): void
    {
        $client = IntegrationClient::query()->create(['name' => 'Inactivo', 'key' => 'disabled', 'is_active' => false]);
        $token = $client->createToken('airbnb-su', ['airbnb'])->plainTextToken;
        $this->withToken($token)->getJson('/api/internal/airbnb/properties')->assertForbidden();
    }

    public function test_airbnb_property_tab_remains_hidden(): void
    {
        $property = $this->createProperty('Casa');
        $this->actingAs(User::factory()->create())->get(route('properties.show', $property))
            ->assertOk()->assertDontSee('tab-airbnb', false);
    }

    public function test_admin_can_prepare_a_vacant_property_without_exposing_a_tab(): void
    {
        $property = $this->createProperty('Casa preparada');
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('administrador', 'web'));

        $this->actingAs($admin)->patchJson(route('properties.airbnb.update', $property), ['enabled' => true])
            ->assertOk()->assertJsonPath('enabled', true);
        $this->assertSame(Property::STATUS_BLOCKED, $property->fresh()->status);
        $this->actingAs($admin)->get(route('properties.show', $property))->assertOk()->assertDontSee('tab-airbnb', false);
        $this->actingAs($admin)->patchJson(route('properties.airbnb.update', $property), ['enabled' => false])
            ->assertOk()->assertJsonPath('enabled', false);
        $this->assertSame(Property::STATUS_AVAILABLE, $property->fresh()->status);
    }

    public function test_preparing_airbnb_never_removes_a_tenant(): void
    {
        $property = $this->createProperty('Casa ocupada');
        $tenant = Tenant::query()->create(['full_name' => 'Inquilino', 'phone_primary' => '9991112233']);
        $property->update(['tenant_id' => $tenant->id, 'status' => Property::STATUS_OCCUPIED]);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('administrador', 'web'));

        $this->actingAs($admin)->patchJson(route('properties.airbnb.update', $property), ['enabled' => true])->assertUnprocessable();
        $this->assertSame($tenant->id, $property->fresh()->tenant_id);
    }


    public function test_airbnb_integration_requires_a_sanctum_token_with_airbnb_ability(): void
    {
        $this->getJson('/api/internal/airbnb/properties')->assertUnauthorized();

        $client = IntegrationClient::query()->create([
            'name' => 'Otro cliente',
            'key' => 'other',
        ]);
        $token = $client->createToken('other', ['other'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/internal/airbnb/properties')
            ->assertForbidden();
    }

    public function test_airbnb_client_can_list_claim_and_release_available_properties(): void
    {
        $property = $this->createProperty('Casa Disponible');
        $occupied = $this->createProperty('Casa Ocupada');
        $occupiedWithoutTenant = $this->createProperty('Casa Marcada Ocupada');
        $notEnabled = $this->createProperty('Casa Sin Airbnb');
        $tenant = Tenant::query()->create([
            'full_name' => 'Inquilino Uno',
            'phone_primary' => '9991112233',
            'is_active' => true,
        ]);
        $property->update(['airbnb_enabled_at' => now()]);
        $occupied->update(['airbnb_enabled_at' => now(), 'tenant_id' => $tenant->id]);
        $occupiedWithoutTenant->update(['airbnb_enabled_at' => now(), 'status' => Property::STATUS_OCCUPIED]);

        $token = $this->airbnbToken();

        $this->withToken($token)
            ->getJson('/api/internal/airbnb/properties')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $property->uuid)
            ->assertJsonMissing(['uuid' => $occupied->uuid])
            ->assertJsonMissing(['uuid' => $occupiedWithoutTenant->uuid])
            ->assertJsonMissing(['uuid' => $notEnabled->uuid]);

        $this->withToken($token)
            ->postJson("/api/internal/airbnb/properties/{$property->uuid}/claim", [
                'external_property_id' => 'airbnb-property-1',
                'external_property_code' => 'AIR-001',
                'external_property_name' => 'Casa Disponible Airbnb',
            ])
            ->assertOk()
            ->assertJsonPath('data.airbnb_external_property_id', 'airbnb-property-1')
            ->assertJsonPath('data.status', Property::STATUS_BLOCKED);

        $property->refresh();
        $this->assertNotNull($property->airbnb_enabled_at);
        $this->assertNotNull($property->airbnb_managed_at);
        $this->assertSame('airbnb-property-1', $property->airbnb_external_property_id);

        $this->withToken($token)
            ->postJson("/api/internal/airbnb/properties/{$property->uuid}/release", [
                'external_property_id' => 'airbnb-property-1',
            ])
            ->assertOk()
            ->assertJsonPath('data.airbnb_external_property_id', null)
            ->assertJsonPath('data.status', Property::STATUS_BLOCKED);
    }

    public function test_claim_rejects_properties_that_are_not_enabled_for_airbnb(): void
    {
        $property = $this->createProperty('Casa Sin Activar');

        $this->withToken($this->airbnbToken())
            ->postJson("/api/internal/airbnb/properties/{$property->uuid}/claim", [
                'external_property_id' => 'airbnb-property-disabled',
            ])
            ->assertUnprocessable();
    }

    public function test_claim_rejects_properties_marked_as_occupied_or_rented(): void
    {
        $occupied = $this->createProperty('Casa Ocupada');
        $occupied->update(['airbnb_enabled_at' => now(), 'status' => Property::STATUS_OCCUPIED]);

        $rented = $this->createProperty('Casa Rentada');
        $rented->update(['airbnb_enabled_at' => now(), 'status' => Property::STATUS_RENTED]);
        $token = $this->airbnbToken();

        $this->withToken($token)
            ->postJson("/api/internal/airbnb/properties/{$occupied->uuid}/claim", [
                'external_property_id' => 'airbnb-property-occupied',
            ])
            ->assertUnprocessable();

        $this->withToken($token)
            ->postJson("/api/internal/airbnb/properties/{$rented->uuid}/claim", [
                'external_property_id' => 'airbnb-property-rented',
            ])
            ->assertUnprocessable();
    }

    public function test_claim_rejects_properties_with_open_charges(): void
    {
        $property = $this->createProperty('Casa con Cobranza');
        $property->update(['airbnb_enabled_at' => now(), 'status' => Property::STATUS_BLOCKED]);
        $tenant = Tenant::query()->create([
            'full_name' => 'Inquilino Cargo',
            'phone_primary' => '9991112233',
            'is_active' => true,
        ]);
        Charge::query()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'due_date' => now()->toDateString(),
            'amount' => 1000,
            'paid_amount' => 0,
            'period_month' => (int) now()->month,
            'period_year' => (int) now()->year,
            'concept' => 'Renta',
            'status' => Charge::STATUS_PENDING,
        ]);

        $this->withToken($this->airbnbToken())
            ->postJson("/api/internal/airbnb/properties/{$property->uuid}/claim", [
                'external_property_id' => 'airbnb-property-open-charge',
            ])
            ->assertUnprocessable();
    }

    public function test_financial_event_creates_an_airbnbsu_charge_paid_in_full_and_is_idempotent(): void
    {
        $property = $this->createProperty('Casa Financiera');
        $property->update([
            'airbnb_enabled_at' => now(),
            'airbnb_managed_at' => now(),
            'airbnb_external_property_id' => 'airbnb-property-financial',
        ]);
        $token = $this->airbnbToken();

        $payload = [
            'property_uuid' => $property->uuid,
            'external_event_id' => 'reservation-payment-001',
            'event_type' => 'late_checkout',
            'external_reservation_id' => 'RSV-001',
            'concept' => 'Late check-out',
            'customer_name' => 'Ana Huésped',
            'amount' => 1250.50,
            'currency' => 'mxn',
            'payment_method' => ChargePayment::METHOD_CARD,
            'paid_at' => now()->toDateString(),
            'payload' => ['source' => 'airbnbsu'],
        ];

        $this->withToken($token)
            ->postJson('/api/internal/airbnb/financial-events', $payload)
            ->assertCreated()
            ->assertJsonPath('data.created', true)
            ->assertJsonPath('data.status', Charge::STATUS_PAID)
            ->assertJsonPath('data.charge.source', Charge::SOURCE_AIRBNB_SU)
            ->assertJsonPath('data.payment.status', ChargePayment::STATUS_SUCCEEDED);

        $charge = Charge::query()->sole();
        $this->assertSame(Charge::STATUS_PAID, $charge->status);
        $this->assertSame(Charge::SOURCE_AIRBNB_SU, $charge->source);
        $this->assertNull($charge->tenant_id);
        $this->assertSame('Ana Huésped', $charge->customer_name);
        $this->assertSame('1250.50', (string) $charge->paid_amount);
        $this->assertSame('Late check-out', $charge->concept);
        $this->assertDatabaseHas('charge_payments', [
            'charge_id' => $charge->id,
            'amount' => 1250.50,
            'status' => ChargePayment::STATUS_SUCCEEDED,
            'source' => ChargePayment::SOURCE_AIRBNB_SU,
            'payment_method' => ChargePayment::METHOD_CARD,
            'reference' => 'reservation-payment-001',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('Late check-out')
            ->assertSee('Airbnb SU')
            ->assertSee('Cliente: Ana Huésped');

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->postJson('/api/internal/airbnb/financial-events', array_merge($payload, ['amount' => 1300]))
            ->assertOk()
            ->assertJsonPath('data.created', false)
            ->assertJsonPath('data.charge.uuid', $charge->uuid);

        $this->assertSame(1, AirbnbFinancialEvent::query()->count());
        $this->assertSame(1, Charge::query()->count());
        $this->assertSame(1, ChargePayment::query()->count());
        $this->assertSame('1250.50', (string) AirbnbFinancialEvent::query()->firstOrFail()->amount);
    }

    public function test_financial_event_rejects_a_property_that_is_not_linked_to_airbnbsu(): void
    {
        $property = $this->createProperty('Casa sin enlace');

        $this->withToken($this->airbnbToken())
            ->postJson('/api/internal/airbnb/financial-events', $this->financialEventPayload($property))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La propiedad no está enlazada con Airbnb SU.');

        $this->assertDatabaseCount('charges', 0);
        $this->assertDatabaseCount('charge_payments', 0);
    }

    private function airbnbToken(): string
    {
        $client = IntegrationClient::query()->create([
            'name' => 'Airbnb SU',
            'key' => 'airbnbsu',
        ]);

        return $client->createToken('airbnb-su', ['airbnb'])->plainTextToken;
    }

    private function createProperty(string $name): Property
    {
        $type = PropertyType::query()->create([
            'name' => 'Casa',
            'slug' => 'casa-'.str()->random(6),
            'is_active' => true,
        ]);
        $zone = Zone::query()->create([
            'name' => 'Centro',
            'slug' => 'centro-'.str()->random(6),
            'is_active' => true,
        ]);

        return Property::query()->create([
            'internal_name' => $name,
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => $name.' 123',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
        ]);
    }

    /** @return array<string, mixed> */
    private function financialEventPayload(Property $property): array
    {
        return [
            'property_uuid' => $property->uuid,
            'external_event_id' => 'event-'.str()->uuid(),
            'event_type' => 'reservation_payment',
            'concept' => 'Pago de reservación',
            'customer_name' => 'Cliente Airbnb',
            'amount' => 500,
            'payment_method' => ChargePayment::METHOD_CARD,
        ];
    }
}
