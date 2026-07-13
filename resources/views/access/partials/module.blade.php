@php
    $activeTab = in_array($activeTab ?? 'users', ['users', 'roles', 'permissions', 'tenants'], true) ? $activeTab : 'users';
    $roleOptions = $roles->pluck('name')->values();
    $permissionOptions = $permissions->pluck('name')->values();
    $administrativeUsers = $administrativeUsers ?? $users->reject(fn($userItem) => $userItem->roles->contains(fn($role) => in_array($role->name, ['inquilino', 'tenant'], true)))->values();
    $tenantUsers = $tenantUsers ?? $users->filter(fn($userItem) => $userItem->roles->contains(fn($role) => in_array($role->name, ['inquilino', 'tenant'], true)))->values();
@endphp

<div id="access-module" class="py-10 access-module">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="mb-1 fw-bold">Usuarios, roles y permisos</h1>
            <div class="text-muted">Administra accesos del sistema.</div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-light-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                <i class="bi bi-person-badge me-1"></i> Rol
            </button>
            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-person-plus me-1"></i> Usuario
            </button>
        </div>
    </div>

    <div class="row g-4 mb-6">
        <div class="col-sm-6 col-xl-3">
            <div class="access-stat p-5">
                <div class="text-muted fs-7 text-uppercase">Usuarios</div>
                <div class="fs-2 fw-bold">{{ $usersTotal }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="access-stat p-5">
                <div class="text-muted fs-7 text-uppercase">Activos</div>
                <div class="fs-2 fw-bold text-success">{{ $activeUsers }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="access-stat p-5">
                <div class="text-muted fs-7 text-uppercase">Roles</div>
                <div class="fs-2 fw-bold">{{ $rolesCount }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="access-stat p-5">
                <div class="text-muted fs-7 text-uppercase">Permisos</div>
                <div class="fs-2 fw-bold">{{ $permissionsCount }}</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <ul class="nav nav-line-tabs mb-8 fs-6" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'users' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#access-users-tab" type="button" role="tab" data-tab-key="users">
                        <i class="bi bi-people me-1"></i> Usuarios
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'roles' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#access-roles-tab" type="button" role="tab" data-tab-key="roles">
                        <i class="bi bi-person-badge me-1"></i> Roles
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'permissions' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#access-permissions-tab" type="button" role="tab" data-tab-key="permissions">
                        <i class="bi bi-key me-1"></i> Permisos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'tenants' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#access-tenants-tab" type="button" role="tab" data-tab-key="tenants">
                        <i class="bi bi-house-door me-1"></i> Inquilinos
                        <span class="badge badge-light-secondary text-secondary ms-1">{{ $tenantUsers->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'users' ? 'show active' : '' }}" id="access-users-tab" role="tabpanel">
                    <div class="row g-3 align-items-end mb-6">
                        <div class="col-lg-5">
                            <label class="form-label">Buscar usuario</label>
                            <input type="text" class="form-control form-control-solid" id="accessUsersSearch" placeholder="Nombre, correo, rol o permiso">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle mb-0" data-access-datatable data-access-search-input="#accessUsersSearch">
                            <thead>
                                <tr class="text-muted text-uppercase fs-8">
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Roles</th>
                                    <th>Permisos directos</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($administrativeUsers as $userItem)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $userItem->name }}</div>
                                            <div class="text-muted fs-8">{{ $userItem->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $userItem->is_active ? 'badge-light-success text-success' : 'badge-light-danger text-danger' }}">
                                                {{ $userItem->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="access-chip-list">
                                                @forelse ($userItem->roles as $role)
                                                    <span class="badge badge-light-primary">{{ $role->name }}</span>
                                                @empty
                                                    <span class="text-muted">Sin rol</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            <div class="access-chip-list">
                                                @forelse ($userItem->permissions as $permission)
                                                    <span class="badge badge-light-secondary text-secondary">{{ $permission->name }}</span>
                                                @empty
                                                    <span class="text-muted">Sin permisos directos</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#editUserModal-{{ $userItem->id }}">
                                                <i class="bi bi-pencil-square me-1"></i> Editar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="access-empty text-center text-muted py-10">No hay usuarios administrativos registrados.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="tab-pane fade {{ $activeTab === 'roles' ? 'show active' : '' }}" id="access-roles-tab" role="tabpanel">
                    <div class="d-flex justify-content-end mb-6">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                            <i class="bi bi-plus-lg me-1"></i> Crear rol
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle" data-access-datatable>
                            <thead>
                                <tr class="text-muted text-uppercase fs-8">
                                    <th>Rol</th>
                                    <th>Permisos asignados</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    <tr>
                                        <td class="fw-semibold">{{ $role->name }}</td>
                                        <td>
                                            <div class="access-chip-list">
                                                @forelse ($role->permissions as $permission)
                                                    <span class="badge badge-light-primary">{{ $permission->name }}</span>
                                                @empty
                                                    <span class="text-muted">Sin permisos</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-light-primary me-2" data-bs-toggle="modal" data-bs-target="#editRoleModal-{{ $role->id }}">
                                                <i class="bi bi-pencil-square me-1"></i> Editar
                                            </button>
                                            <form method="POST" action="{{ route('access.roles.destroy', $role) }}" class="d-inline" data-access-form>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger" data-access-delete data-confirm-message="Solo se eliminará si ningún usuario lo tiene asignado.">
                                                    <i class="bi bi-trash me-1"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'permissions' ? 'show active' : '' }}" id="access-permissions-tab" role="tabpanel">
                    <div class="d-flex justify-content-end mb-6">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                            <i class="bi bi-plus-lg me-1"></i> Crear permiso
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle" data-access-datatable>
                            <thead>
                                <tr class="text-muted text-uppercase fs-8">
                                    <th>Permiso</th>
                                    <th>Guard</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permissions as $permission)
                                    <tr>
                                        <td class="fw-semibold">{{ $permission->name }}</td>
                                        <td><span class="badge badge-light-secondary text-secondary">{{ $permission->guard_name }}</span></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-light-primary me-2" data-bs-toggle="modal" data-bs-target="#editPermissionModal-{{ $permission->id }}">
                                                <i class="bi bi-pencil-square me-1"></i> Editar
                                            </button>
                                            <form method="POST" action="{{ route('access.permissions.destroy', $permission) }}" class="d-inline" data-access-form>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger" data-access-delete data-confirm-message="Solo se eliminará si no está asignado a roles ni usuarios.">
                                                    <i class="bi bi-trash me-1"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'tenants' ? 'show active' : '' }}" id="access-tenants-tab" role="tabpanel">
                    <div class="row g-3 align-items-end mb-6">
                        <div class="col-lg-5">
                            <label class="form-label">Buscar inquilino</label>
                            <input type="text" class="form-control form-control-solid" id="accessTenantsSearch" placeholder="Nombre, correo, rol o permiso">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle mb-0" data-access-datatable data-access-search-input="#accessTenantsSearch">
                            <thead>
                                <tr class="text-muted text-uppercase fs-8">
                                    <th>Inquilino</th>
                                    <th>Estado</th>
                                    <th>Roles</th>
                                    <th>Permisos directos</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tenantUsers as $userItem)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $userItem->name }}</div>
                                            <div class="text-muted fs-8">{{ $userItem->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $userItem->is_active ? 'badge-light-success text-success' : 'badge-light-danger text-danger' }}">
                                                {{ $userItem->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="access-chip-list">
                                                @forelse ($userItem->roles as $role)
                                                    <span class="badge badge-light-primary">{{ $role->name }}</span>
                                                @empty
                                                    <span class="text-muted">Sin rol</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            <div class="access-chip-list">
                                                @forelse ($userItem->permissions as $permission)
                                                    <span class="badge badge-light-secondary text-secondary">{{ $permission->name }}</span>
                                                @empty
                                                    <span class="text-muted">Sin permisos directos</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#editUserModal-{{ $userItem->id }}">
                                                <i class="bi bi-pencil-square me-1"></i> Editar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="access-empty text-center text-muted py-10">No hay usuarios con rol inquilino.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="{{ route('access.users.store') }}" data-access-form>
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger d-none" data-form-error></div>
                        @include('access.partials.user-form', ['userItem' => null, 'roleOptions' => $roleOptions, 'permissionOptions' => $permissionOptions])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('access.roles.store') }}" data-access-form>
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Crear rol</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger d-none" data-form-error></div>
                        @include('access.partials.role-form', ['roleItem' => null, 'permissionOptions' => $permissionOptions])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear rol</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createPermissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('access.permissions.store') }}" data-access-form>
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Crear permiso</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger d-none" data-form-error></div>
                        @include('access.partials.permission-form', ['permissionItem' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear permiso</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($users as $userItem)
        <div class="modal fade" id="editUserModal-{{ $userItem->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <form method="POST" action="{{ route('access.users.update', $userItem) }}" data-access-form>
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Editar usuario</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger d-none" data-form-error></div>
                            @include('access.partials.user-form', ['userItem' => $userItem, 'roleOptions' => $roleOptions, 'permissionOptions' => $permissionOptions])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($roles as $roleItem)
        <div class="modal fade" id="editRoleModal-{{ $roleItem->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('access.roles.update', $roleItem) }}" data-access-form>
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Editar rol</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger d-none" data-form-error></div>
                            @include('access.partials.role-form', ['roleItem' => $roleItem, 'permissionOptions' => $permissionOptions])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar rol</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($permissions as $permissionItem)
        <div class="modal fade" id="editPermissionModal-{{ $permissionItem->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('access.permissions.update', $permissionItem) }}" data-access-form>
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Editar permiso</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger d-none" data-form-error></div>
                            @include('access.partials.permission-form', ['permissionItem' => $permissionItem])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar permiso</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
