@php
    $selectedPermissions = $roleItem?->permissions?->pluck('name')->all() ?? [];
@endphp

<div class="mb-6">
    <label class="form-label required">Nombre del rol</label>
    <input type="text" class="form-control form-control-solid" name="name" value="{{ $roleItem?->name }}" required>
    <div class="invalid-feedback d-block" data-error-for="name"></div>
</div>

<div>
    <label class="form-label">Permisos asignados</label>
    <div class="permission-grid">
        @forelse ($permissionOptions as $permissionName)
            <label class="form-check form-switch form-check-custom form-check-solid">
                <input class="form-check-input" type="checkbox" name="permission_names[]" value="{{ $permissionName }}" {{ in_array($permissionName, $selectedPermissions, true) ? 'checked' : '' }}>
                <span class="form-check-label">{{ $permissionName }}</span>
            </label>
        @empty
            <div class="text-muted">Crea permisos para poder asignarlos a este rol.</div>
        @endforelse
    </div>
    <div class="invalid-feedback d-block" data-error-for="permission_names"></div>
</div>
