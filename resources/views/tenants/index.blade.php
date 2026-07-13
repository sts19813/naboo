@extends('layouts.app')

@section('title', 'Inquilinos | SuWork')

@push('styles')
    <style>
        .tenant-control-module {
            --tc-surface: #ffffff;
            --tc-ink: #172033;
            --tc-text: #334155;
            --tc-muted: #7b879d;
            --tc-line: #e5eaf3;
            --tc-accent: #b54708;
            --tc-accent-soft: #fff1e8;
            --tc-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            color: var(--tc-text);
        }

        .tenant-control-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .tenant-control-search {
            position: relative;
            min-width: min(100%, 360px);
            flex: 1 1 300px;
        }

        .tenant-control-search i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--tc-muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .tenant-control-search .form-control {
            height: 52px;
            padding-left: 46px;
            border-radius: 16px;
            border: 1px solid var(--tc-line);
            background: #fbfcfe;
            color: var(--tc-ink);
            font-weight: 600;
            box-shadow: none;
        }

        .tenant-control-search .form-control:focus {
            border-color: rgba(181, 71, 8, 0.35);
            box-shadow: 0 0 0 4px rgba(181, 71, 8, 0.08);
        }

        .tenant-control-results {
            color: var(--tc-muted);
            font-size: 1rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .tenant-control-table-card {
            margin-top: 20px;
            border: 1px solid var(--tc-line);
            border-radius: 20px;
            overflow: hidden;
            background: var(--tc-surface);
        }

        .tenant-control-table-card .table-responsive {
            overflow-x: auto;
        }

        .tenant-control-table-card table.dataTable {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0;
        }

        .tenant-control-table-card thead th {
            padding-top: 20px;
            padding-bottom: 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--tc-line) !important;
            color: #94a3b8 !important;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
        }

        .tenant-control-row {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .tenant-control-row td {
            padding-top: 12px;
            padding-bottom: 12px;
            border-top: 1px solid var(--tc-line) !important;
            vertical-align: middle;
            background: #fff;
        }

        .tenant-control-row:hover td {
            background: #fcf8f6;
        }

        .tenant-control-person {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .tenant-control-person__title {
            color: var(--tc-ink);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .tenant-control-person__meta,
        .tenant-control-contact__meta {
            color: var(--tc-muted);
            font-size: 0.88rem;
            margin-top: 4px;
            line-height: 1.4;
        }

        .tenant-control-label {
            color: var(--tc-muted);
            font-size: 0.73rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .tenant-control-value {
            color: var(--tc-ink);
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .tenant-control-actions {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .tenant-control-actions .btn {
            border-radius: 12px;
            font-weight: 700;
            min-width: 76px;
        }

        .tenant-control-table-card .dataTables_info,
        .tenant-control-table-card .dataTables_paginate {
            padding: 18px 28px 0;
            color: var(--tc-muted) !important;
            font-weight: 700;
        }

        .tenant-control-table-card .dataTables_paginate .pagination {
            gap: 6px;
        }

        .tenant-control-table-card .page-link {
            border-radius: 10px !important;
            border-color: var(--tc-line) !important;
            color: var(--tc-text) !important;
            min-width: 38px;
            text-align: center;
            font-weight: 700;
        }

        .tenant-control-table-card .page-item.active .page-link {
            background: var(--tc-accent) !important;
            border-color: var(--tc-accent) !important;
            color: #fff !important;
        }

        @media (max-width: 991px) {
            .tenant-control-table-card .dataTables_info,
            .tenant-control-table-card .dataTables_paginate {
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        @media (max-width: 767.98px) {
            .tenant-control-table-card {
                margin-top: 14px;
                border: 0;
                border-radius: 0;
                overflow: visible;
                background: transparent;
            }

            .tenant-control-table-card .table-responsive {
                overflow: visible;
            }

            .tenant-control-table-card table,
            .tenant-control-table-card table.dataTable,
            .tenant-control-table-card tbody {
                display: block;
                width: 100% !important;
            }

            .tenant-control-table-card thead {
                display: none;
            }

            .tenant-control-table-card tbody {
                display: grid;
                gap: 14px;
            }

            .tenant-control-row {
                display: block;
                padding: 18px;
                border: 1px solid #e8eef7;
                border-radius: 8px;
                background: #fff !important;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
                overflow: hidden;
            }

            .tenant-control-table-card table:not(.table-bordered) tr.tenant-control-row {
                padding: 18px !important;
            }

            .tenant-control-row:hover td {
                background: transparent;
            }

            .tenant-control-row td {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                min-width: 0;
                padding: 10px 0 !important;
                border-top: 1px solid #f0f3f8 !important;
                background: transparent !important;
                text-align: right !important;
            }

            .tenant-control-row td::before {
                flex: 0 0 88px;
                color: #8b96b2;
                font-size: 0.66rem;
                font-weight: 800;
                letter-spacing: 0.04em;
                line-height: 1.25;
                text-align: left;
                text-transform: uppercase;
            }

            .tenant-control-row td:nth-child(1) {
                display: block;
                padding-top: 0 !important;
                padding-bottom: 14px !important;
                border-top: 0 !important;
                text-align: left !important;
            }

            .tenant-control-row td:nth-child(1)::before,
            .tenant-control-row td:nth-child(4)::before {
                content: none;
            }

            .tenant-control-row td:nth-child(2)::before {
                content: 'Contacto';
            }

            .tenant-control-row td:nth-child(3)::before {
                content: 'Pagos';
            }

            .tenant-control-row td:nth-child(4) {
                margin-top: 6px;
                padding-top: 14px !important;
                border-top: 1px solid #e8eef7 !important;
            }

            .tenant-control-person {
                gap: 12px;
            }

            .tenant-control-person > div:last-child {
                min-width: 0;
            }

            .tenant-control-person .owner-initial {
                width: 44px;
                height: 44px;
                flex-shrink: 0;
            }

            .tenant-control-person__title {
                font-size: 1rem;
                line-height: 1.25;
                overflow-wrap: anywhere;
            }

            .tenant-control-person__meta,
            .tenant-control-contact__meta {
                font-size: 0.78rem;
                line-height: 1.35;
                overflow-wrap: anywhere;
            }

            .tenant-control-label {
                display: none;
            }

            .tenant-control-value,
            .tenant-control-contact__meta {
                max-width: 58%;
                min-width: 0;
                font-size: 0.84rem;
                line-height: 1.35;
                overflow-wrap: anywhere;
                text-align: right;
            }

            .tenant-control-row td:nth-child(1) .tenant-control-person__meta {
                max-width: none;
                text-align: left;
            }

            .tenant-control-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                width: 100%;
            }

            .tenant-control-actions .btn {
                width: 100%;
                min-width: 0;
                border-radius: 8px;
                padding: 9px 10px;
                font-size: 0.78rem;
                line-height: 1.2;
                white-space: normal;
            }

            .tenant-control-actions .btn:first-child:last-child,
            .tenant-control-actions .btn:nth-child(3):last-child {
                grid-column: 1 / -1;
            }

            .tenant-control-table-card .dataTables_info {
                padding: 14px 2px 0;
                font-size: 0.78rem;
                text-align: center;
            }

            .tenant-control-table-card .dataTables_paginate {
                padding: 12px 2px 0;
            }

            .tenant-control-table-card .dataTables_paginate .pagination {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 property-module tenant-control-module">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Inquilinos</h1>
                <div class="text-muted fs-6">{{ $tenants->count() }} inquilinos registrados</div>
            </div>
            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createTenantModal">
                <i class="ki-outline ki-plus fs-4 me-1"></i> Nuevo inquilino
            </button>
        </div>

        <div class="tenant-control-toolbar">
            <form method="GET" action="{{ route('tenants.index') }}" id="tenantSearchForm" class="tenant-control-search mb-0">
                <i class="bi bi-search"></i>
                <input type="search" name="q" id="tenantSearchInput" class="form-control"
                    placeholder="Buscar por nombre, email, telefono..." value="{{ $search }}"
                    autocomplete="off" data-tenant-search>
            </form>

            <div id="tenantResultCount" class="tenant-control-results">{{ $tenants->count() }} resultados</div>
        </div>

        <div class="tenant-control-table-card">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle mb-0" id="tenantsTable">
                    <thead>
                        <tr class="fw-bold text-muted text-uppercase fs-8">
                            <th class="ps-7 min-w-280px">Inquilino</th>
                            <th class="min-w-220px">Contacto</th>
                            <th class="min-w-160px">Estatus de pagos</th>
                            <th class="min-w-130px text-end pe-7">Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tenants as $tenant)
                            <tr class="tenant-control-row" data-tenant-row>
                                <td class="ps-7">
                                    <div class="tenant-control-person">
                                        <div class="owner-initial">{{ strtoupper(mb_substr($tenant->full_name, 0, 1)) }}</div>
                                        <div>
                                            <div class="tenant-control-person__title">{{ $tenant->full_name }}</div>
                                            <div class="tenant-control-person__meta">{{ $tenant->employer ?: '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="tenant-control-value">{{ $tenant->phone_primary }}</div>
                                    <div class="tenant-control-contact__meta">{{ $tenant->email ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="tenant-control-label">Pagos</div>
                                    @if ((int) $tenant->total_rent_charges_count > 0)
                                        <div class="tenant-control-value">{{ (int) $tenant->paid_rent_charges_count }}/{{ (int) $tenant->total_rent_charges_count }}</div>
                                        <div class="tenant-control-contact__meta">Pagos completos</div>
                                    @else
                                        <div class="tenant-control-value">-</div>
                                    @endif
                                </td>
                                <td class="text-end pe-7">
                                    <div class="tenant-control-actions">
                                        <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-sm btn-light">Ver</a>
                                        <a href="{{ route('dossiers.tenants.show', $tenant) }}" class="btn btn-sm btn-light-primary">Expediente</a>
                                        <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-sm btn-primary">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" data-empty-row="true" class="text-center py-16 text-muted">No hay inquilinos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createTenantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content tenant-modal-content">
                <form method="POST" action="{{ route('tenants.store') }}" class="h-100 d-flex flex-column">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Nuevo inquilino</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body tenant-modal-body">
                        @include('tenants.partials.form-fields')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear inquilino</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('tenantSearchForm');
            const input = document.getElementById('tenantSearchInput');
            const table = document.getElementById('tenantsTable');
            const resultCount = document.getElementById('tenantResultCount');

            if (!form || !input || !table) {
                return;
            }

            form.addEventListener('submit', (event) => {
                event.preventDefault();
            });

            if (typeof $ === 'undefined' || !$.fn.DataTable || !table.querySelector('[data-tenant-row]')) {
                return;
            }

            const dataTable = $(table).DataTable({
                dom: "rt<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
                pageLength: 10,
                lengthChange: false,
                info: true,
                order: [],
                searching: true,
                autoWidth: false,
                columnDefs: [
                    {
                        targets: [3],
                        orderable: false,
                        searchable: false,
                    },
                ],
                language: {
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ inquilinos',
                    infoEmpty: 'Mostrando 0 a 0 de 0 inquilinos',
                    paginate: {
                        first: 'Primera',
                        last: 'Ultima',
                        next: 'Siguiente',
                        previous: 'Anterior',
                    },
                    emptyTable: 'No hay inquilinos registrados.',
                    zeroRecords: 'No se encontraron coincidencias con este filtro.',
                },
            });

            const syncResultCount = () => {
                if (!resultCount) {
                    return;
                }

                const count = dataTable.rows({ filter: 'applied' }).count();
                resultCount.textContent = `${count} ${count === 1 ? 'resultado' : 'resultados'}`;
            };

            dataTable.search(input.value || '').draw();
            input.addEventListener('input', () => {
                dataTable.search(input.value || '').draw();
                syncResultCount();
            });

            dataTable.on('draw', syncResultCount);
            syncResultCount();
        })();
    </script>

    @if ($errors->any())
        <script>
            (() => {
                const createTenantModal = document.getElementById('createTenantModal');
                if (!createTenantModal) {
                    return;
                }
                const modal = new bootstrap.Modal(createTenantModal);
                modal.show();
            })();
        </script>
    @endif
@endpush
