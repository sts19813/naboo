@php
    /** @var \App\Models\Tenant|null $tenant */
    $tenant = $tenant ?? null;
    $isExistingTenant = (bool) $tenant?->exists;
    $isActive = old('is_active', $tenant?->is_active ?? true);
@endphp

@once
    @push('styles')
        <style>
            .tenant-form-layout .tenant-form-section {
                border: 1px solid var(--bs-gray-200);
                border-radius: 1rem;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            }

            .tenant-form-layout .tenant-form-section .card-header {
                min-height: 88px;
            }

            .tenant-form-layout .tenant-form-section .form-label {
                font-weight: 600;
                color: var(--bs-gray-800);
            }

            .tenant-form-layout .tenant-form-section .form-control,
            .tenant-form-layout .tenant-form-section .form-select,
            .tenant-form-layout .tenant-form-section textarea {
                border-color: var(--bs-gray-300);
            }

            .tenant-form-layout .tenant-section-copy {
                color: var(--bs-gray-600);
                font-size: 0.825rem;
            }
        </style>
    @endpush
@endonce

<div class="tenant-form-layout d-flex flex-column gap-6">
    <div class="card tenant-form-section">
        <div class="card-header border-0">
            <div class="card-title">
                <span class="symbol symbol-45px me-4">
                    <span class="symbol-label bg-light-primary">
                        <i class="ki-outline ki-profile-circle fs-2 text-primary"></i>
                    </span>
                </span>
                <div>
                    <h3 class="fw-bold mb-1">Datos personales y acceso</h3>
                    <div class="tenant-section-copy">Informacion principal del inquilino y credenciales del portal.</div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="row g-5">
                <div class="col-lg-6">
                    <label class="form-label required">Nombre completo</label>
                    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                        value="{{ old('full_name', $tenant?->full_name) }}">
                    @error('full_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-3">
                    <label class="form-label required">Telefono principal</label>
                    <input type="text" name="phone_primary" class="form-control @error('phone_primary') is-invalid @enderror"
                        value="{{ old('phone_primary', $tenant?->phone_primary) }}">
                    @error('phone_primary')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-3">
                    <label class="form-label">Telefono secundario</label>
                    <input type="text" name="phone_secondary" class="form-control @error('phone_secondary') is-invalid @enderror"
                        value="{{ old('phone_secondary', $tenant?->phone_secondary) }}">
                    @error('phone_secondary')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $tenant?->email) }}">
                    <div class="form-text">Se usa para acceso y notificaciones del inquilino.</div>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Contrasena de acceso</label>
                    <input type="text" name="access_password" class="form-control @error('access_password') is-invalid @enderror"
                        value="{{ old('access_password') }}">
                    <div class="form-text">
                        {{ $isExistingTenant ? 'Solo captura una nueva si deseas actualizarla.' : 'Si la dejas vacia, el sistema generara una automaticamente.' }}
                    </div>
                    @error('access_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label">CURP</label>
                    <input type="text" name="curp" class="form-control @error('curp') is-invalid @enderror"
                        value="{{ old('curp', $tenant?->curp) }}">
                    @error('curp')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label">RFC</label>
                    <input type="text" name="rfc" class="form-control @error('rfc') is-invalid @enderror"
                        value="{{ old('rfc', $tenant?->rfc) }}">
                    @error('rfc')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <label class="form-label d-block mb-3">Estado de acceso</label>
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" value="1" id="tenant_is_active" name="is_active"
                            {{ (string) $isActive === '1' || $isActive === 1 || $isActive === true ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold text-gray-700 ms-3" for="tenant_is_active">
                            Inquilino activo en el sistema
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card tenant-form-section">
        <div class="card-header border-0">
            <div class="card-title">
                <span class="symbol symbol-45px me-4">
                    <span class="symbol-label bg-light-success">
                        <i class="ki-outline ki-briefcase fs-2 text-success"></i>
                    </span>
                </span>
                <div>
                    <h3 class="fw-bold mb-1">Perfil laboral y expediente</h3>
                    <div class="tenant-section-copy">Contexto economico y estado general de su expediente.</div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="row g-5">
                <div class="col-lg-6">
                    <label class="form-label">Empresa o empleador</label>
                    <input type="text" name="employer" class="form-control @error('employer') is-invalid @enderror"
                        value="{{ old('employer', $tenant?->employer) }}">
                    @error('employer')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Ocupacion</label>
                    <input type="text" name="occupation" class="form-control @error('occupation') is-invalid @enderror"
                        value="{{ old('occupation', $tenant?->occupation) }}">
                    @error('occupation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Ingreso mensual (MXN)</label>
                    <input type="number" min="0" step="0.01" name="monthly_income"
                        class="form-control @error('monthly_income') is-invalid @enderror"
                        value="{{ old('monthly_income', $tenant?->monthly_income) }}">
                    @error('monthly_income')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Anios laborales</label>
                    <input type="number" min="0" max="80" name="employment_years"
                        class="form-control @error('employment_years') is-invalid @enderror"
                        value="{{ old('employment_years', $tenant?->employment_years) }}">
                    @error('employment_years')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Estado del expediente</label>
                    <select name="dossier_status" class="form-select @error('dossier_status') is-invalid @enderror">
                        @foreach ($dossierStatuses as $statusValue => $statusLabel)
                            <option value="{{ $statusValue }}"
                                {{ old('dossier_status', $tenant?->dossier_status ?? \App\Models\Tenant::DOSSIER_INCOMPLETE) === $statusValue ? 'selected' : '' }}>
                                {{ $statusLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('dossier_status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card tenant-form-section">
        <div class="card-header border-0">
            <div class="card-title">
                <span class="symbol symbol-45px me-4">
                    <span class="symbol-label bg-light-info">
                        <i class="ki-outline ki-people fs-2 text-info"></i>
                    </span>
                </span>
                <div>
                    <h3 class="fw-bold mb-1">Referencias y contactos</h3>
                    <div class="tenant-section-copy">Personas clave para validacion, soporte o emergencias.</div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="row g-5">
                <div class="col-lg-6">
                    <label class="form-label">Referencia personal - nombre</label>
                    <input type="text" name="personal_reference_name" class="form-control"
                        value="{{ old('personal_reference_name', $tenant?->personal_reference_name) }}">
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Referencia personal - telefono</label>
                    <input type="text" name="personal_reference_phone" class="form-control"
                        value="{{ old('personal_reference_phone', $tenant?->personal_reference_phone) }}">
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Referencia laboral - nombre</label>
                    <input type="text" name="work_reference_name" class="form-control"
                        value="{{ old('work_reference_name', $tenant?->work_reference_name) }}">
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Referencia laboral - telefono</label>
                    <input type="text" name="work_reference_phone" class="form-control"
                        value="{{ old('work_reference_phone', $tenant?->work_reference_phone) }}">
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Contacto de emergencia - nombre</label>
                    <input type="text" name="emergency_contact_name" class="form-control"
                        value="{{ old('emergency_contact_name', $tenant?->emergency_contact_name) }}">
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Contacto de emergencia - telefono</label>
                    <input type="text" name="emergency_contact_phone" class="form-control"
                        value="{{ old('emergency_contact_phone', $tenant?->emergency_contact_phone) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card tenant-form-section">
        <div class="card-header border-0">
            <div class="card-title">
                <span class="symbol symbol-45px me-4">
                    <span class="symbol-label bg-light-warning">
                        <i class="ki-outline ki-geolocation-home fs-2 text-warning"></i>
                    </span>
                </span>
                <div>
                    <h3 class="fw-bold mb-1">Domicilios y notas internas</h3>
                    <div class="tenant-section-copy">Detalles operativos para seguimiento comercial y administracion.</div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="row g-5">
                <div class="col-lg-6">
                    <label class="form-label">Domicilio anterior</label>
                    <textarea name="previous_address" rows="4" class="form-control">{{ old('previous_address', $tenant?->previous_address) }}</textarea>
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Domicilio actual</label>
                    <textarea name="current_address" rows="4" class="form-control">{{ old('current_address', $tenant?->current_address) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Notas internas</label>
                    <textarea name="notes" rows="4" class="form-control">{{ old('notes', $tenant?->notes) }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>
