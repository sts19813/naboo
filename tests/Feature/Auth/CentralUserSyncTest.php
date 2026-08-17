<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CentralUserSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.central_sso', [
            'url' => 'https://naboo.cloud',
            'workspace' => 'tayde',
            'client_id' => 'tayde-client',
            'client_secret' => 'tayde-secret',
            'sync_users' => true,
        ]);
    }

    public function test_new_local_user_is_provisioned_in_the_central_login(): void
    {
        Http::fake([
            'https://naboo.cloud/api/sso/provision' => Http::response([
                'user' => [
                    'sub' => '91',
                    'email' => 'nuevo@example.com',
                    'workspace' => 'tayde',
                    'access_active' => true,
                ],
            ]),
        ]);

        $user = User::factory()->create([
            'name' => 'Nuevo usuario',
            'email' => 'NUEVO@EXAMPLE.COM',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $this->assertSame('91', $user->fresh()->sso_subject);
        $this->assertFalse($user->fresh()->sso_sync_pending);
        $this->assertNotNull($user->fresh()->sso_synced_at);
        $this->assertNull($user->fresh()->sso_sync_error);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://naboo.cloud/api/sso/provision'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('tayde-client:tayde-secret'))
            && $request['subject'] === null
            && $request['email'] === 'nuevo@example.com'
            && $request['name'] === 'Nuevo usuario'
            && $request['is_active'] === true
            && Hash::check('secret123', (string) $request['password_hash']));
    }

    public function test_failed_provisioning_remains_pending_for_automatic_retry(): void
    {
        Http::fake([
            'https://naboo.cloud/api/sso/provision' => Http::response([], 503),
        ]);

        $user = User::factory()->create([
            'email' => 'pendiente@example.com',
        ]);

        $this->assertTrue($user->fresh()->sso_sync_pending);
        $this->assertNull($user->fresh()->sso_subject);
        $this->assertStringContainsString('HTTP 503', (string) $user->fresh()->sso_sync_error);
    }

    public function test_sync_command_retries_existing_pending_users(): void
    {
        config()->set('services.central_sso.sync_users', false);
        $user = User::factory()->create(['email' => 'existente@example.com']);

        config()->set('services.central_sso.sync_users', true);
        Http::fake([
            'https://naboo.cloud/api/sso/provision' => Http::response([
                'user' => [
                    'sub' => '125',
                    'email' => 'existente@example.com',
                    'workspace' => 'tayde',
                    'access_active' => true,
                ],
            ]),
        ]);

        $this->artisan('sso:sync-users')
            ->expectsOutput('Usuarios sincronizados: 1. Errores: 0.')
            ->assertSuccessful();

        $this->assertSame('125', $user->fresh()->sso_subject);
        $this->assertFalse($user->fresh()->sso_sync_pending);
    }
}
