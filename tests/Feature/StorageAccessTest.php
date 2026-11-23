<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_without_storage_permission_cannot_access_or_see_storage(): void
    {
        $technician = $this->userWithRole('tecnico');

        $this->actingAs($technician)
            ->get(route('storage_items.index'))
            ->assertForbidden();

        $this->actingAs($technician)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertDontSee('Almacén');
    }

    public function test_technician_with_storage_permission_can_access_and_see_storage(): void
    {
        $technician = $this->userWithRole('tecnico');
        $technician->givePermissionTo($this->storagePermission());

        $this->actingAs($technician)
            ->get(route('storage_items.index'))
            ->assertOk()
            ->assertSee('Almacén');

        $this->actingAs($technician)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Almacén');
    }

    public function test_external_provider_never_accesses_storage_even_with_permission(): void
    {
        $provider = $this->userWithRole('proveedor');
        $provider->givePermissionTo($this->storagePermission());

        $this->actingAs($provider)
            ->get(route('storage_items.index'))
            ->assertForbidden();

        $this->actingAs($provider)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertDontSee('Almacén');
    }

    private function userWithRole(string $roleName): User
    {
        Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private function storagePermission(): Permission
    {
        return Permission::query()->firstOrCreate([
            'name' => 'Ver almacén',
            'guard_name' => 'web',
        ]);
    }
}
