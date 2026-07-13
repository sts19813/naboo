@extends('layouts.app')

@section('title', 'Administración de técnicos | SuWork')

@section('content')
    <div class="py-10">
        @if (session('success'))
            <div class="alert alert-success mb-6">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mb-6">{{ session('error') }}</div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
            <div>
                <h1 class="mb-1 fw-bold">Administración de técnicos</h1>
                <div class="text-muted">Alta, acceso al sistema y edición de técnicos/proveedores de mantenimiento</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-light" href="{{ route('maintenance.index') }}">Regresar</a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProviderModal">+ Nuevo técnico</button>
            </div>
        </div>

        <div class="card mb-6">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted">
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Contacto</th>
                                <th>Cuenta sistema</th>
                                <th>Especialidad</th>
                                <th>Costo promedio</th>
                                <th>Calificación</th>
                                <th>Disponibilidad</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($providers as $provider)
                                <tr>
                                    <td>{{ $provider->name }}</td>
                                    <td>{{ \App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type }}</td>
                                    <td>
                                        <div>{{ $provider->email ?: '-' }}</div>
                                        <div class="text-muted fs-8">{{ $provider->phone ?: '-' }}</div>
                                    </td>
                                    <td>
                                        @if ($provider->user)
                                            <div class="fw-semibold">{{ $provider->user->name }}</div>
                                            <div class="text-muted fs-8">{{ $provider->user->email }}</div>
                                        @else
                                            <span class="text-muted">Sin cuenta</span>
                                        @endif
                                    </td>
                                    <td>{{ $provider->specialty ?: '-' }}</td>
                                    <td>{{ $provider->average_cost !== null ? '$'.number_format((float) $provider->average_cost, 2) : '-' }}</td>
                                    <td>{{ $provider->rating !== null ? number_format((float) $provider->rating, 2) : '-' }}</td>
                                    <td>{{ $provider->availability ?: '-' }}</td>
                                    <td>{{ $provider->is_active ? 'Activo' : 'Inactivo' }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#editProviderModal-{{ $provider->id }}">
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-10">No hay técnicos/proveedores.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="createProviderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('maintenance.providers.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h3 class="modal-title">Nuevo técnico/proveedor</h3>
                            <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label required">Tipo</label>
                                    <select class="form-select" name="type" required>
                                        @foreach (\App\Models\MaintenanceProvider::TYPE_LABELS as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label required">Nombre</label>
                                    <input class="form-control" type="text" name="name" maxlength="190" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Correo</label>
                                    <input class="form-control" type="email" name="email" maxlength="190">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input class="form-control" type="text" name="phone" maxlength="40">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Especialidad</label>
                                    <input class="form-control" type="text" name="specialty" maxlength="190">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Costo promedio</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="average_cost">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Calificación</label>
                                    <input class="form-control" type="number" step="0.01" min="0" max="5" name="rating">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Disponibilidad</label>
                                    <input class="form-control" type="text" name="availability" maxlength="255">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" name="is_active" checked>
                                        <label class="form-check-label">Activo</label>
                                    </div>
                                </div>
                                <div class="col-12"><hr class="my-1"></div>
                                <div class="col-md-6">
                                    <label class="form-label">Vincular usuario existente</label>
                                    <select class="form-select" name="user_id">
                                        <option value="">Sin vincular</option>
                                        @foreach ($users as $userRow)
                                            <option value="{{ $userRow->id }}">{{ $userRow->name }} · {{ $userRow->email }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" id="create_user_account_new" name="create_user_account">
                                        <label class="form-check-label" for="create_user_account_new">Crear cuenta nueva</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre cuenta</label>
                                    <input class="form-control" type="text" name="account_name" maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Correo cuenta</label>
                                    <input class="form-control" type="email" name="account_email" maxlength="190">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contraseña cuenta</label>
                                    <input class="form-control" type="text" name="account_password" maxlength="120">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" id="send_credentials_email_new" name="send_credentials_email">
                                        <label class="form-check-label" for="send_credentials_email_new">Enviar acceso por correo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach ($providers as $provider)
            <div class="modal fade" id="editProviderModal-{{ $provider->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('maintenance.providers.update', $provider) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h3 class="modal-title">Editar técnico/proveedor</h3>
                                <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label required">Tipo</label>
                                        <select class="form-select" name="type" required>
                                            @foreach (\App\Models\MaintenanceProvider::TYPE_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ $provider->type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label required">Nombre</label>
                                        <input class="form-control" type="text" name="name" maxlength="190" value="{{ $provider->name }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Correo</label>
                                        <input class="form-control" type="email" name="email" maxlength="190" value="{{ $provider->email }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Teléfono</label>
                                        <input class="form-control" type="text" name="phone" maxlength="40" value="{{ $provider->phone }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Especialidad</label>
                                        <input class="form-control" type="text" name="specialty" maxlength="190" value="{{ $provider->specialty }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Costo promedio</label>
                                        <input class="form-control" type="number" step="0.01" min="0" name="average_cost" value="{{ $provider->average_cost }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Calificación</label>
                                        <input class="form-control" type="number" step="0.01" min="0" max="5" name="rating" value="{{ $provider->rating }}">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Disponibilidad</label>
                                        <input class="form-control" type="text" name="availability" maxlength="255" value="{{ $provider->availability }}">
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" name="is_active" id="provider_active_{{ $provider->id }}" {{ $provider->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label" for="provider_active_{{ $provider->id }}">Activo</label>
                                        </div>
                                    </div>
                                    <div class="col-12"><hr class="my-1"></div>
                                    <div class="col-md-6">
                                        <label class="form-label">Vincular usuario existente</label>
                                        <select class="form-select" name="user_id">
                                            <option value="">Sin vincular</option>
                                            @foreach ($users as $userRow)
                                                <option value="{{ $userRow->id }}" {{ (int) $provider->user_id === (int) $userRow->id ? 'selected' : '' }}>
                                                    {{ $userRow->name }} · {{ $userRow->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" id="create_user_account_{{ $provider->id }}" name="create_user_account">
                                            <label class="form-check-label" for="create_user_account_{{ $provider->id }}">Crear cuenta nueva</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre cuenta</label>
                                        <input class="form-control" type="text" name="account_name" maxlength="255" value="{{ $provider->name }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Correo cuenta</label>
                                        <input class="form-control" type="email" name="account_email" maxlength="190">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contraseña cuenta</label>
                                        <input class="form-control" type="text" name="account_password" maxlength="120">
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" id="send_credentials_email_{{ $provider->id }}" name="send_credentials_email">
                                            <label class="form-check-label" for="send_credentials_email_{{ $provider->id }}">Enviar acceso por correo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
