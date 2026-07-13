@extends('layouts.app')

@section('title', 'Propiedades | SuWork')

@push('styles')
    <style>
        .property-list-module {
            --pl-surface: #ffffff;
            --pl-ink: #172033;
            --pl-text: #334155;
            --pl-muted: #7b879d;
            --pl-line: #e5eaf3;
            --pl-accent: #b54708;
            --pl-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            color: var(--pl-text);
        }

        .property-list-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .property-list-search {
            position: relative;
            min-width: min(100%, 360px);
            flex: 1 1 300px;
        }

        .property-list-search i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--pl-muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .property-list-search .form-control {
            height: 52px;
            padding-left: 46px;
            border-radius: 16px;
            border: 1px solid var(--pl-line);
            background: #fbfcfe;
            color: var(--pl-ink);
            font-weight: 600;
            box-shadow: none;
        }

        .property-list-search .form-control:focus {
            border-color: rgba(181, 71, 8, 0.35);
            box-shadow: 0 0 0 4px rgba(181, 71, 8, 0.08);
        }

        .property-list-results {
            color: var(--pl-muted);
            font-size: 1rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .property-list-table-card {
            margin-top: 20px;
            border: 1px solid var(--pl-line);
            border-radius: 20px;
            overflow: hidden;
            background: var(--pl-surface);
        }

        .property-list-table-card .table-responsive {
            overflow-x: auto;
        }

        .property-list-table-card table.dataTable {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0;
        }

        .property-list-table-card thead th {
            padding-top: 20px;
            padding-bottom: 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--pl-line) !important;
            color: #94a3b8 !important;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
        }

        .property-list-row {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .property-list-row td {
            padding-top: 12px;
            padding-bottom: 12px;
            border-top: 1px solid var(--pl-line) !important;
            vertical-align: middle;
            background: #fff;
        }

        .property-list-row:hover td {
            background: #fcf8f6;
        }

        .property-list-title {
            color: var(--pl-ink);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .property-list-meta {
            color: var(--pl-muted);
            font-size: 0.88rem;
            margin-top: 4px;
            line-height: 1.4;
        }

        .property-list-value {
            color: var(--pl-ink);
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .property-list-actions {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .property-list-actions .btn {
            border-radius: 12px;
            font-weight: 700;
            min-width: 76px;
        }

        .property-list-table-card .dataTables_info,
        .property-list-table-card .dataTables_paginate {
            padding: 18px 28px 0;
            color: var(--pl-muted) !important;
            font-weight: 700;
        }

        .property-list-table-card .dataTables_paginate .pagination {
            gap: 6px;
        }

        .property-list-table-card .page-link {
            border-radius: 10px !important;
            border-color: var(--pl-line) !important;
            color: var(--pl-text) !important;
            min-width: 38px;
            text-align: center;
            font-weight: 700;
        }

        .property-list-table-card .page-item.active .page-link {
            background: var(--pl-accent) !important;
            border-color: var(--pl-accent) !important;
            color: #fff !important;
        }

        @media (max-width: 991px) {
            .property-list-table-card .dataTables_info,
            .property-list-table-card .dataTables_paginate {
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        @media (max-width: 767.98px) {
            .property-list-module {
                --pl-card-radius: 8px;
            }

            .property-list-toolbar {
                gap: 10px;
                margin-bottom: 14px;
            }

            .property-list-search {
                flex-basis: 100%;
                min-width: 0;
            }

            .property-list-search .form-control {
                height: 46px;
                border-radius: 8px;
                font-size: 0.86rem;
            }

            .property-list-results {
                width: 100%;
                font-size: 0.8rem;
                color: var(--pl-muted);
            }

            .property-list-table-card {
                margin-top: 14px;
                border: 0;
                border-radius: 0;
                overflow: visible;
                background: transparent;
            }

            .property-list-table-card .table-responsive {
                overflow: visible;
            }

            .property-list-table-card table,
            .property-list-table-card table.dataTable,
            .property-list-table-card tbody {
                display: block;
                width: 100% !important;
            }

            .property-list-table-card thead {
                display: none;
            }

            .property-list-table-card tbody {
                display: grid;
                gap: 18px;
            }

            .property-list-row {
                display: grid;
                grid-template-columns: 82px minmax(0, 1fr);
                gap: 0 16px;
                padding: 20px;
                border: 1px solid #e8eef7;
                border-radius: 18px;
                background: #fff !important;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
                overflow: hidden;
            }

            .property-list-table-card table:not(.table-bordered) tr.property-list-row {
                padding: 20px !important;
            }

            .property-list-row:hover td {
                background: transparent;
            }

            .property-list-row td {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                min-width: 0;
                padding: 11px 0 !important;
                border-top: 1px solid #f0f3f8 !important;
                background: transparent;
            }

            .property-list-row td::before {
                content: attr(data-mobile-label);
                flex: 0 0 96px;
                color: #8b96b2;
                font-size: 0.66rem;
                font-weight: 800;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .property-list-row td:nth-child(1),
            .property-list-row td:nth-child(2),
            .property-list-row td:nth-child(10) {
                border-top: 0 !important;
            }

            .property-list-row td:nth-child(1)::before,
            .property-list-row td:nth-child(2)::before,
            .property-list-row td:nth-child(10)::before {
                content: none;
            }

            .property-list-row td:nth-child(1) {
                grid-column: 1;
                grid-row: 1;
                align-self: start;
                padding: 0 !important;
            }

            .property-list-row td:nth-child(2) {
                grid-column: 2;
                grid-row: 1;
                align-items: flex-start;
                justify-content: flex-start;
                padding: 4px 0 14px !important;
            }

            .property-list-row td:nth-child(3) {
                margin-top: 16px;
            }

            .property-list-row td:nth-child(n+3):nth-child(-n+9),
            .property-list-row td:nth-child(10) {
                grid-column: 1 / -1;
            }

            .property-list-row td:nth-child(3)::before { content: 'Tipo'; }
            .property-list-row td:nth-child(4)::before { content: 'Zona'; }
            .property-list-row td:nth-child(5)::before { content: 'Estado'; }
            .property-list-row td:nth-child(6)::before { content: 'Inquilino'; }
            .property-list-row td:nth-child(7)::before { content: 'Asesor'; }
            .property-list-row td:nth-child(8)::before { content: 'Contrato'; }
            .property-list-row td:nth-child(9)::before { content: 'Incidencias'; }

            .property-list-row .property-thumb {
                width: 82px;
                height: 82px;
                border-radius: 12px;
            }

            .property-list-title {
                display: block;
                font-size: 1rem;
                line-height: 1.25;
            }

            .property-list-value,
            .property-list-row td > .text-muted,
            .property-list-row td > .text-danger {
                min-width: 0;
                max-width: 58%;
                text-align: right;
                font-size: 0.84rem;
                line-height: 1.35;
                overflow-wrap: anywhere;
            }

            .property-list-row td:nth-child(7) > .d-flex,
            .property-list-row td:nth-child(7) > [data-property-advisor-action] {
                max-width: 62%;
                justify-content: flex-end;
            }

            .property-list-row .maintenance-provider-trigger {
                max-width: 100%;
                justify-content: flex-end;
                text-align: left;
            }

            .property-list-row .maintenance-provider-trigger .min-w-0 {
                max-width: 150px;
            }

            .property-list-row td:nth-child(5) .badge {
                white-space: normal;
                text-align: right;
            }

            .property-list-row td:nth-child(10) {
                margin-top: 8px;
                padding-top: 16px !important;
                border-top: 1px solid #e8eef7 !important;
            }

            .property-list-actions {
                width: 100%;
                justify-content: stretch;
            }

            .property-list-actions .btn {
                flex: 1 1 auto;
                min-width: 0;
                border-radius: 8px;
            }

            .property-list-table-card .dataTables_info {
                padding: 14px 2px 0;
                font-size: 0.78rem;
                text-align: center;
            }

            .property-list-table-card .dataTables_paginate {
                padding: 12px 2px 0;
            }

            .property-list-table-card .dataTables_paginate .pagination {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 property-module property-list-module">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Propiedades</h1>
                <div class="text-muted fs-6">{{ $properties->count() }} propiedades encontradas</div>
            </div>
            <a href="{{ route('properties.create') }}" class="btn btn-primary fw-bold">
                <i class="ki-outline ki-plus fs-4 me-1"></i> Nueva Propiedad
            </a>
        </div>

        <div class="property-list-toolbar">
            <form method="GET" action="{{ route('properties.index') }}"
                id="propertySearchForm" class="property-list-search mb-0">
                <i class="bi bi-search"></i>
                <input
                    id="properties_text_search"
                    type="search"
                    class="form-control"
                    placeholder="Buscar por nombre, tipo, zona, estado, inquilino, asesor..."
                    autocomplete="off">
            </form>

            <div id="propertyResultCount" class="property-list-results">{{ $properties->count() }} resultados</div>
        </div>

        <div class="property-list-table-card">
            <div class="table-responsive">
                <table id="properties_table" class="table table-row-bordered align-middle mb-0">
                    <thead>
                        <tr class="fw-bold text-muted text-uppercase fs-8">
                            <th class="ps-7 min-w-125px">Foto</th>
                            <th class="min-w-220px">Nombre interno</th>
                            <th class="min-w-140px">Tipo</th>
                            <th class="min-w-140px">Zona</th>
                            <th class="min-w-130px">Estado</th>
                            <th class="min-w-150px">Inquilino</th>
                            <th class="min-w-180px">Asesores responsables</th>
                            <th class="min-w-130px">Contrato vence</th>
                            <th class="min-w-110px">Incidencias</th>
                            <th class="text-end pe-7">Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($properties as $property)
                            @php
                                $photoUrl = $property->facade_photo_path
                                    ? \Illuminate\Support\Facades\Storage::url($property->facade_photo_path)
                                    : asset('metronic/assets/media/svg/files/blank-image.svg');
                                $assignedAdvisorIds = $property->advisors->pluck('id')
                                    ->push($property->advisor_user_id)
                                    ->filter()
                                    ->unique()
                                    ->values();
                                $assignedAdvisors = $availableAdvisors->whereIn('id', $assignedAdvisorIds);
                                $assignedAdvisorNames = $assignedAdvisors->pluck('name')->implode(' ');
                                $primaryAdvisor = $assignedAdvisors->first();
                                $primaryAdvisorInitials = $primaryAdvisor
                                    ? collect(explode(' ', trim((string) $primaryAdvisor->name)))
                                        ->filter()
                                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                        ->take(2)
                                        ->implode('')
                                    : '';
                            @endphp
                            <tr class="property-list-row" data-property-row>
                                <td class="ps-7">
                                    <img
                                        src="{{ $photoUrl }}"
                                        class="property-thumb"
                                        alt="{{ $property->internal_name }}"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </td>
                                <td>
                                    <a href="{{ route('properties.show', $property) }}"
                                        class="property-list-title text-hover-primary">
                                        {{ $property->internal_name }}
                                    </a>
                                </td>
                                <td>
                                    <div class="property-list-value">{{ $property->type?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    @php
                                        $zoneName = $property->zone?->name;
                                        $zoneText = filled($property->zone_text) ? trim((string) $property->zone_text) : null;
                                        $zoneDisplay = $zoneName ?: $zoneText ?: '-';
                                        $showZoneTextDetail = $zoneName && $zoneText && strcasecmp($zoneName, $zoneText) !== 0;
                                    @endphp
                                    <div class="property-list-value">{{ $zoneDisplay }}</div>
                                    @if ($showZoneTextDetail)
                                        <div class="property-list-meta">{{ $zoneText }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $property->status_badge_class }}">{{ $property->status_label }}</span>
                                </td>
                                <td>
                                    <div class="property-list-value">{{ $property->tenant?->full_name ?: ($property->current_tenant_name ?: '-') }}</div>
                                </td>
                                    <td data-search="{{ $assignedAdvisorNames ?: 'Sin asesor' }}">
                                        @if ($canManagePropertyAdvisors)
                                            <span class="dropup dropdown maintenance-inline-dropdown maintenance-provider-dropdown" data-property-advisor-action>
                                                <button class="maintenance-provider-trigger dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                    aria-label="Cambiar asesor responsable de {{ $property->internal_name }}">
                                                    @if ($primaryAdvisor)
                                                        <span class="maintenance-avatar">{{ $primaryAdvisorInitials ?: 'A' }}</span>
                                                        <span class="min-w-0">
                                                            <span class="maintenance-cell-title">{{ $primaryAdvisor->name }}</span>
                                                            <span class="maintenance-cell-subtitle">
                                                                {{ $primaryAdvisor->email ?: 'Asesor responsable' }}
                                                                @if ($assignedAdvisors->count() > 1)
                                                                    · +{{ $assignedAdvisors->count() - 1 }}
                                                                @endif
                                                            </span>
                                                        </span>
                                                    @else
                                                        <span class="maintenance-cell-icon"><i class="bi bi-person-plus"></i></span>
                                                        <span class="maintenance-cell-title text-warning">Sin asesor</span>
                                                    @endif
                                                </button>
                                                <div class="dropdown-menu maintenance-inline-menu maintenance-provider-menu">
                                                    <form method="POST" action="{{ route('properties.update.advisors', $property) }}" class="js-property-advisors-inline-form" data-no-ajax>
                                                        @csrf
                                                        @method('PUT')
                                                        <button class="dropdown-item maintenance-provider-option {{ $assignedAdvisors->isEmpty() ? 'active' : '' }}"
                                                            type="submit" {{ $assignedAdvisors->isEmpty() ? 'disabled' : '' }}
                                                            data-property-name="{{ $property->internal_name }}"
                                                            data-advisor-name="Sin asesor">
                                                            <span class="maintenance-cell-icon"><i class="bi bi-person-dash"></i></span>
                                                            <span class="min-w-0">
                                                                <span class="maintenance-cell-title">Sin asesor</span>
                                                                <span class="maintenance-cell-subtitle">Quitar asesor actual</span>
                                                            </span>
                                                        </button>
                                                    </form>
                                                    @foreach ($availableAdvisors as $advisor)
                                                        @php
                                                            $advisorInitials = collect(explode(' ', trim((string) $advisor->name)))
                                                                ->filter()
                                                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                                                ->take(2)
                                                                ->implode('');
                                                        @endphp
                                                        <form method="POST" action="{{ route('properties.update.advisors', $property) }}" class="js-property-advisors-inline-form" data-no-ajax>
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="advisor_user_ids[]" value="{{ $advisor->id }}">
                                                            <button class="dropdown-item maintenance-provider-option {{ $assignedAdvisorIds->contains($advisor->id) && $assignedAdvisorIds->count() === 1 ? 'active' : '' }}"
                                                                type="submit" {{ $assignedAdvisorIds->contains($advisor->id) && $assignedAdvisorIds->count() === 1 ? 'disabled' : '' }}
                                                                data-property-name="{{ $property->internal_name }}"
                                                                data-advisor-name="{{ $advisor->name }}">
                                                                <span class="maintenance-avatar">{{ $advisorInitials ?: 'A' }}</span>
                                                                <span class="min-w-0">
                                                                    <span class="maintenance-cell-title">{{ $advisor->name }}</span>
                                                                    <span class="maintenance-cell-subtitle">{{ $advisor->email ?: 'Asesor' }}</span>
                                                                </span>
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            </span>
                                        @else
                                            <div class="d-flex flex-wrap gap-1">
                                                @forelse ($assignedAdvisors as $advisor)
                                                    <span class="badge badge-light-primary">{{ $advisor->name }}</span>
                                                @empty
                                                    <span class="text-muted">Sin asesor</span>
                                                @endforelse
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="property-list-value">{{ $property->contract_expires_at ? $property->contract_expires_at->format('d/m/Y') : '-' }}</div>
                                    </td>
                                    <td>
                                        @if ($property->incidents_count > 0)
                                            <span class="text-danger fw-bold">
                                                <i class="ki-outline ki-information-5 text-danger fs-6"></i>
                                                {{ $property->incidents_count }}
                                            </span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-7">
                                        <div class="property-list-actions">
                                            <a href="{{ route('properties.show', $property) }}"
                                                class="btn btn-sm btn-light-primary">
                                                Ver
                                            </a>

                                            {{--

                                            <a href="{{ route('dossiers.properties.show', $property) }}"
                                                class="btn btn-xs btn-light-info">
                                                Expediente
                                            </a>
                                            <a href="{{ route('inventory-checks.index', $property) }}"
                                                class="btn btn-xs btn-light-warning">
                                                Inventario
                                            </a>
                                            @if ($property->tenant_id)
                                                <a href="{{ route('charges.index', ['property' => $property->uuid]) }}"
                                                    class="btn btn-xs btn-light-success">
                                                    Cobranza
                                                </a>
                                            @endif
                                            <a href="{{ route('properties.edit', $property) }}" class="btn btn-xs btn-primary">
                                                Editar
                                            </a>
                                            --}}
                                            
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-16 text-muted" data-empty-row="true">
                                        Aún no hay propiedades registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('propertySearchForm');
            const tableElement = document.getElementById('properties_table');
            const textSearchInput = document.getElementById('properties_text_search');
            const resultCount = document.getElementById('propertyResultCount');

            form?.addEventListener('submit', (event) => {
                event.preventDefault();
            });

            if (!tableElement || typeof $ === 'undefined' || !$.fn.DataTable) {
                return;
            }

            const emptyCell = tableElement.querySelector('td[data-empty-row="true"]');
            if (emptyCell) {
                emptyCell.closest('tr')?.remove();
            }

            const dataTable = $(tableElement).DataTable({
                dom: "rt<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
                pageLength: 25,
                lengthChange: false,
                order: [],
                info: true,
                searching: true,
                autoWidth: false,
                language: {
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ propiedades',
                    infoEmpty: 'Mostrando 0 a 0 de 0 propiedades',
                    paginate: {
                        first: 'Primera',
                        last: 'Ultima',
                        next: 'Siguiente',
                        previous: 'Anterior',
                    },
                    emptyTable: 'Aún no hay propiedades registradas.',
                    zeroRecords: 'No se encontraron coincidencias con este filtro.',
                },
                columnDefs: [
                    {
                        targets: [0, 9],
                        orderable: false,
                        searchable: false,
                    },
                ],
            });

            const syncResultCount = () => {
                if (!resultCount) {
                    return;
                }

                const count = dataTable.rows({ filter: 'applied' }).count();
                resultCount.textContent = `${count} ${count === 1 ? 'resultado' : 'resultados'}`;
            };

            if (textSearchInput) {
                textSearchInput.addEventListener('input', (event) => {
                    dataTable.search(event.target.value || '').draw();
                    syncResultCount();
                });
            }

            dataTable.on('draw', syncResultCount);
            syncResultCount();

            if (window.bootstrap?.Dropdown) {
                const openAdvisorMenus = new Set();
                const positionAdvisorMenu = (trigger, menu) => {
                    const margin = 12;
                    const triggerRect = trigger.getBoundingClientRect();
                    const menuWidth = menu.offsetWidth;
                    const menuHeight = menu.offsetHeight;
                    const left = Math.min(
                        Math.max(triggerRect.left, margin),
                        Math.max(margin, window.innerWidth - menuWidth - margin),
                    );
                    const top = Math.max(margin, triggerRect.top - menuHeight - margin);

                    menu.style.position = 'fixed';
                    menu.style.inset = 'auto auto auto auto';
                    menu.style.left = `${left}px`;
                    menu.style.top = `${top}px`;
                    menu.style.transform = 'none';
                };
                const repositionOpenAdvisorMenus = () => {
                    openAdvisorMenus.forEach((trigger) => {
                        const menu = trigger.__propertyAdvisorMenu;

                        if (menu?.classList.contains('show')) {
                            positionAdvisorMenu(trigger, menu);
                        }
                    });
                };

                tableElement.querySelectorAll('[data-property-advisor-action] [data-bs-toggle="dropdown"]').forEach((trigger) => {
                    const dropdown = trigger.closest('[data-property-advisor-action]');
                    const menu = dropdown?.querySelector('.dropdown-menu');

                    if (!dropdown || !menu) {
                        return;
                    }

                    const originalParent = menu.parentElement;
                    const originalNextSibling = menu.nextSibling;

                    trigger.__propertyAdvisorMenu = menu;
                    window.bootstrap.Dropdown.getOrCreateInstance(trigger, {
                        display: 'static',
                    });

                    trigger.addEventListener('show.bs.dropdown', () => {
                        document.body.appendChild(menu);
                        menu.classList.add('property-advisor-menu-portal');
                        openAdvisorMenus.add(trigger);
                    });

                    trigger.addEventListener('shown.bs.dropdown', () => {
                        positionAdvisorMenu(trigger, menu);
                    });

                    trigger.addEventListener('hidden.bs.dropdown', () => {
                        openAdvisorMenus.delete(trigger);
                        menu.classList.remove('property-advisor-menu-portal');
                        menu.removeAttribute('style');

                        if (originalNextSibling?.parentElement === originalParent) {
                            originalParent.insertBefore(menu, originalNextSibling);
                        } else {
                            originalParent.appendChild(menu);
                        }
                    });
                });

                window.addEventListener('resize', repositionOpenAdvisorMenus);
                window.addEventListener('scroll', repositionOpenAdvisorMenus, true);
            }

            document.querySelectorAll('.js-property-advisors-inline-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const submitButton = form.querySelector('[type="submit"]');
                    if (submitButton?.disabled) {
                        return;
                    }

                    const propertyName = submitButton?.dataset.propertyName || 'esta propiedad';
                    const advisorName = submitButton?.dataset.advisorName || 'Sin asesor';
                    const message = advisorName !== 'Sin asesor'
                        ? `${propertyName} quedará asignada a ${advisorName}.`
                        : 'La propiedad quedará sin asesores responsables.';

                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            icon: 'question',
                            title: '¿Guardar asignación?',
                            text: message,
                            showCancelButton: true,
                            confirmButtonText: 'Sí, guardar',
                            cancelButtonText: 'Revisar',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-primary',
                                cancelButton: 'btn btn-light',
                            },
                        });

                        if (!result.isConfirmed) {
                            return;
                        }
                    } else if (!window.confirm(message)) {
                        return;
                    }

                    if (submitButton) {
                        submitButton.disabled = true;
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                            credentials: 'same-origin',
                        });
                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok || payload.success === false) {
                            const firstError = Object.values(payload.errors || {}).flat()[0];
                            window.SuWorkToast?.fire('danger', firstError || payload.message || 'No fue posible guardar la asignación.');
                            return;
                        }

                        window.SuWorkToast?.fire('success', payload.message || 'Asignación guardada.');
                        setTimeout(() => window.location.reload(), 450);
                    } catch (error) {
                        window.SuWorkToast?.fire('danger', error.message || 'No fue posible guardar la asignación.');
                    } finally {
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    }
                });
            });
        })();
    </script>
@endpush
