<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAccessModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_can_access_user_access_module(): void
    {
        $role = Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('access.index'))
            ->assertOk()
            ->assertSee('Usuarios, roles y permisos');
    }

    public function test_non_admin_user_cannot_access_user_access_module(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('access.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_user_with_ajax_response(): void
    {
        $admin = $this->adminUser();
        $role = Role::query()->create(['name' => 'asesor', 'guard_name' => 'web']);
        $permission = Permission::query()->create(['name' => 'propiedades.ver', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->postJson(route('access.users.store'), [
                'name' => 'Usuario Ajax',
                'email' => 'ajax@example.com',
                'password' => 'password123',
                'is_active' => '1',
                'role_names' => [$role->name],
                'permission_names' => [$permission->name],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'tab' => 'users',
            ]);

        $created = User::query()->where('email', 'ajax@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole('asesor'));
        $this->assertTrue($created->hasDirectPermission('propiedades.ver'));
    }

    public function test_ajax_index_returns_access_partial(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('access.index', ['tab' => 'roles']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('id="access-module"', false)
            ->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_access_forms_use_the_module_handler_and_datatables_has_no_remote_language_dependency(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)
            ->get(route('access.index'))
            ->assertOk()
            ->assertSee("language: dataTablesLanguage(emptyTableMessage)", false)
            ->assertDontSee('cdn.datatables.net/plug-ins', false);

        preg_match_all('/<form\\b[^>]*data-access-form[^>]*>/', $response->getContent(), $accessForms);

        $this->assertNotEmpty($accessForms[0]);

        foreach ($accessForms[0] as $accessForm) {
            $this->assertStringContainsString('data-no-ajax', $accessForm);
        }
    }

    public function test_tenant_role_users_are_shown_in_separate_final_tab(): void
    {
        $admin = $this->adminUser();
        Role::query()->create(['name' => 'asesor', 'guard_name' => 'web']);
        Role::query()->create(['name' => 'inquilino', 'guard_name' => 'web']);

        $advisor = User::factory()->create([
            'name' => 'Usuario Administrativo Demo',
            'email' => 'administrativo@example.com',
        ]);
        $advisor->assignRole('asesor');

        $tenant = User::factory()->create([
            'name' => 'Usuario Inquilino Demo',
            'email' => 'inquilino.demo@example.com',
        ]);
        $tenant->assignRole('inquilino');

        $html = $this->actingAs($admin)
            ->get(route('access.index'))
            ->assertOk()
            ->assertSee('data-tab-key="tenants"', false)
            ->assertSee('Inquilinos')
            ->getContent();

        $usersTab = $this->htmlBetween($html, 'id="access-users-tab"', 'id="access-roles-tab"');
        $tenantsTab = $this->htmlBetween($html, 'id="access-tenants-tab"', '<div class="modal fade" id="createUserModal"');

        $this->assertStringContainsString('Usuario Administrativo Demo', $usersTab);
        $this->assertStringNotContainsString('Usuario Inquilino Demo', $usersTab);
        $this->assertStringContainsString('Usuario Inquilino Demo', $tenantsTab);
    }

    public function test_empty_tenant_table_has_no_colspan_row_incompatible_with_datatables(): void
    {
        $admin = $this->adminUser();

        $html = $this->actingAs($admin)
            ->get(route('access.index', ['tab' => 'tenants']))
            ->assertOk()
            ->assertSee('data-access-empty-message="No hay usuarios con rol inquilino."', false)
            ->getContent();

        $tenantsTab = $this->htmlBetween($html, 'id="access-tenants-tab"', '<div class="modal fade" id="createUserModal"');
        $tenantTableBody = $this->htmlBetween($tenantsTab, '<tbody>', '</tbody>');

        $this->assertStringNotContainsString('colspan=', $tenantTableBody);
        $this->assertStringNotContainsString('<tr', $tenantTableBody);
    }

    private function adminUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function htmlBetween(string $html, string $start, string $end): string
    {
        $startPosition = strpos($html, $start);
        $this->assertNotFalse($startPosition, "No se encontró el inicio {$start}.");

        $endPosition = strpos($html, $end, (int) $startPosition);
        $this->assertNotFalse($endPosition, "No se encontró el final {$end}.");

        return substr($html, (int) $startPosition, (int) $endPosition - (int) $startPosition);
    }
}
