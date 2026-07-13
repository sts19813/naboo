@extends('layouts.app')

@section('title', 'Editar Inquilino | SuWork')

@push('styles')
    <style>
        .tenant-edit-shell .tenant-action-bar {
            border-top: 1px dashed var(--bs-gray-300);
            padding-top: 1.5rem;
        }

        .tenant-edit-shell .tenant-form-card {
            border: 0;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .tenant-edit-shell .tenant-edit-heading {
            color: #172033;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.2;
        }

        @media (max-width: 767.98px) {
            .tenant-edit-shell.py-10 {
                padding-top: 1.25rem !important;
                padding-bottom: 1.25rem !important;
            }

            .tenant-edit-shell .tenant-edit-topbar {
                align-items: stretch !important;
                gap: 12px !important;
                margin-bottom: 18px !important;
            }

            .tenant-edit-shell .tenant-edit-topbar > .d-flex {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px !important;
                width: 100%;
            }

            .tenant-edit-shell .tenant-edit-topbar .btn {
                width: 100%;
                min-width: 0;
                border-radius: 8px;
                padding: 9px 10px;
                font-size: 0.78rem;
                line-height: 1.2;
                white-space: normal;
            }

            .tenant-edit-shell .tenant-form-card {
                border-radius: 8px;
            }

            .tenant-edit-shell .tenant-form-card .card-header {
                padding: 18px 18px 0 !important;
            }

            .tenant-edit-shell .tenant-form-card .card-body {
                padding: 14px 18px 8px !important;
            }

            .tenant-edit-shell .tenant-form-card .card-footer {
                padding: 0 18px 18px !important;
            }

            .tenant-edit-shell .tenant-edit-heading {
                font-size: 1.35rem;
            }

            .tenant-edit-shell .tenant-action-bar {
                align-items: stretch !important;
            }

            .tenant-edit-shell .tenant-action-bar > .d-flex {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px !important;
                width: 100%;
            }

            .tenant-edit-shell .tenant-action-bar .btn {
                width: 100%;
                min-width: 0;
                border-radius: 8px;
                padding: 9px 10px;
                white-space: normal;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 property-module tenant-edit-shell">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8 tenant-edit-topbar">
            <a href="{{ route('tenants.index') }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a inquilinos
            </a>

            <div class="d-flex flex-wrap gap-3">
                <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-light">
                    Ver inquilino
                </a>
                <a href="{{ route('dossiers.tenants.show', $tenant) }}" class="btn btn-light-primary">
                    <i class="ki-outline ki-folder fs-4 me-1"></i> Ir a expediente
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-8">
                <div class="fw-bold mb-2">Hay errores en el formulario:</div>
                <ul class="mb-0 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card tenant-form-card">
            <form method="POST" action="{{ route('tenants.update', $tenant) }}">
                @csrf
                @method('PUT')

                <div class="card-header border-0 pt-8">
                    <div class="card-title flex-column align-items-start">
                        <h1 class="tenant-edit-heading mb-1">Editar inquilino</h1>
                        <div class="text-muted fs-7">{{ $tenant->full_name }}</div>
                    </div>
                </div>

                <div class="card-body pt-2 px-8 pb-4">
                    @include('tenants.partials.form-fields', ['tenant' => $tenant])
                </div>

                <div class="card-footer border-0 pt-0 pb-8 px-8">
                    <div class="d-flex flex-wrap justify-content-end align-items-center gap-4 tenant-action-bar">
                        <div class="d-flex gap-3">
                            <a href="{{ route('tenants.index') }}" class="btn btn-light">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ki-outline ki-check fs-4 me-1"></i> Guardar cambios
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
