@extends('layouts.app')

@section('title', $tenant->full_name . ' | SuWork')

@php
    $initials = collect(preg_split('/\s+/', trim((string) $tenant->full_name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

@push('styles')
    <style>
        .tenant-show-module {
            --ts-surface: #ffffff;
            --ts-ink: #172033;
            --ts-text: #334155;
            --ts-muted: #7b879d;
            --ts-line: #e5eaf3;
            --ts-accent: #b54708;
            --ts-accent-soft: #fff1e8;
            --ts-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            color: var(--ts-text);
        }

        .tenant-show-back {
            color: #6b7280;
            font-weight: 700;
        }

        .tenant-show-hero,
        .tenant-show-card,
        .tenant-show-property-card {
            border: 1px solid var(--ts-line);
            border-radius: 18px;
            background: var(--ts-surface);
            box-shadow: var(--ts-shadow);
        }

        .tenant-show-avatar {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ts-accent-soft);
            color: var(--ts-accent);
            font-size: 1.45rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .tenant-show-title {
            color: var(--ts-ink);
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 800;
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .tenant-show-subtitle {
            color: var(--ts-muted);
            font-size: 0.98rem;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .tenant-show-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .tenant-show-actions .btn {
            border-radius: 10px;
            font-weight: 700;
        }

        .tenant-show-stat {
            border: 1px solid var(--ts-line);
            border-radius: 14px;
            padding: 16px;
            background: #fbfcfe;
        }

        .tenant-show-stat__label {
            color: var(--ts-muted);
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .tenant-show-stat__value {
            color: var(--ts-ink);
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1;
        }

        .tenant-show-card__title {
            color: var(--ts-ink);
            font-size: 1rem;
            font-weight: 800;
        }

        .tenant-show-field {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 13px 0;
            border-bottom: 1px solid #f0f3f8;
        }

        .tenant-show-field:last-child {
            border-bottom: 0;
        }

        .tenant-show-field__label {
            color: var(--ts-muted);
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .tenant-show-field__value {
            max-width: 62%;
            color: var(--ts-text);
            font-weight: 700;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .tenant-show-note {
            color: var(--ts-text);
            line-height: 1.55;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .tenant-show-property-card {
            padding: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
        }

        .tenant-show-property-card__title {
            color: var(--ts-ink);
            font-weight: 800;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .tenant-show-property-card__meta {
            color: var(--ts-muted);
            font-size: 0.84rem;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        @media (max-width: 767.98px) {
            .tenant-show-module.py-10 {
                padding-top: 1.25rem !important;
                padding-bottom: 1.25rem !important;
            }

            .tenant-show-module .mb-8 {
                margin-bottom: 18px !important;
            }

            .tenant-show-hero,
            .tenant-show-card,
            .tenant-show-property-card {
                border-radius: 8px;
            }

            .tenant-show-hero .card-body,
            .tenant-show-card .card-body {
                padding: 18px !important;
            }

            .tenant-show-header {
                align-items: flex-start !important;
                gap: 12px !important;
            }

            .tenant-show-avatar {
                width: 50px;
                height: 50px;
                border-radius: 8px;
                font-size: 1rem;
            }

            .tenant-show-title {
                font-size: 1.45rem;
                line-height: 1.15;
            }

            .tenant-show-subtitle {
                font-size: 0.84rem;
                line-height: 1.35;
            }

            .tenant-show-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
                gap: 8px;
                margin-top: 14px;
            }

            .tenant-show-actions .btn {
                width: 100%;
                min-width: 0;
                border-radius: 8px;
                padding: 9px 10px;
                font-size: 0.78rem;
                line-height: 1.2;
                white-space: normal;
            }

            .tenant-show-stat {
                border-radius: 8px;
                padding: 14px;
            }

            .tenant-show-stat__label {
                font-size: 0.66rem;
                line-height: 1.2;
            }

            .tenant-show-stat__value {
                font-size: 1.45rem;
            }

            .tenant-show-field {
                gap: 12px;
                padding: 11px 0;
            }

            .tenant-show-field__label {
                flex: 0 0 92px;
                font-size: 0.66rem;
                line-height: 1.25;
            }

            .tenant-show-field__value {
                max-width: none;
                min-width: 0;
                font-size: 0.84rem;
                line-height: 1.35;
            }

            .tenant-show-property-card {
                padding: 16px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 property-module tenant-show-module">
        <div class="mb-8">
            <a href="{{ route('tenants.index') }}" class="tenant-show-back text-hover-primary">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a inquilinos
            </a>
        </div>

        <div class="card tenant-show-hero mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-wrap justify-content-between gap-5 tenant-show-header">
                    <div class="d-flex gap-4 min-w-0">
                        <div class="tenant-show-avatar">{{ $initials ?: 'I' }}</div>
                        <div class="min-w-0">
                            <span class="badge {{ $tenant->dossier_status_badge_class }} mb-3">{{ $tenant->dossier_status_label }}</span>
                            <h1 class="tenant-show-title mb-2">{{ $tenant->full_name }}</h1>
                            <div class="tenant-show-subtitle">
                                {{ $tenant->occupation ?: 'Sin ocupacion registrada' }} - {{ $tenant->is_active ? 'Activo' : 'Inactivo' }}
                            </div>
                        </div>
                    </div>

                    <div class="tenant-show-actions">
                        <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-primary">
                            <i class="ki-outline ki-pencil fs-4 me-1"></i> Editar
                        </a>
                        <a href="{{ route('dossiers.tenants.show', $tenant) }}" class="btn btn-light-info">
                            Expediente
                        </a>
                    </div>
                </div>

                <div class="row g-4 mt-5">
                    <div class="col-6 col-lg-3">
                        <div class="tenant-show-stat h-100">
                            <div class="tenant-show-stat__label mb-2">Propiedades</div>
                            <div class="tenant-show-stat__value">{{ $tenant->properties_count }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="tenant-show-stat h-100">
                            <div class="tenant-show-stat__label mb-2">Documentos</div>
                            <div class="tenant-show-stat__value">{{ $tenant->documents_count }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="tenant-show-stat h-100">
                            <div class="tenant-show-stat__label mb-2">Pagos renta</div>
                            <div class="tenant-show-stat__value">{{ (int) $tenant->paid_rent_charges_count }}/{{ (int) $tenant->total_rent_charges_count }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="tenant-show-stat h-100">
                            <div class="tenant-show-stat__label mb-2">Ingreso</div>
                            <div class="fw-bold text-gray-800">
                                {{ $tenant->monthly_income ? '$' . number_format((float) $tenant->monthly_income, 2) : '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-6">
            <div class="col-lg-6">
                <div class="card tenant-show-card h-100">
                    <div class="card-body p-7">
                        <div class="tenant-show-card__title mb-4">Contacto</div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">Telefono</div>
                            <div class="tenant-show-field__value">{{ $tenant->phone_primary ?: '-' }}</div>
                        </div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">Telefono 2</div>
                            <div class="tenant-show-field__value">{{ $tenant->phone_secondary ?: '-' }}</div>
                        </div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">Email</div>
                            <div class="tenant-show-field__value">{{ $tenant->email ?: '-' }}</div>
                        </div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">RFC</div>
                            <div class="tenant-show-field__value">{{ $tenant->rfc ?: '-' }}</div>
                        </div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">CURP</div>
                            <div class="tenant-show-field__value">{{ $tenant->curp ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card tenant-show-card h-100">
                    <div class="card-body p-7">
                        <div class="tenant-show-card__title mb-4">Perfil laboral</div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">Empleador</div>
                            <div class="tenant-show-field__value">{{ $tenant->employer ?: '-' }}</div>
                        </div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">Ocupacion</div>
                            <div class="tenant-show-field__value">{{ $tenant->occupation ?: '-' }}</div>
                        </div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">Ingreso</div>
                            <div class="tenant-show-field__value">
                                {{ $tenant->monthly_income ? '$' . number_format((float) $tenant->monthly_income, 2) : '-' }}
                            </div>
                        </div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">Antiguedad</div>
                            <div class="tenant-show-field__value">
                                {{ $tenant->employment_years ? $tenant->employment_years . ' anios' : '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card tenant-show-card h-100">
                    <div class="card-body p-7">
                        <div class="tenant-show-card__title mb-4">Referencias</div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">Personal</div>
                            <div class="tenant-show-field__value">
                                {{ $tenant->personal_reference_name ?: '-' }}
                                @if ($tenant->personal_reference_phone)
                                    <br>{{ $tenant->personal_reference_phone }}
                                @endif
                            </div>
                        </div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">Laboral</div>
                            <div class="tenant-show-field__value">
                                {{ $tenant->work_reference_name ?: '-' }}
                                @if ($tenant->work_reference_phone)
                                    <br>{{ $tenant->work_reference_phone }}
                                @endif
                            </div>
                        </div>
                        <div class="tenant-show-field">
                            <div class="tenant-show-field__label">Emergencia</div>
                            <div class="tenant-show-field__value">
                                {{ $tenant->emergency_contact_name ?: '-' }}
                                @if ($tenant->emergency_contact_phone)
                                    <br>{{ $tenant->emergency_contact_phone }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card tenant-show-card h-100">
                    <div class="card-body p-7">
                        <div class="tenant-show-card__title mb-4">Direcciones y notas</div>
                        <div class="mb-5">
                            <div class="tenant-show-field__label mb-2">Domicilio actual</div>
                            <div class="tenant-show-note">{{ $tenant->current_address ?: '-' }}</div>
                        </div>
                        <div class="mb-5">
                            <div class="tenant-show-field__label mb-2">Domicilio anterior</div>
                            <div class="tenant-show-note">{{ $tenant->previous_address ?: '-' }}</div>
                        </div>
                        <div>
                            <div class="tenant-show-field__label mb-2">Notas</div>
                            <div class="tenant-show-note">{{ $tenant->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <h2 class="fw-bold text-dark mb-0 fs-3">Propiedades asociadas</h2>
                <span class="text-muted fw-semibold">{{ $tenant->properties_count }} registros</span>
            </div>

            <div class="row g-4">
                @forelse ($tenant->properties as $property)
                    <div class="col-lg-6">
                        <div class="tenant-show-property-card h-100">
                            <div class="d-flex flex-wrap justify-content-between gap-3">
                                <div class="min-w-0">
                                    <div class="tenant-show-property-card__title mb-1">{{ $property->internal_name }}</div>
                                    <div class="tenant-show-property-card__meta">
                                        {{ $property->internal_reference ?: 'Sin referencia' }}
                                    </div>
                                </div>
                                <span class="badge {{ $property->status_badge_class }}">{{ $property->status_label }}</span>
                            </div>
                            <div class="tenant-show-property-card__meta mt-3">
                                {{ $property->type?->name ?: '-' }} - {{ $property->zone?->name ?: ($property->zone_text ?: '-') }}
                            </div>
                            <div class="tenant-show-property-card__meta mt-2">{{ $property->full_address ?: '-' }}</div>
                            <div class="mt-4">
                                <a href="{{ route('properties.show', $property) }}" class="btn btn-sm btn-light-primary fw-bold">
                                    Ver propiedad
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-light-info mb-0">Este inquilino aun no tiene propiedades asociadas.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
