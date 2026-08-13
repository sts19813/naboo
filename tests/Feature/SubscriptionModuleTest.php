<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.central_sso.url', 'https://naboo.cloud');
        config()->set('services.central_sso.workspace', 'demo');
        config()->set('services.central_sso.client_id', 'client-demo');
        config()->set('services.central_sso.client_secret', 'secret-demo');
    }

    public function test_admin_sees_subscription_summary_and_central_payment_link(): void
    {
        Http::fake([
            'https://naboo.cloud/api/billing/usage' => Http::response([
                'usage' => [
                    'properties' => 20,
                    'rented_properties' => 10,
                    'vacant_properties' => 10,
                ],
                'billing' => [
                    'calculated_amount' => 60000,
                    'subscription_status' => null,
                ],
            ]),
        ]);

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('subscription.index'))
            ->assertOk()
            ->assertSee('Suscripción de esta instancia')
            ->assertSee('$600.00')
            ->assertSee('https://naboo.cloud/espacios/demo/suscripcion', false)
            ->assertSee('Suscripción');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://naboo.cloud/api/billing/usage'
                && $request['property_count'] === 0
                && $request['rented_property_count'] === 0
                && $request->hasHeader('Authorization');
        });
    }

    public function test_non_admin_cannot_view_subscription_module(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->get(route('subscription.index'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    private function adminUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
