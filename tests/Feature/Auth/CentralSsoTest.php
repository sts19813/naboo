<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CentralSsoTest extends TestCase
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
        ]);
    }

    public function test_existing_authorized_user_can_enter_with_a_central_code(): void
    {
        $user = User::factory()->create(['email' => 'sts19813@gmail.com']);
        $code = Str::random(80);

        Http::fake([
            'https://naboo.cloud/api/sso/exchange' => Http::response([
                'token_type' => 'sso_identity',
                'user' => [
                    'sub' => 'central-user-id',
                    'email' => 'sts19813@gmail.com',
                    'name' => 'Santos',
                    'avatar_url' => 'https://example.com/avatar.webp',
                    'email_verified' => true,
                    'workspace' => 'tayde',
                ],
            ]),
        ]);

        $response = $this->get(route('sso.callback', [
            'code' => $code,
            'workspace' => 'tayde',
        ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'sso_subject' => 'central-user-id',
        ]);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://naboo.cloud/api/sso/exchange'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('tayde-client:tayde-secret'))
            && $request['code'] === $code);
    }

    public function test_callback_rejects_a_workspace_other_than_the_configured_tenant(): void
    {
        Http::fake();

        $this->get(route('sso.callback', [
            'code' => Str::random(80),
            'workspace' => 'tipi',
        ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_callback_never_sends_client_credentials_over_plain_http(): void
    {
        config()->set('services.central_sso.url', 'http://naboo.cloud');
        Http::fake();

        $this->get(route('sso.callback', [
            'code' => Str::random(80),
            'workspace' => 'tayde',
        ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_callback_does_not_create_an_unknown_local_user(): void
    {
        Http::fake([
            'https://naboo.cloud/api/sso/exchange' => Http::response([
                'token_type' => 'sso_identity',
                'user' => [
                    'sub' => 'unknown-user-id',
                    'email' => 'unknown@example.com',
                    'name' => 'Unknown',
                    'avatar_url' => null,
                    'email_verified' => true,
                    'workspace' => 'tayde',
                ],
            ]),
        ]);

        $this->get(route('sso.callback', [
            'code' => Str::random(80),
            'workspace' => 'tayde',
        ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'unknown@example.com']);
    }

    public function test_callback_rejects_an_expired_or_consumed_code(): void
    {
        Http::fake([
            'https://naboo.cloud/api/sso/exchange' => Http::response([
                'error' => 'invalid_grant',
                'message' => 'El código es inválido, expiró o ya fue utilizado.',
            ], 422),
        ]);

        $this->get(route('sso.callback', [
            'code' => Str::random(80),
            'workspace' => 'tayde',
        ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
    }

    public function test_callback_rejects_an_identity_without_verified_email(): void
    {
        $user = User::factory()->create(['email' => 'sts19813@gmail.com']);

        Http::fake([
            'https://naboo.cloud/api/sso/exchange' => Http::response([
                'token_type' => 'sso_identity',
                'user' => [
                    'sub' => 'central-user-id',
                    'email' => $user->email,
                    'name' => $user->name,
                    'avatar_url' => null,
                    'email_verified' => false,
                    'workspace' => 'tayde',
                ],
            ]),
        ]);

        $this->get(route('sso.callback', [
            'code' => Str::random(80),
            'workspace' => 'tayde',
        ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertNull($user->fresh()->sso_subject);
    }
}
