<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
            'usuarios.gestionar',
            'expedientes.configurar',
            'expedientes.eliminar_archivos',
            'expedientes.ver_bitacora_eliminados',
            'notificaciones.configurar',
            'propiedades.control_ver',
            'propiedades.ver_propias',
            'propiedades.asignar_asesores',
            'propietarios.eliminar',
            'cobranza.eliminar_pagados',
            'administracion de tecnicos',
        ];

        $permissions = collect($permissionNames)
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        $adminRole = Role::query()->firstOrCreate([
            'name' => 'administrador',
            'guard_name' => 'web',
        ]);
        $adminRole->syncPermissions($permissions);

        Role::query()->firstOrCreate([
            'name' => 'propietario',
            'guard_name' => 'web',
        ]);
        Role::query()->firstOrCreate([
            'name' => 'inquilino',
            'guard_name' => 'web',
        ]);
        Role::query()->firstOrCreate([
            'name' => 'tecnico',
            'guard_name' => 'web',
        ]);

        $advisorRole = Role::query()->firstOrCreate([
            'name' => 'asesores',
            'guard_name' => 'web',
        ]);
        $advisorRole->syncPermissions([
            Permission::findOrCreate('propiedades.ver_propias', 'web'),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
