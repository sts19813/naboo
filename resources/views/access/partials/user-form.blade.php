@php
    $selectedRoles = $userItem?->roles?->pluck('name')->all() ?? [];
    $selectedPermissions = $userItem?->permissions?->pluck('name')->all() ?? [];
@endphp

<div class="row g-5">
    @php $reviewRange = $userItem?->maintenanceReviewerAccess; @endphp
    <div class="col-12"><div class="border rounded p-3"><strong>Rango para revisor de tickets</strong><p class="text-muted small mb-2">Se aplica únicamente al rol Revisor de tickets.</p><div class="row g-2">
        <div class="col-md-4"><label class="form-label">Tipo de rango</label><select class="form-select" name="reviewer_range_type"><option value="relative" @selected(($reviewRange?->range_type ?? 'relative') === 'relative')>Relativo</option><option value="custom" @selected($reviewRange?->range_type === 'custom')>Fechas específicas</option></select></div>
        <div class="col-md-4"><label class="form-label">Días anteriores</label><input class="form-control" type="number" min="0" max="36500" name="reviewer_days_before" value="{{ $reviewRange?->days_before ?? 30 }}"></div>
        <div class="col-md-4"><label class="form-label">Días posteriores</label><input class="form-control" type="number" min="0" max="36500" name="reviewer_days_after" value="{{ $reviewRange?->days_after ?? 0 }}"></div>
        <div class="col-md-6"><label class="form-label">Desde</label><input class="form-control" type="date" name="reviewer_starts_on" value="{{ $reviewRange?->starts_on?->format('Y-m-d') }}"></div>
        <div class="col-md-6"><label class="form-label">Hasta</label><input class="form-control" type="date" name="reviewer_ends_on" value="{{ $reviewRange?->ends_on?->format('Y-m-d') }}"></div>
    </div></div></div>
    <div class="col-md-4">
        <label class="form-label required">Nombre</label>
        <input type="text" class="form-control form-control-solid" name="name" value="{{ $userItem?->name }}" required>
        <div class="invalid-feedback d-block" data-error-for="name"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label required">Correo</label>
        <input type="email" class="form-control form-control-solid" name="email" value="{{ $userItem?->email }}" required>
        <div class="invalid-feedback d-block" data-error-for="email"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label {{ $userItem ? '' : 'required' }}">{{ $userItem ? 'Nueva contraseña' : 'Contraseña' }}</label>
        <input type="password" class="form-control form-control-solid" name="password" minlength="8" {{ $userItem ? '' : 'required' }} placeholder="{{ $userItem ? 'Opcional' : '' }}">
        <div class="invalid-feedback d-block" data-error-for="password"></div>
    </div>
    <div class="col-12">
        <label class="form-label">Roles</label>
        <div class="permission-grid">
            @forelse ($roleOptions as $roleName)
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="role_names[]" value="{{ $roleName }}" {{ in_array($roleName, $selectedRoles, true) ? 'checked' : '' }}>
                    <span class="form-check-label">{{ $roleName }}</span>
                </label>
            @empty
                <div class="text-muted">Aún no hay roles creados.</div>
            @endforelse
        </div>
        <div class="invalid-feedback d-block" data-error-for="role_names"></div>
    </div>
    <div class="col-12">
        <label class="form-label">Permisos directos</label>
        <div class="permission-grid">
            @forelse ($permissionOptions as $permissionName)
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="permission_names[]" value="{{ $permissionName }}" {{ in_array($permissionName, $selectedPermissions, true) ? 'checked' : '' }}>
                    <span class="form-check-label">{{ $permissionName }}</span>
                </label>
            @empty
                <div class="text-muted">Aún no hay permisos creados.</div>
            @endforelse
        </div>
        <div class="invalid-feedback d-block" data-error-for="permission_names"></div>
    </div>
    <div class="col-12">
        <label class="form-check form-switch form-check-custom form-check-solid">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $userItem === null || $userItem->is_active ? 'checked' : '' }}>
            <span class="form-check-label">Usuario activo y con acceso al sistema</span>
        </label>
    </div>
</div>
