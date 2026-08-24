<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantSuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantSuggestionModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_send_a_suggestion(): void
    {
        Role::query()->create(['name' => 'inquilino', 'guard_name' => 'web']);

        $user = User::factory()->create(['email' => 'inquilino@example.com']);
        $user->assignRole('inquilino');
        $tenant = Tenant::create([
            'full_name' => 'Inquilino Prueba',
            'phone_primary' => '9991112233',
            'email' => $user->email,
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('tenant-suggestions.create'))
            ->assertOk()
            ->assertSee('Sugerencias');

        $this->actingAs($user)
            ->post(route('tenant-suggestions.store'), [
                'title' => 'Mejorar el área común',
                'message' => 'Sería útil colocar más iluminación en la entrada.',
            ])
            ->assertRedirect(route('tenant-suggestions.create'));

        $this->assertDatabaseHas('tenant_suggestions', [
            'tenant_id' => $tenant->id,
            'sender_user_id' => $user->id,
            'title' => 'Mejorar el área común',
            'message' => 'Sería útil colocar más iluminación en la entrada.',
        ]);
    }

    public function test_only_administrators_can_access_the_mailbox(): void
    {
        Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $advisor = User::factory()->create();
        $advisor->assignRole('asesores');
        $tenant = Tenant::create([
            'full_name' => 'Inquilino del buzón',
            'phone_primary' => '9991112233',
            'email' => 'buzon@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
            'is_active' => true,
        ]);
        TenantSuggestion::create([
            'tenant_id' => $tenant->id,
            'title' => 'Sugerencia privada',
            'message' => 'Este mensaje debe estar disponible únicamente para administración.',
        ]);

        $this->actingAs($admin)
            ->get(route('mailbox.index'))
            ->assertOk()
            ->assertSee('Buzón')
            ->assertSee('Sugerencia privada')
            ->assertSee('Inquilino del buzón')
            ->assertSee('data-bs-target="#suggestionModal'.$tenant->suggestions()->firstOrFail()->id.'"', false)
            ->assertSee('Este mensaje debe estar disponible únicamente para administración.');

        $this->actingAs($advisor)
            ->get(route('mailbox.index'))
            ->assertForbidden();
    }

    public function test_non_tenant_cannot_submit_a_suggestion(): void
    {
        Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $advisor = User::factory()->create();
        $advisor->assignRole('asesores');

        $this->actingAs($advisor)
            ->post(route('tenant-suggestions.store'), [
                'title' => 'Intento no autorizado',
                'message' => 'No debe guardarse.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('tenant_suggestions', 0);
    }
}
