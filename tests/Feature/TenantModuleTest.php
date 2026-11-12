<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenants_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('tenants.index'));

        $response->assertOk();
        $response->assertSee('Inquilinos');
    }

    public function test_tenant_show_is_displayed(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'full_name' => 'Ana Lucia Torres',
            'phone_primary' => '9991112233',
            'email' => 'ana@example.com',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('tenants.show', $tenant));

        $response->assertOk();
        $response->assertSee('Ana Lucia Torres');
        $response->assertSee('Editar');
        $response->assertSee('Expediente');
    }

    public function test_new_tenant_form_is_empty_and_edit_form_shows_the_selected_tenant(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'full_name' => 'Inquilino Existente',
            'phone_primary' => '9991112233',
            'email' => 'existente@example.com',
            'employer' => 'Empresa Existente',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);

        foreach ([route('tenants.index') => '', route('tenants.edit', $tenant) => 'Inquilino Existente'] as $url => $expectedName) {
            $response = $this->actingAs($user)->get($url)->assertOk();
            $dom = new \DOMDocument();
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $scope = $expectedName === '' ? $xpath->query('//*[@id="createTenantModal"]')->item(0) : $dom->documentElement;
            $this->assertNotNull($scope);
            $this->assertSame($expectedName, $xpath->query('.//input[@name="full_name"]', $scope)->item(0)->getAttribute('value'));
            $this->assertSame($expectedName === '' ? '' : 'existente@example.com', $xpath->query('.//input[@name="email"]', $scope)->item(0)->getAttribute('value'));
            $this->assertSame($expectedName === '' ? '' : 'Empresa Existente', $xpath->query('.//input[@name="employer"]', $scope)->item(0)->getAttribute('value'));
        }
    }

    public function test_new_tenant_form_preserves_submitted_input_after_validation_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('tenants.index'))->post(route('tenants.store'), [
            'full_name' => 'Nombre Capturado',
        ])->assertSessionHasErrors();

        $this->get(route('tenants.index'))->assertSee('value="Nombre Capturado"', false);
    }

    public function test_tenant_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('tenants.store'), [
                'full_name' => 'Ana Lucia Torres',
                'phone_primary' => '9991112233',
                'email' => 'ana@example.com',
                'monthly_income' => 28000,
                'dossier_status' => Tenant::DOSSIER_COMPLETE,
            ]);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', ['full_name' => 'Ana Lucia Torres']);
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);
        $this->assertTrue(User::query()->where('email', 'ana@example.com')->firstOrFail()->hasRole('inquilino'));
    }

    public function test_tenant_can_be_updated(): void
    {
        $user = User::factory()->create();
        Role::query()->firstOrCreate(['name' => 'inquilino', 'guard_name' => 'web']);
        $tenant = Tenant::create([
            'full_name' => 'Roberto Canul',
            'phone_primary' => '9994445566',
            'email' => 'roberto@example.com',
            'dossier_status' => Tenant::DOSSIER_IN_REVIEW,
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'Roberto Canul',
            'email' => 'roberto@example.com',
        ])->assignRole('inquilino');

        $response = $this
            ->actingAs($user)
            ->put(route('tenants.update', $tenant), [
                'full_name' => 'Roberto Canul Dzib',
                'phone_primary' => '9994445566',
                'email' => 'roberto@example.com',
                'dossier_status' => Tenant::DOSSIER_IN_REVIEW,
                'access_password' => 'NuevaClave123',
            ]);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'full_name' => 'Roberto Canul Dzib',
        ]);
        $this->assertTrue(Hash::check('NuevaClave123', User::query()->where('email', 'roberto@example.com')->firstOrFail()->password));
    }

    public function test_tenant_edit_page_does_not_show_dossier_section(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'full_name' => 'Laura Sanchez',
            'phone_primary' => '9991234567',
            'email' => 'laura@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('tenants.edit', $tenant));

        $response->assertOk();
        $response->assertSee('Editar inquilino');
        $response->assertDontSee('Expediente del inquilino');
    }
}
