@extends('layouts.app')

@section('title', $owner->name . ' | SuWork')

@push('styles')
    <style>
        .owner-show-module {
            --os-surface: #ffffff;
            --os-ink: #172033;
            --os-text: #334155;
            --os-muted: #7b879d;
            --os-line: #e5eaf3;
            --os-accent: #b54708;
            --os-accent-soft: #fff1e8;
            --os-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            color: var(--os-text);
        }

        .owner-show-back {
            color: #6b7280;
            font-weight: 700;
        }

        .owner-show-hero,
        .owner-show-card,
        .owner-show-property-card {
            border: 1px solid var(--os-line);
            border-radius: 18px;
            background: var(--os-surface);
            box-shadow: var(--os-shadow);
        }

        .owner-show-hero {
            overflow: hidden;
        }

        .owner-show-avatar {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--os-accent-soft);
            color: var(--os-accent);
            font-size: 1.55rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .owner-show-title {
            color: var(--os-ink);
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 800;
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .owner-show-subtitle {
            color: var(--os-muted);
            font-size: 0.98rem;
            font-weight: 600;
        }

        .owner-show-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .owner-show-actions .btn {
            border-radius: 10px;
            font-weight: 700;
        }

        .owner-show-stat {
            border: 1px solid var(--os-line);
            border-radius: 14px;
            padding: 16px;
            background: #fbfcfe;
        }

        .owner-show-stat__label {
            color: var(--os-muted);
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .owner-show-stat__value {
            color: var(--os-ink);
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1;
        }

        .owner-show-card__title {
            color: var(--os-ink);
            font-size: 1rem;
            font-weight: 800;
        }

        .owner-show-field {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 13px 0;
            border-bottom: 1px solid #f0f3f8;
        }

        .owner-show-field:last-child {
            border-bottom: 0;
        }

        .owner-show-field__label {
            color: var(--os-muted);
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .owner-show-field__value {
            max-width: 62%;
            color: var(--os-text);
            font-weight: 700;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .owner-show-note {
            color: var(--os-text);
            line-height: 1.55;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .owner-show-property-card {
            padding: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
        }

        .owner-show-property-card__title {
            color: var(--os-ink);
            font-weight: 800;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .owner-show-property-card__meta {
            color: var(--os-muted);
            font-size: 0.84rem;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        @media (max-width: 767.98px) {
            .owner-show-module.py-10 {
                padding-top: 1.25rem !important;
                padding-bottom: 1.25rem !important;
            }

            .owner-show-module .mb-8 {
                margin-bottom: 18px !important;
            }

            .owner-show-hero,
            .owner-show-card,
            .owner-show-property-card {
                border-radius: 8px;
            }

            .owner-show-hero .card-body,
            .owner-show-card .card-body {
                padding: 18px !important;
            }

            .owner-show-header {
                align-items: flex-start !important;
                gap: 12px !important;
            }

            .owner-show-avatar {
                width: 50px;
                height: 50px;
                border-radius: 8px;
                font-size: 1.15rem;
            }

            .owner-show-title {
                font-size: 1.45rem;
                line-height: 1.15;
            }

            .owner-show-subtitle {
                font-size: 0.84rem;
                line-height: 1.35;
            }

            .owner-show-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
                gap: 8px;
                margin-top: 14px;
            }

            .owner-show-actions .btn {
                width: 100%;
                min-width: 0;
                border-radius: 8px;
                padding: 9px 10px;
                font-size: 0.78rem;
                line-height: 1.2;
                white-space: normal;
            }

            .owner-show-stat {
                border-radius: 8px;
                padding: 14px;
            }

            .owner-show-stat__label {
                font-size: 0.66rem;
                line-height: 1.2;
            }

            .owner-show-stat__value {
                font-size: 1.45rem;
            }

            .owner-show-field {
                gap: 12px;
                padding: 11px 0;
            }

            .owner-show-field__label {
                flex: 0 0 92px;
                font-size: 0.66rem;
                line-height: 1.25;
            }

            .owner-show-field__value {
                max-width: none;
                min-width: 0;
                font-size: 0.84rem;
                line-height: 1.35;
            }

            .owner-show-property-card {
                padding: 16px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 property-module owner-show-module">
        <div class="mb-8">
            <a href="{{ route('owners.index') }}" class="owner-show-back text-hover-primary">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a propietarios
            </a>
        </div>

        <div class="card owner-show-hero mb-8">
            <div class="card-body p-8">
                <div class="d-flex flex-wrap justify-content-between gap-5 owner-show-header">
                    <div class="d-flex gap-4 min-w-0">
                        <div class="owner-show-avatar">{{ strtoupper(mb_substr($owner->name, 0, 1)) }}</div>
                        <div class="min-w-0">
                            <h1 class="owner-show-title mb-2">{{ $owner->name }}</h1>
                            <div class="owner-show-subtitle">
                                {{ $owner->owner_type_label ?: 'Propietario' }} - {{ $owner->is_active ? 'Activo' : 'Inactivo' }}
                            </div>
                        </div>
                    </div>

                    <div class="owner-show-actions">
                        <a href="{{ route('owners.edit', $owner) }}" class="btn btn-primary">
                            <i class="ki-outline ki-pencil fs-4 me-1"></i> Editar
                        </a>
                        <a href="{{ route('dossiers.owners.show', $owner) }}" class="btn btn-light-info">
                            Expediente
                        </a>
                    </div>
                </div>

                <div class="row g-4 mt-5">
                    <div class="col-6 col-lg-3">
                        <div class="owner-show-stat h-100">
                            <div class="owner-show-stat__label mb-2">Propiedades</div>
                            <div class="owner-show-stat__value">{{ $owner->properties_count }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="owner-show-stat h-100">
                            <div class="owner-show-stat__label mb-2">Documentos</div>
                            <div class="owner-show-stat__value">{{ $owner->documents_count }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="owner-show-stat h-100">
                            <div class="owner-show-stat__label mb-2">Metodo de pago</div>
                            <div class="fw-bold text-gray-800">{{ $owner->payment_method_label ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-6">
            <div class="col-lg-6">
                <div class="card owner-show-card h-100">
                    <div class="card-body p-7">
                        <div class="owner-show-card__title mb-4">Informacion de contacto</div>
                        <div class="owner-show-field">
                            <div class="owner-show-field__label">Telefono</div>
                            <div class="owner-show-field__value">{{ $owner->phone ?: '-' }}</div>
                        </div>
                        <div class="owner-show-field">
                            <div class="owner-show-field__label">Email</div>
                            <div class="owner-show-field__value">{{ $owner->email ?: '-' }}</div>
                        </div>
                        <div class="owner-show-field">
                            <div class="owner-show-field__label">RFC</div>
                            <div class="owner-show-field__value">{{ $owner->rfc ?: '-' }}</div>
                        </div>
                        <div class="owner-show-field">
                            <div class="owner-show-field__label">CURP</div>
                            <div class="owner-show-field__value">{{ $owner->curp ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card owner-show-card h-100">
                    <div class="card-body p-7">
                        <div class="owner-show-card__title mb-4">Datos bancarios</div>
                        <div class="owner-show-field">
                            <div class="owner-show-field__label">Banco</div>
                            <div class="owner-show-field__value">{{ $owner->bank_name ?: '-' }}</div>
                        </div>
                        <div class="owner-show-field">
                            <div class="owner-show-field__label">CLABE</div>
                            <div class="owner-show-field__value">{{ $owner->clabe ?: '-' }}</div>
                        </div>
                        <div class="owner-show-field">
                            <div class="owner-show-field__label">Titular</div>
                            <div class="owner-show-field__value">{{ $owner->account_holder ?: '-' }}</div>
                        </div>
                        <div class="owner-show-field">
                            <div class="owner-show-field__label">Pago</div>
                            <div class="owner-show-field__value">{{ $owner->payment_method_label ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card owner-show-card h-100">
                    <div class="card-body p-7">
                        <div class="owner-show-card__title mb-4">Domicilio</div>
                        <div class="owner-show-note">{{ $owner->address ?: '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card owner-show-card h-100">
                    <div class="card-body p-7">
                        <div class="owner-show-card__title mb-4">Notas</div>
                        <div class="owner-show-note">{{ $owner->notes ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <h2 class="fw-bold text-dark mb-0 fs-3">Propiedades asociadas</h2>
                <span class="text-muted fw-semibold">{{ $owner->properties_count }} registros</span>
            </div>

            <div class="row g-4">
                @forelse ($owner->properties as $property)
                    <div class="col-lg-6">
                        <div class="owner-show-property-card h-100">
                            <div class="d-flex flex-wrap justify-content-between gap-3">
                                <div class="min-w-0">
                                    <div class="owner-show-property-card__title mb-1">{{ $property->internal_name }}</div>
                                    <div class="owner-show-property-card__meta">
                                        {{ $property->internal_reference ?: 'Sin referencia' }}
                                    </div>
                                </div>
                                <span class="badge {{ $property->status_badge_class }}">{{ $property->status_label }}</span>
                            </div>
                            <div class="owner-show-property-card__meta mt-3">
                                {{ $property->type?->name ?: '-' }} · {{ $property->zone?->name ?: ($property->zone_text ?: '-') }}
                            </div>
                            <div class="owner-show-property-card__meta mt-2">{{ $property->full_address ?: '-' }}</div>
                            <div class="mt-4">
                                <a href="{{ route('properties.show', $property) }}" class="btn btn-sm btn-light-primary fw-bold">
                                    Ver propiedad
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-light-info mb-0">Este propietario aun no tiene propiedades asociadas.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
