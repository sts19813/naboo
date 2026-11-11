<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'propiedades.archivar',
        'propiedades.eliminar',
    ];

    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('status')->index();
        });

        foreach (self::PERMISSIONS as $permissionName) {
            $permissionId = DB::table('permissions')->where([
                'name' => $permissionName,
                'guard_name' => 'web',
            ])->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $adminRoleIds = DB::table('roles')
                ->where('guard_name', 'web')
                ->whereIn('name', ['administrador', 'admin'])
                ->pluck('id');

            foreach ($adminRoleIds as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn('archived_at');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
