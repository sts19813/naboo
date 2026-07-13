@extends('layouts.app')

@section('title', $property->internal_name . ' | SuWork')

@section('content')

    <link rel="stylesheet" href="/assets/css/propiedades.css">

    @php
        $photoUrl = $property->facade_photo_path
            ? \Illuminate\Support\Facades\Storage::url($property->facade_photo_path)
            : asset('metronic/assets/media/svg/files/blank-image.svg');

        $formatChangeValue = function ($value): string {
            if (is_array($value)) {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
            }

            if ($value === null || $value === '') {
                return 'Sin valor';
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            return (string) $value;
        };
    @endphp

    <div class="py-10 property-module">
        <div class="mb-8">
            <a href="{{ route('properties.index') }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver al listado
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-information-5 fs-2hx text-warning me-4"></i>
                <div class="fw-semibold">{{ session('warning') }}</div>
            </div>
        @endif

        <div class="property-page-shell">
            <div class="card property-block-card mb-8">
                <div class="card-body p-8">

                    <div class="row g-8 align-items-stretch">

                        <!-- =========================
                             IZQUIERDA (IMAGEN GRANDE + INFO)
                        ========================== -->
                        <div class="col-xl-8">

                            <div class="row g-6">

                                <!-- IMAGEN GRANDE -->
                                <div class="col-lg-4">
                                    <img src="{{ $photoUrl }}" class="w-100 h-100 rounded"
                                        style="object-fit: cover; min-height: 220px; max-height: 220px;" alt="{{ $property->internal_name }}">
                                </div>

                                <!-- INFO -->
                                <div class="col-lg-8 d-flex flex-column justify-content-between">

                                    <!-- HEADER -->
                                    <div>
                                        <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                            <h1 class="mb-0 fw-bold">{{ $property->internal_name }} -
                                                {{ $property->internal_reference ?: '-' }}
                                            </h1>
                                            <span class="badge {{ $property->status_badge_class }} fs-7">
                                                {{ $property->status_label }}
                                            </span>
                                        </div>

                                        <div class="property-header-meta mb-4 d-flex flex-wrap gap-2">
                                            <span class="meta-pill">
                                                <i class="ki-outline ki-home-2 fs-6"></i>
                                                {{ $property->type?->name ?? '-' }}
                                            </span>

                                            <span class="meta-pill">
                                                <i class="ki-outline ki-geolocation fs-6"></i>
                                                @if ($property->map_url)
                                                    <a href="{{ $property->map_url }}" target="_blank">Ubicación</a>
                                                @else
                                                    -
                                                @endif
                                            </span>

                                            <span class="meta-pill">
                                                <i class="ki-outline ki-profile-user fs-6"></i>
                                                {{ $property->tenant?->full_name ?: ($property->current_tenant_name ?: 'Sin inquilino') }}
                                            </span>
                                        </div>

                                        @if ($property->tenant_id)
                                            <div class="row g-4 mb-4 pt-5 align-items-center">

                                                <!-- POR COBRAR -->
                                                <div class="col-6 col-md-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        
                                                        <div>
                                                            <div class="meta-pill text-warning ">Por cobrar</div>
                                                            <div class="fw-bold fs-5">
                                                                ${{ number_format((float) $propertyPendingAmount, 2) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- VENCIDO -->
                                                <div class="col-6 col-md-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        
                                                        <div>
                                                            <div class="meta-pill  text-danger">Vencido</div>
                                                            <div class="fw-bold fs-5">
                                                                ${{ number_format((float) $propertyOverdueAmount, 2) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- COBRADO -->
                                                <div class="col-6 col-md-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                      
                                                        <div>
                                                            <div class="meta-pill text-success">
                                                                Cobrado ({{ $propertyCurrentMonthLabel }})
                                                            </div>
                                                            <div class="fw-bold fs-5">
                                                                ${{ number_format((float) $propertyCollectedMonthAmount, 2) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- PENDIENTE VALIDACIÓN -->
                                                <div class="col-6 col-md-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        
                                                        <div>
                                                            <div class="meta-pill text-info">Pend. validación</div>
                                                            <div class="fw-bold fs-5">
                                                                {{ (int) $propertyPendingValidationCount }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        @endif
                                    </div>

                                    <!-- RESUMEN RÁPIDO -->
                                    <div class="row g-4">

                                        <div class="col-sm-4">
                                            <div class="property-value-label">Precio renta</div>
                                            <div class="property-value-content">
                                                {{ $property->monthly_rent_price ? '$' . number_format((float) $property->monthly_rent_price, 2) : '-' }}
                                            </div>
                                        </div>

                                        <div class="col-sm-4">
                                            <div class="property-value-label">Contrato inicia</div>
                                            <div class="property-value-content">
                                                {{ $property->contract_starts_at ? $property->contract_starts_at->format('d/m/Y') : '-' }}
                                            </div>
                                        </div>

                                        <div class="col-sm-4">
                                            <div class="property-value-label">Contrato vence</div>
                                            <div class="property-value-content">
                                                {{ $property->contract_expires_at ? $property->contract_expires_at->format('d/m/Y') : '-' }}
                                            </div>
                                        </div>

                                    </div>

                                    <!-- ASIGNAR INQUILINO -->
                                    <div class="mt-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown">
                                                {{ $property->tenant_id ? 'Cambiar inquilino' : 'Asignar inquilino' }}
                                            </button>

                                            <ul class="dropdown-menu">
                                                @if ($property->tenant_id)
                                                    <li>
                                                        <form method="POST"
                                                            action="{{ route('properties.update.tenant', $property) }}"
                                                            class="d-inline js-remove-tenant-form"
                                                            data-tenant-change-allowed="{{ $canRemoveTenant ? 'true' : 'false' }}"
                                                            data-blocked-message="{{ $tenantChangeBlockedMessage }}"
                                                            data-current-tenant-name="{{ $property->tenant?->full_name ?: 'el inquilino actual' }}">
                                                            @csrf
                                                            @method('PUT')

                                                            <input type="hidden" name="tenant_id" value="">

                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-person-dash me-2"></i> Quitar inquilino
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                @endif
                                                @foreach ($tenants as $tenant)
                                                    @php
                                                        $assignmentCheck = $tenantAssignmentChecks[(string) $tenant->id] ?? ['missing' => [], 'is_complete' => true];
                                                    @endphp
                                                    <li>
                                                        <form method="POST"
                                                            action="{{ route('properties.update.tenant', $property) }}"
                                                            class="d-inline js-assign-tenant-form"
                                                            data-tenant-change-allowed="{{ $canReassignTenant ? 'true' : 'false' }}"
                                                            data-blocked-message="{{ $tenantChangeBlockedMessage }}"
                                                            data-tenant-name="{{ $tenant->full_name }}"
                                                            data-missing='@json($assignmentCheck['missing'])'>
                                                            @csrf
                                                            @method('PUT')

                                                            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                                                            <input type="hidden" name="force_assignment" value="0">

                                                            <button type="submit" class="dropdown-item">
                                                                {{ $tenant->full_name }}
                                                                @unless ($assignmentCheck['is_complete'])
                                                                    <span class="text-warning">(incompleto)</span>
                                                                @endunless
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- =========================
                             DERECHA (COBRANZA LIMPIA)
                        ========================== -->
                        <div class="col-xl-4 d-flex flex-column justify-content-between">

                            <div>

                                <!-- HEADER -->
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h3 class="fw-bold mb-0">Cobranza</h3>
                                    <a href="{{ route('charges.index', ['property' => $property->uuid]) }}"
                                        class="btn btn-sm btn-light-primary">
                                        Abrir
                                    </a>
                                </div>

                                <!-- INDICADORES -->
                                <div class="d-flex flex-wrap gap-2 mb-5">
                                    <span class="meta-pill bg-light-warning text-warning badge">
                                        Por cobrar: {{ (int) $chargesPorCobrar }}
                                    </span>

                                    <span class="meta-pill bg-light-danger text-danger badge">
                                        Vencido: {{ (int) $chargesVencidos }}
                                    </span>

                                    <span class="meta-pill bg-light-success text-success badge">
                                        Cobrado: {{ $paidThroughDate?->format('d/m/Y') ?: '-' }}
                                    </span>

                                    <span class="meta-pill bg-light-primary text-primary badge">
                                        Validación: {{ (int) $chargesPendingValidation }}
                                    </span>
                                </div>

                                <!-- DETALLE -->
                                <div class="row g-4">
                                    <div class="col-6">
                                        <div class="property-value-label">Día cobro</div>
                                        <div class="property-value-content">
                                            {{ $property->charge_day ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="property-value-label">Días de Tolerancia</div>
                                        <div class="property-value-content">
                                            {{ is_null($property->charge_tolerance_days) ? '-' : (int) $property->charge_tolerance_days }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="property-value-label">Pagos</div>
                                        <div class="property-value-content">
                                            {{ (int) $rentChargesPaid }}/{{ (int) $rentChargesTotal }}
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>
            </div>

            <div class="property-tabs-wrap">
                <ul class="nav property-tabs-nav" id="propertyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-general-tab" data-bs-toggle="tab"
                            data-bs-target="#tab-general" type="button" role="tab" aria-controls="tab-general"
                            aria-selected="true">
                            Información general
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-owners-tab" data-bs-toggle="tab" data-bs-target="#tab-owners"
                            type="button" role="tab" aria-controls="tab-owners" aria-selected="false">
                            Propietarios
                        </button>
                    </li>
                    @if ($property->tenant)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-tenant-tab" data-bs-toggle="tab" data-bs-target="#tab-tenant"
                                type="button" role="tab" aria-controls="tab-tenant" aria-selected="false">
                                Inquilino
                            </button>
                        </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-extra-tab" data-bs-toggle="tab" data-bs-target="#tab-extra"
                            type="button" role="tab" aria-controls="tab-extra" aria-selected="false">
                            Información adicional
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-charges-tab" data-bs-toggle="tab" data-bs-target="#tab-charges"
                            type="button" role="tab" aria-controls="tab-charges" aria-selected="false">
                            Cobranza
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-expenses-tab" data-bs-toggle="tab" data-bs-target="#tab-expenses"
                            type="button" role="tab" aria-controls="tab-expenses" aria-selected="false">
                            Gastos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-maintenance-tab" data-bs-toggle="tab" data-bs-target="#tab-maintenance"
                            type="button" role="tab" aria-controls="tab-maintenance" aria-selected="false">
                            Mantenimiento
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-inventory-tab" data-bs-toggle="tab" data-bs-target="#tab-inventory"
                            type="button" role="tab" aria-controls="tab-inventory" aria-selected="false">
                            Inventario
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-history-tab" data-bs-toggle="tab" data-bs-target="#tab-history"
                            type="button" role="tab" aria-controls="tab-history" aria-selected="false">
                            Histórico de cambios
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="propertyTabsContent">
                    <div class="tab-pane fade show active property-tab-pane" id="tab-general" role="tabpanel"
                        aria-labelledby="tab-general-tab">
                        <div class="row g-6">
                            <div class="col-xl-7">
                                <div class="card property-block-card h-100">
                                    <div
                                        class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <h3 class="card-title fw-bold mb-0">Información general</h3>
                                        <a href="{{ route('properties.edit', $property) }}"
                                            class="btn btn-sm btn-light-primary">
                                            Editar propiedad
                                        </a>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="row g-6">
                                            <div class="col-12">
                                                <div class="property-value-label">Dirección</div>
                                                <div class="property-value-content">{{ $property->full_address }}</div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="property-value-label">Referencia interna</div>
                                                <div class="property-value-content">
                                                    {{ $property->internal_reference ?: '-' }}
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="property-value-label">Complejo o privada</div>
                                                <div class="property-value-content">{{ $property->complex_name ?: '-' }}
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="property-value-label">Número interior</div>
                                                <div class="property-value-content">{{ $property->official_number ?: '-' }}
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="property-value-label">Número exterior</div>
                                                <div class="property-value-content">{{ $property->unit_number ?: '-' }}
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="property-value-label">Estatus</div>
                                                <div class="property-value-content">{{ $property->status_label }}</div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="property-value-label">Precio renta mensual</div>
                                                <div class="property-value-content">
                                                    {{ $property->monthly_rent_price ? '$' . number_format((float) $property->monthly_rent_price, 2) : '-' }}
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="property-value-label">Día de cobro</div>
                                                <div class="property-value-content">{{ $property->charge_day ?: '-' }}</div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="property-value-label">Tolerancia (días)</div>
                                                <div class="property-value-content">
                                                    {{ is_null($property->charge_tolerance_days) ? '-' : (int) $property->charge_tolerance_days }}
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="property-value-label">Inquilino actual</div>
                                                <div class="property-value-content">
                                                    {{ $property->tenant?->full_name ?: ($property->current_tenant_name ?: '-') }}
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="property-value-label">Contrato inicia</div>
                                                <div class="property-value-content">
                                                    {{ $property->contract_starts_at ? $property->contract_starts_at->format('d/m/Y') : '-' }}
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="property-value-label">Contrato vence</div>
                                                <div class="property-value-content">
                                                    {{ $property->contract_expires_at ? $property->contract_expires_at->format('d/m/Y') : '-' }}
                                                </div>
                                            </div>
                                            @if ($property->map_url)
                                                <div class="col-12">
                                                    <div class="property-value-label">Ubicación en mapa</div>
                                                    <div class="property-value-content">
                                                        <a href="{{ $property->map_url }}" target="_blank"
                                                            class="text-primary fw-semibold">
                                                            Ver en Google Maps
                                                        </a>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-5">
                                <div class="card property-block-card h-100">
                                    <div
                                        class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <h3 class="card-title fw-bold mb-0">Expediente</h3>
                                        <a href="{{ route('dossiers.properties.show', $property) }}"
                                            class="btn btn-sm btn-light-primary">
                                            Abrir expediente
                                        </a>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="d-flex flex-column gap-4">
                                            @foreach ($documents as $document)
                                                <div
                                                    class="border rounded px-5 py-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <i class="ki-outline ki-document text-gray-500 fs-2"></i>
                                                        <span class="fw-semibold">{{ $document->label }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-3">
                                                        @unless ($document->file_path)
                                                            <span class="badge badge-light-warning text-warning">Pendiente</span>
                                                        @endunless
                                                        <span
                                                            class="badge badge-light-info text-info">v{{ $document->versions->count() }}</span>
                                                        @if ($document->file_path)
                                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}"
                                                                class="btn btn-sm btn-light-primary" target="_blank">
                                                                Ver archivo
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if ($customDocuments->isNotEmpty())
                                            <div class="separator my-6"></div>
                                            <h4 class="fw-bold mb-4">Otros documentos</h4>
                                            <div class="d-flex flex-column gap-4">
                                                @foreach ($customDocuments as $document)
                                                    <div
                                                        class="border rounded px-5 py-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <i class="ki-outline ki-document text-gray-500 fs-2"></i>
                                                            <span class="fw-semibold">{{ $document->label }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-3">
                                                            @unless ($document->file_path)
                                                                <span class="badge badge-light-warning text-warning">Pendiente</span>
                                                            @endunless
                                                            <span
                                                                class="badge badge-light-info text-info">v{{ $document->versions->count() }}</span>
                                                            @if ($document->file_path)
                                                                <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}"
                                                                    class="btn btn-sm btn-light-primary" target="_blank">
                                                                    Ver archivo
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($property->owners && $property->owners->count())
                        <div class="tab-pane fade property-tab-pane" id="tab-owners" role="tabpanel"
                            aria-labelledby="tab-owners-tab">

                            @foreach ($property->owners as $owner)
                                @php
                                    $ownerUuid = $owner->uuid ?: $owner->id;
                                @endphp

                                <div class="row g-6 mb-8">

                                    <!-- =========================
                                                                                                 INFO PROPIETARIO (60%)
                                                                                            ========================== -->
                                    <div class="col-xl-7">
                                        <div class="card property-block-card h-100">

                                            <!-- HEADER -->
                                            <div
                                                class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                                <h3 class="card-title fw-bold mb-0">
                                                    Propietario: {{ $owner->name ?: 'Sin nombre' }}
                                                </h3>

                                                <a href="{{ url('/propietarios/' . $ownerUuid . '/editar') }}"
                                                    class="btn btn-sm btn-light-primary">
                                                    Editar propietario
                                                </a>
                                            </div>

                                            <!-- BODY -->
                                            <div class="card-body pt-0">
                                                <div class="row g-6">

                                                    <div class="col-lg-6">
                                                        <div class="property-value-label">Nombre</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->name ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="property-value-label">Teléfono</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->phone ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="property-value-label">Tipo</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->owner_type_label ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-4">
                                                        <div class="property-value-label">Email</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->email ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-4">
                                                        <div class="property-value-label">RFC</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->rfc ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-4">
                                                        <div class="property-value-label">CURP</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->curp ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-4">
                                                        <div class="property-value-label">Banco</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->bank_name ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-4">
                                                        <div class="property-value-label">CLABE</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->clabe ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-4">
                                                        <div class="property-value-label">Titular de cuenta</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->account_holder ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-4">
                                                        <div class="property-value-label">Método de pago</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->payment_method_label ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-4">
                                                        <div class="property-value-label">Estatus</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->is_active ? 'Activo' : 'Inactivo' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-12">
                                                        <div class="property-value-label">Dirección</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->address ?: '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-12">
                                                        <div class="property-value-label">Notas</div>
                                                        <div class="property-value-content">
                                                            {{ $owner->notes ?: '-' }}
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- =========================
                                                                                                 EXPEDIENTE (40%)
                                                                                            ========================== -->
                                    <div class="col-xl-5">
                                        <div class="card property-block-card h-100">

                                            <!-- HEADER -->
                                            <div
                                                class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                                <h3 class="card-title fw-bold mb-0">Expediente</h3>

                                                <a href="{{ url('/propietarios/' . $ownerUuid . '/expediente') }}"
                                                    class="btn btn-sm btn-light-primary">
                                                    Ver expediente
                                                </a>
                                            </div>

                                            <!-- BODY -->
                                            <div class="card-body pt-0">
                                                <div class="d-flex flex-column gap-4">

                                                    @foreach ($owner->documents as $document)
                                                        <div
                                                            class="border rounded px-5 py-4 d-flex justify-content-between align-items-center flex-wrap gap-3">

                                                            <div class="d-flex align-items-center gap-3">
                                                                <i class="ki-outline ki-document text-gray-500 fs-2"></i>
                                                                <span class="fw-semibold">{{ $document->label }}</span>
                                                            </div>

                                                            <div class="d-flex align-items-center gap-3">

                                                                @unless ($document->file_path)
                                                                    <span class="badge badge-light-warning text-warning">Pendiente</span>
                                                                @endunless

                                                                <span class="badge badge-light-info text-info">
                                                                    v{{ $document->versions->count() }}
                                                                </span>

                                                                @if ($document->file_path)
                                                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}"
                                                                        class="btn btn-sm btn-light-primary" target="_blank">
                                                                        Ver archivo
                                                                    </a>
                                                                @endif

                                                            </div>

                                                        </div>
                                                    @endforeach

                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            @endforeach

                        </div>
                    @endif

                    @if ($property->tenant)
                        @php
                            $tenantUuid = $property->tenant?->uuid ?: $property->tenant?->id;
                        @endphp
                        <div class="tab-pane fade property-tab-pane" id="tab-tenant" role="tabpanel"
                            aria-labelledby="tab-tenant-tab">
                            <div class="row g-6">

                                <!-- =========================
                                                                                                 INFO INQUILINO (60%)
                                                                                            ========================== -->
                                <div class="col-xl-7">
                                    <div class="card property-block-card h-100">

                                        <!-- HEADER -->
                                        <div
                                            class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                            <h3 class="card-title fw-bold mb-0">Información del inquilino</h3>

                                            <div class="d-flex flex-wrap gap-2">
                                                <a href="{{ url('/inquilinos/' . $tenantUuid . '/editar') }}"
                                                    class="btn btn-sm btn-light-primary">
                                                    Editar inquilino
                                                </a>
                                                <form method="POST"
                                                    action="{{ route('properties.update.tenant', $property) }}"
                                                    class="d-inline js-remove-tenant-form"
                                                    data-tenant-change-allowed="{{ $canRemoveTenant ? 'true' : 'false' }}"
                                                    data-blocked-message="{{ $tenantChangeBlockedMessage }}"
                                                    data-current-tenant-name="{{ $property->tenant?->full_name ?: 'el inquilino actual' }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="tenant_id" value="">
                                                    <button type="submit" class="btn btn-sm btn-light-danger">
                                                        <i class="bi bi-person-dash me-1"></i> Quitar inquilino
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- BODY -->
                                        <div class="card-body pt-0">
                                            <div class="row g-6">

                                                <div class="col-lg-6">
                                                    <div class="property-value-label">Nombre completo</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->full_name ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-3">
                                                    <div class="property-value-label">Teléfono principal</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->phone_primary ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-3">
                                                    <div class="property-value-label">Teléfono secundario</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->phone_secondary ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="property-value-label">Email</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->email ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="property-value-label">CURP</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->curp ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="property-value-label">RFC</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->rfc ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="property-value-label">Empresa</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->employer ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="property-value-label">Ocupación</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->occupation ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="property-value-label">Ingreso mensual</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->monthly_income ? '$' . number_format((float) $property->tenant?->monthly_income, 2) : '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="property-value-label">Años laborales</div>
                                                    <div class="property-value-content">
                                                        {{ is_null($property->tenant?->employment_years) ? '-' : (int) $property->tenant?->employment_years }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="property-value-label">Estado del expediente</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->dossier_status_label ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="property-value-label">Contacto de emergencia</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->emergency_contact_name ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="property-value-label">Tel. emergencia</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->emergency_contact_phone ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-6">
                                                    <div class="property-value-label">Referencia personal</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->personal_reference_name ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-6">
                                                    <div class="property-value-label">Tel. referencia personal</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->personal_reference_phone ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-6">
                                                    <div class="property-value-label">Referencia laboral</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->work_reference_name ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-6">
                                                    <div class="property-value-label">Tel. referencia laboral</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->work_reference_phone ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-6">
                                                    <div class="property-value-label">Domicilio actual</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->current_address ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-lg-6">
                                                    <div class="property-value-label">Domicilio anterior</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->previous_address ?: '-' }}
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="property-value-label">Notas</div>
                                                    <div class="property-value-content">
                                                        {{ $property->tenant?->notes ?: '-' }}
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- =========================
                                                                                                 EXPEDIENTE (40%)
                                                                                            ========================== -->
                                <div class="col-xl-5">
                                    <div class="card property-block-card h-100">

                                        <!-- HEADER -->
                                        <div
                                            class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                            <h3 class="card-title fw-bold mb-0">Expediente</h3>

                                            <a href="{{ url('/inquilinos/' . $tenantUuid . '/expediente') }}"
                                                class="btn btn-sm btn-light-primary">
                                                Ver expediente
                                            </a>
                                        </div>

                                        <!-- BODY -->
                                        <div class="card-body pt-0">
                                            <div class="d-flex flex-column gap-4">

                                                @foreach ($tenantDocuments as $document)
                                                    <div
                                                        class="border rounded px-5 py-4 d-flex justify-content-between align-items-center flex-wrap gap-3">

                                                        <div class="d-flex align-items-center gap-3">
                                                            <i class="ki-outline ki-document text-gray-500 fs-2"></i>
                                                            <span class="fw-semibold">{{ $document->label }}</span>
                                                        </div>

                                                        <div class="d-flex align-items-center gap-3">

                                                            @unless ($document->file_path)
                                                                <span class="badge badge-light-warning text-warning">Pendiente</span>
                                                            @endunless

                                                            <span class="badge badge-light-info text-info">
                                                                v{{ $document->versions->count() }}
                                                            </span>

                                                            @if ($document->file_path)
                                                                <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}"
                                                                    class="btn btn-sm btn-light-primary" target="_blank">
                                                                    Ver archivo
                                                                </a>
                                                            @endif

                                                        </div>

                                                    </div>
                                                @endforeach

                                                @if ($tenantCustomDocuments->isNotEmpty())
                                                    <div class="separator my-6"></div>

                                                    <h4 class="fw-bold mb-2">Otros documentos</h4>

                                                    @foreach ($tenantCustomDocuments as $document)
                                                        <div
                                                            class="border rounded px-5 py-4 d-flex justify-content-between align-items-center flex-wrap gap-3">

                                                            <div class="d-flex align-items-center gap-3">
                                                                <i class="ki-outline ki-document text-gray-500 fs-2"></i>
                                                                <span class="fw-semibold">{{ $document->label }}</span>
                                                            </div>

                                                            <div class="d-flex align-items-center gap-3">

                                                                @unless ($document->file_path)
                                                                    <span class="badge badge-light-warning text-warning">Pendiente</span>
                                                                @endunless

                                                                <span class="badge badge-light-info text-info">
                                                                    v{{ $document->versions->count() }}
                                                                </span>

                                                                @if ($document->file_path)
                                                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}"
                                                                        class="btn btn-sm btn-light-primary" target="_blank">
                                                                        Ver archivo
                                                                    </a>
                                                                @endif

                                                            </div>

                                                        </div>
                                                    @endforeach
                                                @endif

                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                    @endif

                    <div class="tab-pane fade property-tab-pane" id="tab-extra" role="tabpanel"
                        aria-labelledby="tab-extra-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold">Datos adicionales</h3>
                            </div>
                            <div class="card-body pt-0">
                                @if ($property->details || $property->description || $property->rental_requirements || $property->amenities)
                                    <div class="row g-6">
                                        @if ($property->details)
                                            <div class="col-lg-6 col-12">
                                                <div class="property-value-label">Detalles</div>
                                                <div class="ck-content">{!! $property->details !!}</div>
                                            </div>
                                        @endif

                                        @if ($property->description)
                                            <div class="col-lg-6 col-12">
                                                <div class="property-value-label">Descripción</div>
                                                <div class="ck-content">{!! $property->description !!}</div>
                                            </div>
                                        @endif

                                        @if ($property->rental_requirements)
                                            <div class="col-lg-6 col-12">
                                                <div class="property-value-label">Requisitos de renta</div>
                                                <div class="ck-content">{!! $property->rental_requirements !!}</div>
                                            </div>
                                        @endif

                                        @if ($property->amenities)
                                            <div class="col-lg-6 col-12">
                                                <div class="property-value-label">Amenidades</div>
                                                <div class="ck-content">{!! $property->amenities !!}</div>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="alert alert-light-info mb-0">No hay información adicional capturada.</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade property-tab-pane" id="tab-charges" role="tabpanel"
                        aria-labelledby="tab-charges-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="card-title fw-bold mb-1">Cobranza</h3>
                                    <div class="text-muted fs-7">Pagos completos:
                                        {{ (int) $rentChargesPaid }}/{{ (int) $rentChargesTotal }}
                                    </div>
                                </div>
                                <a href="{{ route('charges.index', ['property' => $property->uuid]) }}"
                                    class="btn btn-sm btn-light-primary">Abrir cobranza</a>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered align-middle mb-0">
                                        <thead>
                                            <tr class="text-muted text-uppercase fs-8">
                                                <th>Concepto</th>
                                                <th>Periodo</th>
                                                <th>Vencimiento</th>
                                                <th>Monto</th>
                                                <th>Estado</th>
                                                <th class="text-end">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($propertyCharges as $charge)
                                                <tr>
                                                    <td>{{ $charge->concept }}</td>
                                                    <td>{{ str_pad((string) $charge->period_month, 2, '0', STR_PAD_LEFT) }}/{{ $charge->period_year }}
                                                    </td>
                                                    <td>{{ $charge->due_date?->format('d/m/Y') ?? '-' }}</td>
                                                    <td>${{ number_format((float) $charge->amount, 2) }}</td>
                                                    <td>
                                                        <span
                                                            class="badge {{ $charge->status_badge_class }}">{{ $charge->display_status_label }}</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="{{ route('charges.show', [
                                                            'charge' => $charge,
                                                            'property' => $property->uuid,
                                                            'return_to' => route('properties.show', $property) . '#tab-charges',
                                                        ]) }}"
                                                            class="btn btn-sm btn-light">Ver</a>
                                                        @php
                                                            $canDeleteThisCharge = $canManageCharges
                                                                && $charge->status !== \App\Models\Charge::STATUS_CANCELED
                                                                && ($charge->status !== \App\Models\Charge::STATUS_PAID || $canDeletePaidCharges);
                                                        @endphp
                                                        @if ($canDeleteThisCharge)
                                                            <form method="POST" action="{{ route('charges.destroy', $charge) }}"
                                                                class="d-inline js-delete-charge-form"
                                                                data-charge-concept="{{ $charge->concept }}"
                                                                data-charge-paid="{{ $charge->status === \App\Models\Charge::STATUS_PAID ? 'true' : 'false' }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" name="deletion_note" value="">
                                                                <input type="hidden" name="return_to"
                                                                    value="{{ route('properties.show', $property) }}#tab-charges">
                                                                <button type="submit" class="btn btn-sm btn-light-danger">Eliminar</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-8">No hay cargos
                                                        registrados para esta propiedad.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('expenses.partials.property-tab')

                    <div class="tab-pane fade property-tab-pane" id="tab-maintenance" role="tabpanel"
                        aria-labelledby="tab-maintenance-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="card-title fw-bold mb-1">Mantenimiento de la propiedad</h3>
                                    <div class="text-muted fs-7">
                                        Tickets históricos: {{ $propertyMaintenanceTickets->count() }}
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('maintenance.index', ['property' => $property->uuid]) }}" class="btn btn-sm btn-light-primary">
                                        Abrir módulo
                                    </a>
                                    @if ($canCreatePropertyMaintenanceTicket)
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createPropertyMaintenanceTicketModal">
                                            Crear ticket
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered align-middle mb-0">
                                        <thead>
                                            <tr class="text-muted text-uppercase fs-8">
                                                <th>Folio</th>
                                                <th>Ticket</th>
                                                <th>Categoría</th>
                                                <th>Prioridad</th>
                                                <th>Estado</th>
                                                <th>Técnico/Proveedor</th>
                                                <th>Fecha</th>
                                                <th class="text-end">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($propertyMaintenanceTickets as $ticket)
                                                <tr>
                                                    <td>{{ $ticket->reference ?: \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($ticket->uuid, 0, 8)) }}</td>
                                                    <td>
                                                        <div class="fw-semibold">{{ $ticket->title }}</div>
                                                        <div class="text-muted fs-8">{{ $ticket->files_count }} archivos · {{ $ticket->messages_count }} mensajes</div>
                                                    </td>
                                                    <td>{{ \App\Models\MaintenanceTicket::CATEGORY_LABELS[$ticket->category] ?? $ticket->category }}</td>
                                                    <td>{{ \App\Models\MaintenanceTicket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority }}</td>
                                                    <td>
                                                        <span class="badge badge-light">
                                                            {{ \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $ticket->currentProvider?->name ?: 'Sin asignar' }}</td>
                                                    <td>{{ $ticket->reported_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                                    <td class="text-end">
                                                        <a href="{{ route('maintenance.show', $ticket) }}" class="btn btn-sm btn-light">Ver</a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center py-8 text-muted">No hay tickets de mantenimiento para esta propiedad.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade property-tab-pane" id="tab-inventory" role="tabpanel"
                        aria-labelledby="tab-inventory-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <h3 class="card-title fw-bold">Inventario</h3>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('properties.inventory.edit', $property) }}"
                                        class="btn btn-sm btn-light-primary">
                                        <i class="ki-outline ki-pencil fs-5 me-1"></i> Editar inventario
                                    </a>
                                     <a href="{{ route('inventory-checks.index', $property) }}"
                                    class="btn btn-sm btn-light-primary">Checks Entrada /salida</a>
                                    <a href="{{ route('inventory-checks.export-pdf', $property) }}"
                                        class="btn btn-sm btn-light-success">
                                        <i class="ki-outline ki-file-down fs-5 me-1"></i> Descargar PDF
                                    </a>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @if ($property->inventoryAreas->isEmpty())
                                    <div class="alert alert-light-info mb-0">No hay inventario capturado todavía.</div>
                                @else
                                    <div class="d-flex flex-column gap-6">
                                        @foreach ($property->inventoryAreas as $area)
                                            <div class="border rounded p-5">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <h4 class="mb-0">{{ $area->name }}</h4>
                                                    <span class="text-muted">{{ $area->items->count() }} elementos</span>
                                                </div>

                                                @if ($area->notes)
                                                    <p class="text-gray-700 mb-4">{{ $area->notes }}</p>
                                                @endif

                                                @if ($area->photos->isNotEmpty())
                                                    <div class="d-flex flex-wrap gap-4 mb-4">
                                                        @foreach ($area->photos as $photo)
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->file_path) }}"
                                                                class="inventory-thumb" alt="{{ $area->name }}">
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if ($area->items->isNotEmpty())
                                                    <div class="table-responsive">
                                                        <table class="table table-row-bordered align-middle mb-0">
                                                            <thead>
                                                                <tr class="text-muted text-uppercase fs-8">
                                                                    <th>Elemento</th>
                                                                    <th>Estado</th>
                                                                    <th>Notas</th>
                                                                    <th>Fotos</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($area->items as $item)
                                                                    <tr>
                                                                        <td>{{ $item->name }}</td>
                                                                        <td>{{ $item->condition ?: '-' }}</td>
                                                                        <td>{{ $item->notes ?: '-' }}</td>
                                                                        <td>
                                                                            @if ($item->photos->isNotEmpty())
                                                                                <div class="d-flex gap-2">
                                                                                    @foreach ($item->photos as $photo)
                                                                                        @if ($photo->latestVersion)
                                                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->latestVersion->file_path) }}"
                                                                                                class="property-thumb" alt="Foto {{ $item->name }}">
                                                                                        @endif
                                                                                    @endforeach
                                                                                </div>
                                                                            @else
                                                                                -
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade property-tab-pane" id="tab-history" role="tabpanel"
                        aria-labelledby="tab-history-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold">Historico de cambios</h3>
                            </div>
                            <div class="card-body pt-0">
                                @if ($propertyChangeLogs->isEmpty())
                                    <div class="alert alert-light-info mb-0">No hay cambios registrados para esta propiedad.
                                    </div>
                                @else
                                    <div class="d-flex flex-column gap-6">
                                        @foreach ($propertyChangeLogs as $changeLog)
                                            @php
                                                $changeSet = collect($changeLog->change_set ?? [])->filter(fn($item) => is_array($item));
                                            @endphp

                                            @if ($changeSet->isNotEmpty())
                                                <div class="border rounded p-5">
                                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                                        <div class="fw-bold text-gray-900">
                                                            {{ $changeLog->user?->name ?: 'Sistema' }}
                                                        </div>
                                                        <span class="badge badge-light-primary text-primary">
                                                            {{ $changeLog->changed_at?->format('d/m/Y H:i') ?: '-' }}
                                                        </span>
                                                    </div>

                                                    <div class="d-flex flex-column gap-4">
                                                        @foreach ($changeSet as $field => $values)
                                                            @php
                                                                $label = $propertyChangeFieldLabels[$field] ?? \Illuminate\Support\Str::of($field)->replace('_', ' ')->title();
                                                            @endphp
                                                            <div class="border rounded p-4 bg-light">
                                                                <div class="fw-bold mb-3">{{ $label }}</div>
                                                                <div class="change-log-grid">
                                                                    <div>
                                                                        <div class="change-log-tag">Valor anterior</div>
                                                                        <pre
                                                                            class="change-log-value">{{ $formatChangeValue($values['old'] ?? null) }}</pre>
                                                                    </div>
                                                                    <div>
                                                                        <div class="change-log-tag">Valor nuevo</div>
                                                                        <pre
                                                                            class="change-log-value">{{ $formatChangeValue($values['new'] ?? null) }}</pre>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canCreatePropertyMaintenanceTicket)
        <div class="modal fade" id="createPropertyMaintenanceTicketModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('maintenance.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="property_id" value="{{ $property->id }}">

                        <div class="modal-header">
                            <h3 class="modal-title">Nuevo ticket de mantenimiento</h3>
                            <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-4">
                                @if (!$isTenantMaintenanceReporter)
                                    <div class="col-md-4">
                                        <label class="form-label required">Categoría</label>
                                        <select class="form-select" name="category" required>
                                            @foreach ($maintenanceCategoryOptions as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label required">Prioridad</label>
                                        <select class="form-select" name="priority" required>
                                            @foreach ($maintenancePriorityOptions as $key => $label)
                                                <option value="{{ $key }}" {{ $key === 'media' ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label required">Fecha reporte</label>
                                        <input class="form-control" type="datetime-local" name="reported_at" value="{{ now()->format('Y-m-d\\TH:i') }}" required>
                                    </div>
                                @endif
                                <div class="col-md-12">
                                    <label class="form-label required">Título</label>
                                    <input class="form-control" type="text" name="title" maxlength="190" required>
                                </div>
                                @if (!$isTenantMaintenanceReporter)
                                    <div class="col-md-12">
                                        <label class="form-label required">Ubicación exacta</label>
                                        <input class="form-control" type="text" name="exact_location" maxlength="255" placeholder="Ej: Baño principal, recámara 2" required>
                                    </div>
                                @endif
                                <div class="col-md-12">
                                    <label class="form-label required">Descripción</label>
                                    <textarea class="form-control" rows="4" name="description" maxlength="10000" required></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label {{ $isTenantMaintenanceReporter ? 'required' : '' }}">Archivos {{ $isTenantMaintenanceReporter ? '(mínimo 1)' : '(opcional)' }}</label>
                                    <input class="form-control" type="file" name="files[]" multiple {{ $isTenantMaintenanceReporter ? 'required' : '' }}>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Crear ticket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @include('charges.partials.delete-confirmation-script')

    <script>
        (() => {
            const showTenantChangeBlockedAlert = async (form) => {
                const message = form.dataset.blockedMessage
                    || 'No es posible cambiar el inquilino mientras existan cargos pendientes, en validación o vencidos.';

                if (window.Swal?.fire) {
                    await window.Swal.fire({
                        title: 'No es posible cambiar el inquilino',
                        text: message,
                        icon: 'warning',
                        confirmButtonText: 'Entendido',
                    });
                } else {
                    window.alert(message);
                }
            };

            document.querySelectorAll('.js-remove-tenant-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (form.dataset.tenantChangeAllowed === 'false') {
                        await showTenantChangeBlockedAlert(form);
                        return;
                    }

                    const tenantName = form.dataset.currentTenantName || 'el inquilino actual';
                    const message = `¿Deseas quitar a ${tenantName} de esta propiedad?`;
                    let confirmed = false;

                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            title: 'Quitar inquilino',
                            text: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, quitar',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#d9214e',
                            reverseButtons: true,
                        });
                        confirmed = !!result.isConfirmed;
                    } else {
                        confirmed = window.confirm(message);
                    }

                    if (confirmed) {
                        form.submit();
                    }
                });
            });

            document.querySelectorAll('.js-assign-tenant-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    if (form.dataset.tenantChangeAllowed === 'false') {
                        event.preventDefault();
                        await showTenantChangeBlockedAlert(form);
                        return;
                    }

                    const forceInput = form.querySelector('input[name="force_assignment"]');
                    if (forceInput && forceInput.value === '1') {
                        return;
                    }

                    let missing = [];
                    try {
                        missing = JSON.parse(form.dataset.missing || '[]');
                    } catch (error) {
                        missing = [];
                    }

                    if (!Array.isArray(missing) || !missing.length) {
                        return;
                    }

                    event.preventDefault();

                    const tenantName = form.dataset.tenantName || 'este inquilino';
                    const details = missing.join('<br>');

                    const message = `El inquilino ${tenantName} tiene datos o documentos incompletos:<br><br>- ${details}<br><br>¿Deseas continuar con la asignación?`;

                    let confirmed = false;

                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            title: 'Inquilino incompleto',
                            html: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, continuar',
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true,
                        });
                        confirmed = !!result.isConfirmed;
                    } else {
                        confirmed = window.confirm(message);
                    }

                    if (!confirmed) {
                        return;
                    }

                    if (forceInput) {
                        forceInput.value = '1';
                    }
                    form.submit();
                });
            });

            const tabButtons = document.querySelectorAll('#propertyTabs [data-bs-toggle="tab"]');
            const tabRestoreKey = `suwork:property-tab-restore:${window.location.pathname}`;
            const rememberActiveTab = () => {
                const activeButton = document.querySelector('#propertyTabs [data-bs-toggle="tab"].active');
                const target = activeButton?.getAttribute('data-bs-target') || '';
                if (target.startsWith('#tab-')) {
                    try {
                        sessionStorage.setItem(tabRestoreKey, target);
                    } catch (error) {
                        // The URL hash remains as the fallback when storage is unavailable.
                    }
                }
            };

            document.addEventListener('submit', rememberActiveTab, true);

            tabButtons.forEach((button) => {
                button.addEventListener('shown.bs.tab', (event) => {
                    const target = event.target.getAttribute('data-bs-target') || '';
                    if (!target.startsWith('#')) {
                        return;
                    }

                    history.replaceState(null, '', target);
                });
            });

            const hashTarget = window.location.hash.startsWith('#tab-') ? window.location.hash : '';
            let storedTarget = '';
            try {
                storedTarget = sessionStorage.getItem(tabRestoreKey) || '';
                sessionStorage.removeItem(tabRestoreKey);
            } catch (error) {
                storedTarget = '';
            }
            const targetToRestore = hashTarget || storedTarget;

            if (/^#tab-[a-z0-9-]+$/i.test(targetToRestore)) {
                const hashButton = document.querySelector(`#propertyTabs [data-bs-target="${targetToRestore}"]`);
                if (hashButton) {
                    history.replaceState(null, '', targetToRestore);
                    new bootstrap.Tab(hashButton).show();
                }
            }
        })();
    </script>

    @if ($errors->createExpense->any() || $errors->updateExpense->any() || $errors->expensePropertySetup->any() || $errors->recurringExpenseItem->any())
        <script>
            (() => {
                const tabButton = document.querySelector('#propertyTabs [data-bs-target="#tab-expenses"]');
                if (!tabButton) {
                    return;
                }

                history.replaceState(null, '', '#tab-expenses');
                new bootstrap.Tab(tabButton).show();
            })();
        </script>
    @endif

    @if ($errors->createMaintenanceTicket->any())
        <script>
            (() => {
                const tabButton = document.querySelector('#propertyTabs [data-bs-target="#tab-maintenance"]');
                if (tabButton) {
                    history.replaceState(null, '', '#tab-maintenance');
                    new bootstrap.Tab(tabButton).show();
                }

                const modalEl = document.getElementById('createPropertyMaintenanceTicketModal');
                if (!modalEl) {
                    return;
                }
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif
@endpush
