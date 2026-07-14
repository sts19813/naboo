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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChargeModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_charges_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('charges.index'));

        $response->assertOk();
        $response->assertSee('Cobranza');
    }

    public function test_pending_tab_excludes_paid_charges_and_keeps_other_statuses(): void
    {
        $user = User::factory()->create();
        $pendingCharge = $this->createChargeFixture();
        $chargesByStatus = collect([
            Charge::STATUS_PARTIAL,
            Charge::STATUS_IN_VALIDATION,
            Charge::STATUS_CANCELED,
            Charge::STATUS_PAID,
        ])->mapWithKeys(function (string $status) use ($pendingCharge): array {
            $charge = $pendingCharge->replicate(['uuid', 'payment_token', 'paid_at']);
            $charge->concept = 'Cargo ' . $status;
            $charge->status = $status;
            $charge->paid_amount = $status === Charge::STATUS_PAID ? $charge->amount : 0;
            $charge->paid_at = $status === Charge::STATUS_PAID ? now() : null;
            $charge->save();

            return [$status => $charge];
        });

        $paidPayment = ChargePayment::create([
            'charge_id' => $chargesByStatus[Charge::STATUS_PAID]->id,
            'amount' => $chargesByStatus[Charge::STATUS_PAID]->amount,
            'currency' => 'mxn',
            'status' => ChargePayment::STATUS_SUCCEEDED,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('charges.index'));

        $response->assertOk();

        $pendingTabCharges = $response->viewData('charges');
        $this->assertTrue($pendingTabCharges->contains($pendingCharge));
        $this->assertTrue($pendingTabCharges->contains($chargesByStatus[Charge::STATUS_PARTIAL]));
        $this->assertTrue($pendingTabCharges->contains($chargesByStatus[Charge::STATUS_IN_VALIDATION]));
        $this->assertTrue($pendingTabCharges->contains($chargesByStatus[Charge::STATUS_CANCELED]));
        $this->assertFalse($pendingTabCharges->contains($chargesByStatus[Charge::STATUS_PAID]));
        $this->assertSame(4, $response->viewData('stats')['charges_count']);
        $this->assertTrue($response->viewData('payments')->contains($paidPayment));
    }

    public function test_charge_can_be_created(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);
        $tenant = Tenant::create([
            'full_name' => 'Ana Lucia Torres',
            'phone_primary' => '9991112233',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);

        $property = Property::create([
            'internal_name' => 'Casa Centro 201',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 20 #201',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('charges.store'), [
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'type' => Charge::TYPE_RENT,
                'due_date' => now()->addDays(7)->toDateString(),
                'amount' => 18000,
                'period_month' => 3,
                'period_year' => 2026,
                'concept' => 'Renta Marzo 2026',
                'notes' => 'Prueba modulo cobranza',
            ]);

        $response->assertRedirect(route('charges.index'));
        $this->assertDatabaseHas('charges', [
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'concept' => 'Renta Marzo 2026',
            'status' => Charge::STATUS_PENDING,
        ]);
    }

    public function test_public_charge_link_is_accessible(): void
    {
        $charge = $this->createChargeFixture();

        $response = $this->get(route('charges.public.show', ['token' => $charge->payment_token]));

        $response->assertOk();
        $response->assertSee('Pagar con Stripe');
    }

    public function test_webhook_marks_charge_as_paid(): void
    {
        $charge = $this->createChargeFixture();
        config()->set('services.stripe.webhook_secret', '');

        $payload = [
            'id' => 'evt_test_charge_paid',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_charge_paid',
                    'payment_status' => 'paid',
                    'amount_total' => 1800000,
                    'currency' => 'mxn',
                    'created' => now()->timestamp,
                    'client_reference_id' => (string) $charge->id,
                    'payment_intent' => 'pi_test_123',
                    'metadata' => [
                        'charge_id' => (string) $charge->id,
                        'charge_token' => $charge->payment_token,
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('stripe.webhook'), $payload);

        $response->assertOk();
        $this->assertDatabaseHas('charges', [
            'id' => $charge->id,
            'status' => Charge::STATUS_PAID,
            'paid_amount' => 18000.00,
        ]);
        $this->assertDatabaseHas('charge_payments', [
            'charge_id' => $charge->id,
            'stripe_checkout_session_id' => 'cs_test_charge_paid',
            'status' => 'succeeded',
        ]);
    }

    public function test_admin_can_register_partial_payments_until_charge_is_paid(): void
    {
        $user = User::factory()->create();
        $charge = $this->createChargeFixture();

        $this->actingAs($user)->post(route('charges.payments.store', $charge), [
            'amount' => 5000,
            'payment_date' => now()->toDateString(),
            'payment_method' => ChargePayment::METHOD_SPEI,
            'reference' => 'SPEI-001',
        ])->assertRedirect();

        $charge->refresh();
        $this->assertSame(Charge::STATUS_PARTIAL, $charge->status);
        $this->assertEquals(5000.0, (float) $charge->paid_amount);

        $this->actingAs($user)->post(route('charges.payments.store', $charge), [
            'amount' => 13000,
            'payment_date' => now()->toDateString(),
            'payment_method' => ChargePayment::METHOD_TRANSFER,
            'reference' => 'TR-002',
        ])->assertRedirect();

        $charge->refresh();
        $this->assertSame(Charge::STATUS_PAID, $charge->status);
        $this->assertEquals(18000.0, (float) $charge->paid_amount);
    }

    public function test_public_transfer_proof_sets_charge_in_validation(): void
    {
        Storage::fake('public');
        $charge = $this->createChargeFixture();

        $response = $this->post(route('charges.public.transfer-proof', ['token' => $charge->payment_token]), [
            'amount' => 6000,
            'payment_date' => now()->toDateString(),
            'reference' => 'TRX-900',
            'receipt' => UploadedFile::fake()->image('proof.png'),
        ]);

        $response->assertRedirect(route('charges.public.show', ['token' => $charge->payment_token]));
        $this->assertDatabaseHas('charge_payments', [
            'charge_id' => $charge->id,
            'status' => ChargePayment::STATUS_PENDING_VALIDATION,
            'source' => ChargePayment::SOURCE_PUBLIC_TRANSFER,
        ]);

        $charge->refresh();
        $this->assertSame(Charge::STATUS_IN_VALIDATION, $charge->status);
    }

    public function test_admin_can_validate_transfer_proof_and_complete_charge(): void
    {
        $user = User::factory()->create();
        $charge = $this->createChargeFixture();

        $payment = ChargePayment::create([
            'charge_id' => $charge->id,
            'amount' => 18000,
            'currency' => 'mxn',
            'status' => ChargePayment::STATUS_PENDING_VALIDATION,
            'source' => ChargePayment::SOURCE_PUBLIC_TRANSFER,
            'payment_method' => ChargePayment::METHOD_TRANSFER,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->post(route('charges.payments.validate', [$charge, $payment]));

        $response->assertRedirect();
        $this->assertDatabaseHas('charge_payments', [
            'id' => $payment->id,
            'status' => ChargePayment::STATUS_SUCCEEDED,
        ]);

        $charge->refresh();
        $this->assertSame(Charge::STATUS_PAID, $charge->status);
    }

    public function test_user_without_permission_cannot_delete_a_paid_charge(): void
    {
        $user = User::factory()->create();
        $charge = $this->createChargeFixture();
        $charge->forceFill([
            'status' => Charge::STATUS_PAID,
            'paid_amount' => $charge->amount,
            'paid_at' => now(),
        ])->save();

        $this->actingAs($user)
            ->delete(route('charges.destroy', $charge), [
                'deletion_note' => 'Intento sin permiso',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('charges', ['id' => $charge->id]);

        $this->actingAs($user)
            ->get(route('charges.show', $charge))
            ->assertOk()
            ->assertDontSee('data-charge-paid="true"', false);
    }

    public function test_user_with_permission_can_delete_a_paid_charge_and_its_payments(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('cobranza.eliminar_pagados', 'web'));
        $charge = $this->createChargeFixture();
        $payment = ChargePayment::create([
            'charge_id' => $charge->id,
            'amount' => $charge->amount,
            'currency' => 'mxn',
            'status' => ChargePayment::STATUS_SUCCEEDED,
            'paid_at' => now(),
        ]);
        $charge->forceFill([
            'status' => Charge::STATUS_PAID,
            'paid_amount' => $charge->amount,
            'paid_at' => now(),
        ])->save();
        $returnUrl = route('properties.show', $charge->property) . '#tab-charges';

        $this->actingAs($user)
            ->delete(route('charges.destroy', $charge), [
                'deletion_note' => 'Pago capturado por duplicado',
                'return_to' => $returnUrl,
            ])
            ->assertRedirect($returnUrl);

        $this->assertDatabaseMissing('charges', ['id' => $charge->id]);
        $this->assertDatabaseMissing('charge_payments', ['id' => $payment->id]);
        $this->assertDatabaseHas('property_change_logs', [
            'property_id' => $charge->property_id,
            'user_id' => $user->id,
        ]);
    }

    public function test_charge_detail_returns_to_property_and_shows_paid_delete_action_with_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('cobranza.eliminar_pagados', 'web'));
        $charge = $this->createChargeFixture();
        $charge->forceFill([
            'status' => Charge::STATUS_PAID,
            'paid_amount' => $charge->amount,
            'paid_at' => now(),
        ])->save();
        $returnUrl = route('properties.show', $charge->property) . '#tab-charges';

        $response = $this->actingAs($user)->get(route('charges.show', [
            'charge' => $charge,
            'return_to' => $returnUrl,
        ]));

        $response->assertOk();
        $response->assertSee('Volver a la propiedad');
        $response->assertSee(route('charges.destroy', $charge), false);
        $response->assertSee('data-charge-paid="true"', false);
        $response->assertSee('window.Swal?.fire', false);

        $propertyResponse = $this->actingAs($user)->get(route('properties.show', $charge->property));
        $propertyResponse->assertOk();
        $propertyResponse->assertSee(route('charges.destroy', $charge), false);
        $propertyResponse->assertSee('data-charge-paid="true"', false);
    }

    public function test_tenant_sees_charges_from_all_assigned_properties_only(): void
    {
        Role::query()->firstOrCreate(['name' => 'inquilino', 'guard_name' => 'web']);

        $tenantUser = User::factory()->create(['email' => 'cliente@example.com']);
        $tenantUser->assignRole('inquilino');
        $admin = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        $tenant = Tenant::create([
            'full_name' => 'Cliente Uno',
            'phone_primary' => '9991112233',
            'email' => 'cliente@example.com',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);
        $otherTenant = Tenant::create([
            'full_name' => 'Cliente Dos',
            'phone_primary' => '9991112244',
            'email' => 'otro@example.com',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);

        $propertyA = Property::create([
            'internal_name' => 'Casa A',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle A',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
            'onboarding_step' => 5,
            'created_by' => $admin->id,
        ]);
        $propertyB = Property::create([
            'internal_name' => 'Casa B',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle B',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
            'onboarding_step' => 5,
            'created_by' => $admin->id,
        ]);
        $propertyC = Property::create([
            'internal_name' => 'Casa C',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle C',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $otherTenant->id,
            'current_tenant_name' => $otherTenant->full_name,
            'onboarding_step' => 5,
            'created_by' => $admin->id,
        ]);

        Charge::create([
            'property_id' => $propertyA->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'due_date' => now()->toDateString(),
            'amount' => 1000,
            'paid_amount' => 0,
            'period_month' => 3,
            'period_year' => 2026,
            'concept' => 'Cargo Casa A',
            'status' => Charge::STATUS_PENDING,
            'created_by' => $admin->id,
        ]);
        Charge::create([
            'property_id' => $propertyB->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'due_date' => now()->toDateString(),
            'amount' => 2000,
            'paid_amount' => 0,
            'period_month' => 4,
            'period_year' => 2026,
            'concept' => 'Cargo Casa B',
            'status' => Charge::STATUS_PENDING,
            'created_by' => $admin->id,
        ]);
        Charge::create([
            'property_id' => $propertyC->id,
            'tenant_id' => $otherTenant->id,
            'type' => Charge::TYPE_RENT,
            'due_date' => now()->toDateString(),
            'amount' => 3000,
            'paid_amount' => 0,
            'period_month' => 5,
            'period_year' => 2026,
            'concept' => 'Cargo Casa C',
            'status' => Charge::STATUS_PENDING,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($tenantUser)->get(route('charges.index'));

        $response->assertOk();
        $response->assertSee('Cargo Casa A');
        $response->assertSee('Cargo Casa B');
        $response->assertDontSee('Cargo Casa C');
    }

    private function createChargeFixture(): Charge
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Departamento', 'slug' => 'departamento', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Montebello', 'slug' => 'montebello', 'is_active' => true]);
        $tenant = Tenant::create([
            'full_name' => 'Roberto Canul',
            'phone_primary' => '9994445566',
            'email' => 'roberto@example.com',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);

        $property = Property::create([
            'internal_name' => 'Departamento 12',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 40 #12',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        return Charge::create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'due_date' => now()->toDateString(),
            'amount' => 18000,
            'paid_amount' => 0,
            'period_month' => 3,
            'period_year' => 2026,
            'concept' => 'Renta Marzo 2026',
            'status' => Charge::STATUS_PENDING,
            'created_by' => $user->id,
        ]);
    }
}
