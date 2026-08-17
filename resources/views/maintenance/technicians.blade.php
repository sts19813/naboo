@extends('layouts.app')

@section('title', (($directoryType ?? 'technicians') === 'technicians' ? 'Técnicos' : 'Proveedores').' | '.config('app.name'))

@section('content')
    @php
        $isTechnicianDirectory = ($directoryType ?? 'technicians') === 'technicians';
        $directoryTitle = $isTechnicianDirectory ? 'Técnicos' : 'Proveedores';
        $directoryDescription = $isTechnicianDirectory
            ? 'Administra el equipo técnico interno y sus cuentas de acceso al sistema.'
            : 'Administra los contactos externos disponibles para atender tickets de mantenimiento.';
    @endphp

    <div class="maintenance-directory py-8">
        @if (session('success'))
            <div class="alert alert-success mb-5">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mb-5">{{ session('error') }}</div>
        @endif

        <div class="directory-heading">
            <div>
                <div class="directory-eyebrow">Mantenimiento</div>
                <h1>{{ $directoryTitle }}</h1>
                <p>{{ $directoryDescription }}</p>
            </div>
            <div class="directory-actions">
                <a class="btn btn-light" href="{{ route('maintenance.index') }}"><i class="bi bi-arrow-left"></i> Regresar</a>
                @if ($isTechnicianDirectory)
                    <a class="btn btn-light-primary" href="{{ route('maintenance.providers.index') }}"><i class="bi bi-building"></i> Ver proveedores</a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTechnicianModal"><i class="bi bi-person-plus"></i> Nuevo técnico</button>
                @else
                    <a class="btn btn-light-primary" href="{{ route('maintenance.technicians.index') }}"><i class="bi bi-person-gear"></i> Ver técnicos</a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSupplierModal"><i class="bi bi-building-add"></i> Nuevo proveedor</button>
                @endif
            </div>
        </div>

        <div class="directory-card">
            @if ($isTechnicianDirectory)
            <div>
                <div class="directory-card-heading"><h2>Técnicos</h2><p>Los técnicos tienen una cuenta para entrar al sistema y gestionar los tickets que les corresponden.</p></div>
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Nombre</th><th>Contacto</th><th>Especialidad</th><th>Cuenta del sistema</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($providers as $technician)
                            <tr>
                                <td class="fw-bold text-dark">{{ $technician->name }}</td>
                                <td><div>{{ $technician->email ?: 'Sin correo' }}</div><small>{{ $technician->phone ?: 'Sin teléfono' }}</small></td>
                                <td>{{ $technician->specialty ?: 'Sin especialidad' }}</td>
                                <td>@if ($technician->user)<div class="fw-semibold">{{ $technician->user->name }}</div><small>{{ $technician->user->email }}</small>@else<span class="badge badge-light-warning">Sin cuenta vinculada</span>@endif</td>
                                <td><span class="directory-status {{ $technician->is_active ? 'is-active' : '' }}">{{ $technician->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="text-end"><button class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#editProviderModal-{{ $technician->id }}">Editar</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="directory-empty"><i class="bi bi-person-gear"></i><strong>Aún no hay técnicos</strong><span>Agrega el primer técnico y vincula su acceso.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>

            @else
            <div>
                <div class="directory-card-heading"><h2>Proveedores</h2><p>Son contactos externos sin acceso al sistema. El asesor responsable administra sus tickets.</p></div>
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Nombre</th><th>Contacto</th><th>Categoría</th><th>Disponibilidad</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($providers as $supplier)
                            <tr>
                                <td class="fw-bold text-dark">{{ $supplier->name }}</td>
                                <td><div>{{ $supplier->email ?: 'Sin correo' }}</div><small>{{ $supplier->phone ?: 'Sin teléfono' }}</small></td>
                                <td>{{ $supplier->category ?: 'Sin categoría' }}</td>
                                <td>{{ $supplier->availability ?: 'Sin especificar' }}</td>
                                <td><span class="directory-status {{ $supplier->is_active ? 'is-active' : '' }}">{{ $supplier->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="text-end"><button class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#editProviderModal-{{ $supplier->id }}">Editar</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="directory-empty"><i class="bi bi-building"></i><strong>Aún no hay proveedores</strong><span>Agrega contactos externos para asignarlos a tickets.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
            @endif
        </div>

        @if ($isTechnicianDirectory)
        <div class="modal fade" id="createTechnicianModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <form method="POST" action="{{ route('maintenance.providers.store') }}">@csrf
                    <input type="hidden" name="type" value="tecnico_interno">
                    <div class="modal-header"><div><h3 class="modal-title">Nuevo técnico</h3><p class="text-muted mb-0">Registra sus datos y configura su acceso al sistema.</p></div><button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button></div>
                    <div class="modal-body"><div class="row g-4">
                        <div class="col-md-6"><label class="form-label required">Nombre</label><input class="form-control" name="name" maxlength="190" required></div>
                        <div class="col-md-6"><label class="form-label">Especialidad</label><input class="form-control" name="specialty" maxlength="190"></div>
                        <div class="col-md-6"><label class="form-label">Correo</label><input class="form-control" type="email" name="email" maxlength="190"></div>
                        <div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control" name="phone" maxlength="40"></div>
                        <div class="col-12"><div class="directory-account-title"><i class="bi bi-shield-lock"></i><div><strong>Acceso al sistema</strong><span>Vincula un usuario existente o crea uno nuevo.</span></div></div></div>
                        <div class="col-md-6"><label class="form-label">Vincular usuario existente</label><select class="form-select" name="user_id"><option value="">Seleccionar usuario</option>@foreach ($users as $userRow)<option value="{{ $userRow->id }}">{{ $userRow->name }} · {{ $userRow->email }}</option>@endforeach</select></div>
                        <div class="col-md-6 d-flex align-items-end"><div class="form-check form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" value="1" id="create_user_account_new" name="create_user_account"><label class="form-check-label" for="create_user_account_new">Crear usuario nuevo</label></div></div>
                        <div class="col-md-6"><label class="form-label">Nombre del usuario</label><input class="form-control" name="account_name" maxlength="255"></div>
                        <div class="col-md-6"><label class="form-label">Correo de acceso</label><input class="form-control" type="email" name="account_email" maxlength="190"></div>
                        <div class="col-md-6"><label class="form-label">Contraseña</label><input class="form-control" type="text" name="account_password" maxlength="120"></div>
                        <div class="col-md-6 d-flex align-items-end"><div class="form-check form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" value="1" id="send_credentials_email_new" name="send_credentials_email"><label class="form-check-label" for="send_credentials_email_new">Enviar acceso por correo</label></div></div>
                        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" value="1" name="is_active" id="technician_active_new" checked><label class="form-check-label" for="technician_active_new">Técnico activo</label></div></div>
                    </div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar técnico</button></div>
                </form>
            </div></div>
        </div>

        @else
        <div class="modal fade" id="createSupplierModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <form method="POST" action="{{ route('maintenance.providers.store') }}">@csrf
                    <input type="hidden" name="type" value="proveedor">
                    <div class="modal-header"><div><h3 class="modal-title">Nuevo proveedor</h3><p class="text-muted mb-0">El proveedor será un contacto externo sin cuenta de acceso.</p></div><button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button></div>
                    <div class="modal-body"><div class="row g-4">
                        <div class="col-md-6"><label class="form-label required">Nombre</label><input class="form-control" name="name" maxlength="190" required></div>
                        <div class="col-md-6"><label class="form-label">Categoría</label><input class="form-control" name="category" maxlength="190" placeholder="Ej. Plomería, electricidad"></div>
                        <div class="col-md-6"><label class="form-label">Correo</label><input class="form-control" type="email" name="email" maxlength="190"></div>
                        <div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control" name="phone" maxlength="40"></div>
                        <div class="col-12"><label class="form-label">Disponibilidad</label><input class="form-control" name="availability" maxlength="255" placeholder="Ej. Lunes a viernes de 9:00 a 18:00"></div>
                        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" value="1" name="is_active" id="supplier_active_new" checked><label class="form-check-label" for="supplier_active_new">Proveedor activo</label></div></div>
                    </div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar proveedor</button></div>
                </form>
            </div></div>
        </div>
        @endif

        @foreach ($providers as $provider)
            <div class="modal fade" id="editProviderModal-{{ $provider->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                    <form method="POST" action="{{ route('maintenance.providers.update', $provider) }}">@csrf @method('PUT')
                        <input type="hidden" name="type" value="{{ $provider->type }}">
                        <div class="modal-header"><div><h3 class="modal-title">Editar {{ $provider->isTechnician() ? 'técnico' : 'proveedor' }}</h3><p class="text-muted mb-0">{{ $provider->name }}</p></div><button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button></div>
                        <div class="modal-body"><div class="row g-4">
                            <div class="col-md-6"><label class="form-label required">Nombre</label><input class="form-control" name="name" maxlength="190" value="{{ $provider->name }}" required></div>
                            <div class="col-md-6"><label class="form-label">{{ $provider->isTechnician() ? 'Especialidad' : 'Categoría' }}</label><input class="form-control" name="{{ $provider->isTechnician() ? 'specialty' : 'category' }}" maxlength="190" value="{{ $provider->isTechnician() ? $provider->specialty : $provider->category }}"></div>
                            <div class="col-md-6"><label class="form-label">Correo</label><input class="form-control" type="email" name="email" maxlength="190" value="{{ $provider->email }}"></div>
                            <div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control" name="phone" maxlength="40" value="{{ $provider->phone }}"></div>
                            @if ($provider->isSupplier())
                                <div class="col-12"><label class="form-label">Disponibilidad</label><input class="form-control" name="availability" maxlength="255" value="{{ $provider->availability }}"></div>
                            @else
                                <div class="col-12"><div class="directory-account-title"><i class="bi bi-shield-lock"></i><div><strong>Acceso al sistema</strong><span>Conserva la cuenta vinculada o crea una nueva.</span></div></div></div>
                                <div class="col-md-6"><label class="form-label">Vincular usuario existente</label><select class="form-select" name="user_id"><option value="">Conservar cuenta actual</option>@foreach ($users as $userRow)<option value="{{ $userRow->id }}" {{ (int) $provider->user_id === (int) $userRow->id ? 'selected' : '' }}>{{ $userRow->name }} · {{ $userRow->email }}</option>@endforeach</select></div>
                                <div class="col-md-6 d-flex align-items-end"><div class="form-check form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" value="1" id="create_user_account_{{ $provider->id }}" name="create_user_account"><label class="form-check-label" for="create_user_account_{{ $provider->id }}">Crear usuario nuevo</label></div></div>
                                <div class="col-md-6"><label class="form-label">Nombre del usuario</label><input class="form-control" name="account_name" maxlength="255" value="{{ $provider->name }}"></div>
                                <div class="col-md-6"><label class="form-label">Correo de acceso</label><input class="form-control" type="email" name="account_email" maxlength="190"></div>
                                <div class="col-md-6"><label class="form-label">Contraseña</label><input class="form-control" type="text" name="account_password" maxlength="120"></div>
                                <div class="col-md-6 d-flex align-items-end"><div class="form-check form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" value="1" id="send_credentials_email_{{ $provider->id }}" name="send_credentials_email"><label class="form-check-label" for="send_credentials_email_{{ $provider->id }}">Enviar acceso por correo</label></div></div>
                            @endif
                            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" value="1" name="is_active" id="provider_active_{{ $provider->id }}" {{ $provider->is_active ? 'checked' : '' }}><label class="form-check-label" for="provider_active_{{ $provider->id }}">Registro activo</label></div></div>
                        </div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar cambios</button></div>
                    </form>
                </div></div>
            </div>
        @endforeach
    </div>
@endsection

@push('styles')
<style>
    .maintenance-directory{display:grid;gap:1.25rem}.directory-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:1.5rem}.directory-eyebrow{text-transform:uppercase;letter-spacing:.12em;color:#ff3366;font-size:.76rem;font-weight:800}.directory-heading h1{margin:.2rem 0;font-size:clamp(1.8rem,3vw,2.4rem);font-weight:800;color:#111d3c}.directory-heading p,.directory-card-heading p{margin:0;color:#7e89a2}.directory-actions{display:flex;flex-wrap:wrap;gap:.65rem}.directory-tabs{display:flex;gap:.45rem;padding:.35rem;width:max-content;max-width:100%;background:#eef1f6;border-radius:15px}.directory-tabs .nav-link{display:flex;align-items:center;gap:.5rem;border:0;border-radius:11px;color:#6f7991;font-weight:700;padding:.72rem 1rem}.directory-tabs .nav-link.active{background:#fff;color:#ef285c;box-shadow:0 4px 14px rgba(25,40,75,.09)}.directory-tabs .nav-link span{display:grid;place-items:center;min-width:23px;height:23px;padding:0 .35rem;border-radius:999px;background:#dfe4ed;font-size:.72rem}.directory-tabs .nav-link.active span{background:#fff0f4}.directory-card{background:#fff;border:1px solid #e6eaf1;border-radius:20px;overflow:hidden;box-shadow:0 10px 30px rgba(20,36,72,.06)}.directory-card-heading{padding:1.25rem 1.4rem;border-bottom:1px solid #edf0f5}.directory-card-heading h2{margin:0 0 .2rem;color:#15213f;font-size:1.15rem;font-weight:800}.directory-card th{padding:.9rem 1rem;color:#818ca5;text-transform:uppercase;font-size:.74rem;letter-spacing:.04em;background:#f8f9fc}.directory-card td{padding:1rem;border-bottom-color:#eef1f5;color:#34405d}.directory-card th:first-child,.directory-card td:first-child{padding-left:1.5rem!important}.directory-card th:last-child,.directory-card td:last-child{padding-right:1.5rem!important}.directory-card td small{display:block;color:#8792aa}.directory-status{display:inline-flex;padding:.38rem .65rem;border-radius:999px;background:#f0f2f6;color:#778198;font-size:.75rem;font-weight:700}.directory-status.is-active{background:#e8faef;color:#0a9d59}.directory-empty{display:flex;min-height:210px;align-items:center;justify-content:center;flex-direction:column;gap:.35rem;color:#8792aa}.directory-empty i{display:grid;place-items:center;width:52px;height:52px;border-radius:16px;background:#fff0f4;color:#ef285c;font-size:1.35rem}.directory-empty strong{color:#1a2746;font-size:1rem}.directory-account-title{display:flex;align-items:center;gap:.75rem;padding:1rem;border-radius:14px;background:#f6f8fc;border:1px solid #e8ecf3}.directory-account-title i{display:grid;place-items:center;width:38px;height:38px;border-radius:11px;background:#eaf3ff;color:#2878e5}.directory-account-title span{display:block;color:#7e89a2;font-size:.78rem}.modal-header p{font-size:.82rem;margin-top:.15rem}@media(max-width:767px){.maintenance-directory{padding-top:1rem!important;padding-bottom:6rem!important}.directory-heading{align-items:flex-start;flex-direction:column}.directory-actions{display:grid;grid-template-columns:1fr 1fr;width:100%}.directory-actions .btn:first-child{grid-column:1/-1}.directory-tabs{width:100%;display:grid;grid-template-columns:1fr 1fr}.directory-tabs .nav-link{justify-content:center;padding:.7rem .45rem;font-size:.82rem}.directory-card{border-radius:16px}.directory-card-heading{padding:1rem}.directory-card table{min-width:820px}.modal-footer{position:sticky;bottom:0;background:#fff;z-index:2}}
</style>
@endpush
