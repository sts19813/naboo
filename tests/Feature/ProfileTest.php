<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_and_legacy_link_are_available(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/perfil')->assertOk();
        $this->actingAs($user)->get('/profile')->assertRedirect('/perfil');
        $this->assertSame(url('/perfil/actualizar'), route('profile.update'));
    }

    public function test_updating_email_resets_verification_but_unchanged_email_does_not(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from('/perfil')->post(route('profile.update'), ['name' => 'Nuevo nombre', 'email' => $user->email])->assertSessionHasNoErrors()->assertRedirect('/perfil');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->actingAs($user)->from('/perfil')->post(route('profile.update'), ['name' => 'Nuevo nombre', 'email' => 'nuevo@example.test'])->assertSessionHasNoErrors()->assertRedirect('/perfil');
        $this->assertSame('nuevo@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_existing_email_cannot_be_assigned_to_another_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->from('/perfil')->post(route('profile.update'), ['name' => $user->name, 'email' => $other->email])->assertSessionHasErrors('email');
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_old_account_deletion_endpoint_does_not_delete_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertStatus(405);
        $this->assertNotNull($user->fresh());
    }
}
