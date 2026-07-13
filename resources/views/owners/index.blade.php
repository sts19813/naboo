@extends('layouts.app')

@section('title', 'Propietarios | SuWork')

@push('styles')
    <style>
        @media (max-width: 767.98px) {
            .owners-list-module.py-10 {
                padding-top: 1.25rem !important;
                padding-bottom: 1.25rem !important;
            }

            .owners-list-header {
                align-items: stretch !important;
                gap: 14px !important;
                margin-bottom: 18px !important;
            }

            .owners-list-header > div:first-child {
                min-width: 0;
                width: 100%;
            }

            .owners-list-header h1 {
                font-size: 1.35rem;
                line-height: 1.2;
                overflow-wrap: anywhere;
            }

            .owners-list-header h1 + .text-muted {
                font-size: 0.84rem !important;
                line-height: 1.35;
            }

            .owners-list-header .btn {
                width: 100%;
                border-radius: 8px;
                min-height: 44px;
            }

            .owners-list-filter-card {
                margin-bottom: 18px !important;
                border: 0;
                border-radius: 8px;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
            }

            .owners-list-filter-card .card-body {
                padding: 14px !important;
            }

            .owners-list-filter {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px !important;
            }

            .owners-list-filter .form-control {
                grid-column: 1 / -1;
                height: 46px;
                border-radius: 8px;
                font-size: 0.86rem;
            }

            .owners-list-filter .btn {
                min-width: 0;
                border-radius: 8px;
                padding-left: 10px;
                padding-right: 10px;
            }

            .owners-list-grid {
                --bs-gutter-y: 14px;
            }

            .owners-list-grid > [class*="col-"] {
                min-width: 0;
            }

            .owners-list-module .owner-card {
                border-radius: 8px;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
                overflow: hidden;
            }

            .owners-list-module .owner-card .card-body {
                padding: 18px !important;
            }

            .owner-card-header {
                display: grid !important;
                grid-template-columns: 44px minmax(0, 1fr);
                gap: 0 12px;
                margin-bottom: 14px !important;
            }

            .owner-card-identity {
                display: contents !important;
            }

            .owners-list-module .owner-initial {
                grid-column: 1;
                grid-row: 1;
                width: 44px;
                height: 44px;
                flex-shrink: 0;
            }

            .owner-card-title {
                grid-column: 2;
                grid-row: 1;
                min-width: 0;
                padding-top: 2px;
            }

            .owner-card-title .fw-bold {
                font-size: 1rem !important;
                line-height: 1.25;
                overflow-wrap: anywhere;
            }

            .owner-card-title .text-muted {
                font-size: 0.74rem !important;
                overflow-wrap: anywhere;
            }

            .owner-card-actions {
                grid-column: 1 / -1;
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px !important;
                width: 100%;
                margin-top: 14px;
            }

            .owner-card-actions form {
                display: contents;
            }

            .owner-card-actions .btn {
                width: 100%;
                min-width: 0;
                border-radius: 8px;
                padding: 8px 10px;
                font-size: 0.78rem;
                line-height: 1.2;
                white-space: normal;
            }

            .owner-card-contact,
            .owner-card-bank,
            .owner-card-footnote {
                font-size: 0.84rem;
                line-height: 1.35;
                overflow-wrap: anywhere;
            }

            .owner-card-bank {
                display: grid;
                gap: 8px;
            }

            .owner-card-bank > div {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                padding: 8px 0;
                border-bottom: 1px solid #f0f3f8;
            }

            .owner-card-bank > div:last-child {
                border-bottom: 0;
            }

            .owner-card-bank span {
                max-width: 58%;
                text-align: right;
                overflow-wrap: anywhere;
            }

            .owners-list-module .pagination {
                justify-content: center;
                flex-wrap: wrap;
                gap: 6px;
            }

            .owners-list-module .page-link {
                border-radius: 8px !important;
                min-width: 36px;
                text-align: center;
            }

            #createOwnerModal .modal-dialog {
                margin: 0;
                min-height: 100dvh;
            }

            #createOwnerModal .modal-content {
                min-height: 100dvh;
                border-radius: 0;
            }

            #createOwnerModal .modal-header,
            #createOwnerModal .modal-footer {
                padding: 14px 16px;
            }

            #createOwnerModal .modal-body {
                padding: 16px;
            }

            #createOwnerModal .modal-footer {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            #createOwnerModal .modal-footer .btn {
                width: 100%;
                margin: 0;
                border-radius: 8px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $canDeleteOwners = auth()->user()?->can('propietarios.eliminar')
            || auth()->user()?->hasRole('administrador')
            || auth()->user()?->hasRole('admin');
    @endphp

    <div class="py-10 property-module owners-list-module">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8 owners-list-header">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Propietarios</h1>
                <div class="text-muted fs-6">{{ $owners->total() }} propietarios registrados</div>
            </div>
            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createOwnerModal">
                <i class="ki-outline ki-plus fs-4 me-1"></i> Nuevo propietario
            </button>
        </div>

        <div class="card mb-8 owners-list-filter-card">
            <div class="card-body py-6">
                <form method="GET" action="{{ route('owners.index') }}" class="d-flex gap-3 owners-list-filter">
                    <input type="text" name="q" class="form-control"
                        placeholder="Buscar por nombre, telefono, email o RFC..." value="{{ $search }}">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="{{ route('owners.index') }}" class="btn btn-light">Limpiar</a>
                </form>
            </div>
        </div>

        <div class="row g-6 owners-list-grid">
            @forelse ($owners as $owner)
                <div class="col-lg-6">
                    <div class="card owner-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-4 owner-card-header">
                                <div class="d-flex align-items-center gap-4 owner-card-identity">
                                    <div class="owner-initial">{{ strtoupper(mb_substr($owner->name, 0, 1)) }}</div>
                                    <div class="owner-card-title">
                                        <div class="fw-bold fs-3 mb-1">{{ $owner->name }}</div>
                                        <div class="text-muted fs-7">RFC: {{ $owner->rfc ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap owner-card-actions">
                                    <a href="{{ route('owners.show', $owner) }}" class="btn btn-sm btn-light">Ver</a>
                                    <a href="{{ route('owners.edit', $owner) }}" class="btn btn-sm btn-light-primary">Editar</a>
                                    <a href="{{ route('dossiers.owners.show', $owner) }}" class="btn btn-sm btn-light-info">Expediente</a>
                                    @if ($canDeleteOwners)
                                        <form method="POST" action="{{ route('owners.destroy', $owner) }}"
                                            class="js-delete-owner-form"
                                            data-owner-name="{{ $owner->name }}"
                                            data-properties-count="{{ $owner->properties_count }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="owner-card-contact">
                                <div class="text-gray-700 mb-1">{{ $owner->phone }}</div>
                                <div class="text-gray-700 mb-3">{{ $owner->email ?: '-' }}</div>
                            </div>
                            <div class="separator my-3"></div>
                            <div class="owner-card-bank">
                                <div class="text-gray-700">Banco: <span class="fw-semibold">{{ $owner->bank_name ?: '-' }}</span></div>
                                <div class="text-gray-700">CLABE: <span class="fw-semibold">{{ $owner->clabe ?: '-' }}</span></div>
                            </div>
                            <div class="text-muted fs-8 mt-3 owner-card-footnote">{{ $owner->properties_count }} propiedades asociadas</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light-info mb-0">No hay propietarios registrados.</div>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $owners->links() }}
        </div>
    </div>

    <div class="modal fade" id="createOwnerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('owners.store') }}" class="h-100 d-flex flex-column">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Nuevo propietario</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        @include('owners.partials.form-fields')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if ($errors->any())
        <script>
            (() => {
                const createOwnerModal = document.getElementById('createOwnerModal');
                if (!createOwnerModal) {
                    return;
                }
                const modal = new bootstrap.Modal(createOwnerModal);
                modal.show();
            })();
        </script>
    @endif
    <script>
        (() => {
            document.querySelectorAll('.js-delete-owner-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const escapeHtml = (value) => String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                    const ownerName = form.dataset.ownerName || 'este propietario';
                    const propertiesCount = Number.parseInt(form.dataset.propertiesCount || '0', 10);
                    const propertyText = propertiesCount === 1
                        ? '1 propiedad quedará sin este propietario.'
                        : `${propertiesCount} propiedades quedarán sin este propietario.`;
                    const html = [
                        `Se eliminará a <strong>${escapeHtml(ownerName)}</strong>.`,
                        'También se eliminará su expediente de propietario.',
                        propertiesCount > 0 ? propertyText : 'No tiene propiedades asociadas actualmente.',
                    ].join('<br>');

                    let confirmed = false;

                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            title: 'Eliminar propietario',
                            html,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-danger',
                                cancelButton: 'btn btn-light',
                            },
                            reverseButtons: true,
                        });
                        confirmed = !!result.isConfirmed;
                    } else {
                        confirmed = window.confirm(`¿Deseas eliminar a ${ownerName}? ${propertiesCount > 0 ? propertyText : ''}`);
                    }

                    if (confirmed) {
                        form.submit();
                    }
                });
            });
        })();
    </script>
@endpush
