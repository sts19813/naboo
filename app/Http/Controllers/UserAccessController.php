<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserAccessController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAccess($request);

        $data = $this->accessData($request);

        if ($request->ajax() && !$request->wantsJson()) {
            return view('access.partials.module', $data);
        }

        return view('access.index', $data);
    }

    public function storeUser(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'role_names' => ['nullable', 'array'],
            'role_names.*' => ['string', Rule::exists('roles', 'name')],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $user = User::query()->create([
            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'password' => (string) $validated['password'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);
        $user->syncRoles($validated['role_names'] ?? []);
        $user->syncPermissions($validated['permission_names'] ?? []);

        return $this->respond($request, 'Usuario creado correctamente.', 'users');
    }

    public function updateUser(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'role_names' => ['nullable', 'array'],
            'role_names.*' => ['string', Rule::exists('roles', 'name')],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $payload = [
            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
        if (filled($validated['password'] ?? null)) {
            $payload['password'] = (string) $validated['password'];
        }

        $user->update($payload);
        $user->syncRoles($validated['role_names'] ?? []);
        $user->syncPermissions($validated['permission_names'] ?? []);

        return $this->respond($request, 'Usuario actualizado correctamente.', 'users');
    }

    public function storeRole(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:roles,name'],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::query()->create([
            'name' => trim((string) $validated['name']),
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($validated['permission_names'] ?? []);

        return $this->respond($request, 'Rol creado correctamente.', 'roles');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('roles', 'name')->ignore($role->id)],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role->update([
            'name' => trim((string) $validated['name']),
        ]);
        $role->syncPermissions($validated['permission_names'] ?? []);

        return $this->respond($request, 'Rol actualizado correctamente.', 'roles');
    }

    public function destroyRole(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        $this->ensureAccess($request);

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'No puedes eliminar un rol que todavía tiene usuarios asignados.',
            ]);
        }

        $role->delete();

        return $this->respond($request, 'Rol eliminado correctamente.', 'roles');
    }

    public function storePermission(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:permissions,name'],
        ]);

        Permission::query()->create([
            'name' => trim((string) $validated['name']),
            'guard_name' => 'web',
        ]);

        return $this->respond($request, 'Permiso creado correctamente.', 'permissions');
    }

    public function updatePermission(Request $request, Permission $permission): RedirectResponse|JsonResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ]);

        $permission->update([
            'name' => trim((string) $validated['name']),
        ]);

        return $this->respond($request, 'Permiso actualizado correctamente.', 'permissions');
    }

    public function destroyPermission(Request $request, Permission $permission): RedirectResponse|JsonResponse
    {
        $this->ensureAccess($request);

        if ($permission->roles()->exists()) {
            throw ValidationException::withMessages([
                'permission' => 'No puedes eliminar un permiso que todavía está asignado a roles.',
            ]);
        }

        if (method_exists($permission, 'users') && $permission->users()->exists()) {
            throw ValidationException::withMessages([
                'permission' => 'No puedes eliminar un permiso que todavía está asignado a usuarios.',
            ]);
        }

        $permission->delete();

        return $this->respond($request, 'Permiso eliminado correctamente.', 'permissions');
    }

    private function accessData(Request $request): array
    {
        $filters = $request->validate([
            'tab' => ['nullable', 'string', Rule::in(['users', 'roles', 'permissions', 'tenants'])],
        ]);
        $activeTab = (string) ($filters['tab'] ?? 'users');

        $roles = Role::query()->with('permissions')->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('name')->get();

        $users = User::query()
            ->with(['roles:name,id', 'permissions:name,id'])
            ->orderBy('name')
            ->get();
        $tenantUsers = $users
            ->filter(fn(User $user): bool => $this->userHasTenantRole($user))
            ->values();
        $administrativeUsers = $users
            ->reject(fn(User $user): bool => $this->userHasTenantRole($user))
            ->values();

        return [
            'users' => $users,
            'administrativeUsers' => $administrativeUsers,
            'tenantUsers' => $tenantUsers,
            'roles' => $roles,
            'permissions' => $permissions,
            'activeTab' => $activeTab,
            'usersTotal' => User::query()->count(),
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'rolesCount' => $roles->count(),
            'permissionsCount' => $permissions->count(),
        ];
    }

    private function userHasTenantRole(User $user): bool
    {
        return $user->roles->contains(fn(Role $role): bool => in_array($role->name, ['inquilino', 'tenant'], true));
    }

    private function respond(Request $request, string $message, string $tab): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'type' => 'success',
                'tab' => $tab,
            ]);
        }

        return redirect()
            ->route('access.index', ['tab' => $tab])
            ->with('success', $message);
    }

    private function ensureAccess(Request $request): void
    {
        $user = $request->user();
        $isAdminRole = $user?->hasRole('administrador') || $user?->hasRole('admin');
        if (!$isAdminRole && !$user?->can('usuarios.gestionar')) {
            abort(403);
        }
    }
}
