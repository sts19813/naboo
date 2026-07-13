@extends('layouts.app')

@section('title', 'Documentos | SuWork')

@section('content')
    @php
        $activeView = $filters['view'] ?? 'all';
        $storagePercentage = min(100, $dossierStorage['percentage'] ?? 0);

        $fileIcon = function (?string $name): array {
            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

            return match ($extension) {
                'zip' => ['ki-archive', 'text-warning', 'bg-light-warning'],
                'jpg', 'jpeg', 'png', 'webp', 'gif' => ['ki-picture', 'text-success', 'bg-light-success'],
                default => ['ki-document', 'text-danger', 'bg-light-danger'],
            };
        };

        $activeDocuments = $activeView === 'expired' ? $expiredDocuments : $currentDocuments;
    @endphp

    @push('styles')
        <style>
            .documents-index {
                --dl-surface: #ffffff;
                --dl-ink: #172033;
                --dl-text: #334155;
                --dl-muted: #7b879d;
                --dl-line: #e5eaf3;
                --dl-accent: #b54708;
                --dl-accent-soft: #fff1e8;
                --dl-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
                color: var(--dl-text);
            }

            .documents-index .documents-kpi-card {
                border: 1px solid var(--dl-line);
                border-radius: 18px;
                box-shadow: var(--dl-shadow);
            }

            .documents-index .documents-file-icon {
                width: 44px;
                height: 44px;
                flex: 0 0 44px;
            }

            .documents-index .documents-file-open {
                text-decoration: none;
                transition: transform 0.2s ease, opacity 0.2s ease;
            }

            .documents-index .documents-file-open:hover {
                opacity: 0.86;
                transform: translateY(-1px);
            }

            .documents-list-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                margin-bottom: 20px;
            }

            .documents-list-search {
                position: relative;
                min-width: min(100%, 360px);
                flex: 1 1 300px;
            }

            .documents-list-search i {
                position: absolute;
                left: 16px;
                top: 50%;
                transform: translateY(-50%);
                color: var(--dl-muted);
                font-size: 1rem;
                pointer-events: none;
            }

            .documents-list-search .form-control {
                height: 52px;
                padding-left: 46px;
                border-radius: 16px;
                border: 1px solid var(--dl-line);
                background: #fbfcfe;
                color: var(--dl-ink);
                font-weight: 600;
                box-shadow: none;
            }

            .documents-list-search .form-control:focus {
                border-color: rgba(181, 71, 8, 0.35);
                box-shadow: 0 0 0 4px rgba(181, 71, 8, 0.08);
            }

            .documents-list-results {
                color: var(--dl-muted);
                font-size: 1rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .documents-list-tabs {
                gap: 12px;
                margin-bottom: 20px;
            }

            .documents-list-tabs .nav-link {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                border: 1px solid transparent;
                border-radius: 14px;
                padding: 12px 18px;
                background: #f8fafc;
                color: var(--dl-text);
                font-weight: 800;
            }

            .documents-list-tabs .nav-link:hover {
                background: var(--dl-accent-soft);
                color: var(--dl-accent);
                border-color: rgba(181, 71, 8, 0.15);
            }

            .documents-list-tabs .nav-link.active {
                background: var(--dl-accent);
                color: #fff !important;
                box-shadow: 0 12px 28px rgba(181, 71, 8, 0.22);
            }

            .documents-list-tabs__count {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 26px;
                height: 26px;
                border-radius: 999px;
                padding: 0 8px;
                background: rgba(15, 23, 42, 0.08);
                color: inherit;
                font-size: 12px;
                font-weight: 800;
            }

            .documents-list-tabs .nav-link.active .documents-list-tabs__count {
                background: rgba(255, 255, 255, 0.18);
            }

            .documents-list-table-card {
                margin-top: 20px;
                border: 1px solid var(--dl-line);
                border-radius: 20px;
                overflow: hidden;
                background: var(--dl-surface);
            }

            .documents-list-table-card .table-responsive {
                overflow-x: auto;
            }

            .documents-list-table-card table.dataTable {
                margin-top: 0 !important;
                margin-bottom: 0 !important;
                border-collapse: separate !important;
                border-spacing: 0;
            }

            .documents-list-table-card thead th {
                padding-top: 20px;
                padding-bottom: 20px;
                background: #f8fafc;
                border-bottom: 1px solid var(--dl-line) !important;
                color: #94a3b8 !important;
                font-size: 0.76rem;
                letter-spacing: 0.08em;
            }

            .documents-list-row td {
                padding-top: 12px;
                padding-bottom: 12px;
                border-top: 1px solid var(--dl-line) !important;
                vertical-align: middle;
                background: #fff;
            }

            .documents-list-row:hover td {
                background: #fcf8f6;
            }

            .documents-list-title {
                color: var(--dl-ink);
                font-size: 1rem;
                font-weight: 800;
                line-height: 1.25;
            }

            .documents-list-meta {
                color: var(--dl-muted);
                font-size: 0.88rem;
                margin-top: 4px;
                line-height: 1.4;
            }

            .documents-list-value {
                color: var(--dl-ink);
                font-size: 0.95rem;
                font-weight: 700;
                line-height: 1.35;
            }

            .documents-list-actions {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                flex-wrap: nowrap;
                justify-content: flex-end;
            }

            .documents-list-actions .btn {
                border-radius: 12px;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding-left: 12px;
                padding-right: 12px;
                white-space: nowrap;
            }

            .documents-list-table-card .dataTables_info,
            .documents-list-table-card .dataTables_paginate {
                padding: 18px 28px 0;
                color: var(--dl-muted) !important;
                font-weight: 700;
            }

            .documents-list-table-card .dataTables_paginate .pagination {
                gap: 6px;
            }

            .documents-list-table-card .page-link {
                border-radius: 10px !important;
                border-color: var(--dl-line) !important;
                color: var(--dl-text) !important;
                min-width: 38px;
                text-align: center;
                font-weight: 700;
            }

            .documents-list-table-card .page-item.active .page-link {
                background: var(--dl-accent) !important;
                border-color: var(--dl-accent) !important;
                color: #fff !important;
            }

            @media (max-width: 991px) {
                .documents-list-table-card .dataTables_info,
                .documents-list-table-card .dataTables_paginate {
                    padding-left: 16px;
                    padding-right: 16px;
                }
            }

            @media (max-width: 767.98px) {
                .documents-index.py-10 {
                    padding-top: 1.25rem !important;
                    padding-bottom: 1.25rem !important;
                }

                .documents-index > .d-flex.flex-wrap.justify-content-between {
                    align-items: flex-start !important;
                    gap: 12px !important;
                    margin-bottom: 18px !important;
                }

                .documents-index > .d-flex.flex-wrap.justify-content-between h1 {
                    font-size: 1.35rem;
                    line-height: 1.2;
                }

                .documents-index > .d-flex.flex-wrap.justify-content-between h1 + .text-muted {
                    font-size: 0.84rem !important;
                    line-height: 1.35;
                }

                .documents-kpi-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 10px !important;
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                    margin-bottom: 18px !important;
                }

                .documents-kpi-grid > [class*="col-"] {
                    width: auto;
                    max-width: none;
                    padding: 0 !important;
                }

                .documents-index .documents-kpi-card {
                    min-width: 0;
                    border-radius: 8px;
                    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
                }

                .documents-index .documents-kpi-card .card-body {
                    min-width: 0;
                    padding: 14px !important;
                }

                .documents-index .documents-kpi-card .text-muted {
                    min-height: 1.6rem;
                    font-size: 0.68rem;
                    font-weight: 800 !important;
                    letter-spacing: 0.03em;
                    line-height: 1.15;
                    text-transform: uppercase;
                }

                .documents-index .documents-kpi-card .fs-1 {
                    font-size: clamp(1.45rem, 8vw, 2rem) !important;
                    line-height: 1.05;
                    overflow-wrap: anywhere;
                }

                .documents-list-toolbar {
                    gap: 10px;
                    margin-bottom: 14px;
                }

                .documents-list-search {
                    flex-basis: 100%;
                    min-width: 0;
                }

                .documents-list-search .form-control {
                    height: 46px;
                    border-radius: 8px;
                    font-size: 0.86rem;
                }

                .documents-list-results {
                    width: 100%;
                    font-size: 0.8rem;
                    white-space: normal;
                }

                .documents-list-tabs {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 8px;
                    width: 100%;
                    margin-bottom: 0;
                }

                .documents-list-tabs .nav-item,
                .documents-list-tabs .nav-link {
                    width: 100%;
                }

                .documents-list-tabs .nav-link {
                    justify-content: center;
                    min-height: 44px;
                    border-radius: 8px;
                    padding: 9px 10px;
                    font-size: 0.78rem;
                    line-height: 1.2;
                    text-align: center;
                    white-space: normal;
                }

                .documents-list-tabs__count {
                    min-width: 24px;
                    height: 24px;
                    font-size: 0.7rem;
                }

                .documents-storage-widget {
                    width: 100%;
                    min-width: 0 !important;
                    padding: 12px;
                    border: 1px solid #e8eef7;
                    border-radius: 8px;
                    background: #fff;
                }

                .documents-list-table-card {
                    margin-top: 14px;
                    border: 0;
                    border-radius: 0;
                    overflow: visible;
                    background: transparent;
                }

                .documents-list-table-card .table-responsive {
                    overflow: visible;
                }

                .documents-list-table-card table,
                .documents-list-table-card table.dataTable,
                .documents-list-table-card tbody {
                    display: block;
                    width: 100% !important;
                }

                .documents-list-table-card thead {
                    display: none;
                }

                .documents-list-table-card tbody {
                    display: grid;
                    gap: 14px;
                }

                .documents-list-row {
                    display: block;
                    padding: 18px;
                    border: 1px solid #e8eef7;
                    border-radius: 8px;
                    background: #fff !important;
                    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
                    overflow: hidden;
                }

                .documents-list-table-card table:not(.table-bordered) tr.documents-list-row {
                    padding: 18px !important;
                }

                .documents-list-row:hover td {
                    background: transparent;
                }

                .documents-list-row td {
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

                .documents-list-row td::before {
                    flex: 0 0 82px;
                    color: #8b96b2;
                    font-size: 0.66rem;
                    font-weight: 800;
                    letter-spacing: 0.04em;
                    line-height: 1.25;
                    text-align: left;
                    text-transform: uppercase;
                }

                .documents-list-row td:nth-child(1) {
                    display: block;
                    padding-top: 0 !important;
                    padding-bottom: 14px !important;
                    border-top: 0 !important;
                    text-align: left !important;
                }

                .documents-list-row td:nth-child(1)::before,
                .documents-list-row td:nth-child(5)::before {
                    content: none;
                }

                .documents-list-row td:nth-child(2)::before {
                    content: 'Archivo';
                }

                .documents-list-row td:nth-child(3)::before {
                    content: 'Vence';
                }

                .documents-list-row td:nth-child(4)::before {
                    content: 'Fecha';
                }

                .documents-list-row td:nth-child(5) {
                    margin-top: 6px;
                    padding-top: 14px !important;
                    border-top: 1px solid #e8eef7 !important;
                }

                .documents-list-title {
                    font-size: 1rem;
                    line-height: 1.25;
                    overflow-wrap: anywhere;
                }

                .documents-list-meta {
                    font-size: 0.78rem;
                    line-height: 1.35;
                    overflow-wrap: anywhere;
                }

                .documents-list-value,
                .documents-list-row td:nth-child(3) > span {
                    max-width: 58%;
                    min-width: 0;
                    font-size: 0.84rem;
                    line-height: 1.35;
                    overflow-wrap: anywhere;
                    text-align: right;
                    white-space: normal;
                }

                .documents-list-row td:nth-child(2) > .d-flex {
                    max-width: 62%;
                    min-width: 0;
                    justify-content: flex-end;
                }

                .documents-index .documents-file-icon {
                    width: 38px;
                    height: 38px;
                    flex-basis: 38px;
                }

                .documents-list-row td:nth-child(2) .documents-file-name {
                    display: block;
                    max-width: 170px;
                    text-align: right;
                    overflow-wrap: anywhere;
                }

                .documents-list-actions {
                    display: grid;
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    gap: 8px;
                    width: 100%;
                }

                .documents-list-actions .btn {
                    width: 100%;
                    min-width: 0;
                    border-radius: 8px;
                    justify-content: center;
                    padding: 8px 8px;
                    font-size: 0.72rem;
                    line-height: 1.2;
                    white-space: normal;
                }

                .documents-list-table-card .dataTables_info {
                    padding: 14px 2px 0;
                    font-size: 0.78rem;
                    text-align: center;
                }

                .documents-list-table-card .dataTables_paginate {
                    padding: 12px 2px 0;
                }

                .documents-list-table-card .dataTables_paginate .pagination {
                    justify-content: center;
                    flex-wrap: wrap;
                }
            }
        </style>
    @endpush

    <div class="py-10 property-module documents-index">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Documentos</h1>
                <div class="text-muted fs-6">{{ $stats['total'] }} documentos encontrados</div>
            </div>
            <div class="d-flex gap-2">
                @canany(['expedientes.ver_bitacora_eliminados', 'expedientes.eliminar_archivos'])
                    <a href="{{ route('documents.deleted-files-log') }}" class="btn btn-icon btn-light-danger" title="Bitacora eliminados">
                        <i class="ki-outline ki-archive fs-2"></i>
                    </a>
                @endcanany
                <a href="{{ route('settings.dossiers.index') }}" class="btn btn-icon btn-light-primary" title="Configurar expedientes">
                    <i class="ki-outline ki-setting-2 fs-2"></i>
                </a>
            </div>
        </div>

        <div class="row g-5 mb-8 documents-kpi-grid">
            <div class="col-md-6 col-xl-3">
                <div class="card documents-kpi-card">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Total existentes</div>
                        <div class="fs-1 fw-bold text-gray-900">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card documents-kpi-card">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Propiedades</div>
                        <div class="fs-1 fw-bold text-primary">{{ $stats['properties'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card documents-kpi-card">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Inquilinos</div>
                        <div class="fs-1 fw-bold text-info">{{ $stats['tenants'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <a href="{{ route('documents.expired') }}" class="card documents-kpi-card text-decoration-none">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Vencidos</div>
                        <div class="fs-1 fw-bold text-warning">{{ $stats['expired'] }}</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="documents-list-toolbar">
            <form method="GET" action="{{ $activeView === 'expired' ? route('documents.expired') : route('documents.index') }}"
                id="documentsSearchForm" class="documents-list-search mb-0">
                <i class="bi bi-search"></i>
                <input
                    id="documentsSearchInput"
                    type="search"
                    class="form-control"
                    placeholder="Buscar documento, expediente o archivo..."
                    autocomplete="off">
            </form>

            <div id="documentsResultCount" class="documents-list-results">{{ $activeDocuments->count() }} resultados</div>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 mb-4">
            <ul class="nav documents-list-tabs mb-0" id="documentsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeView === 'expired' ? '' : 'active' }}" id="current-documents-tab"
                        data-bs-toggle="tab" data-bs-target="#current-documents-pane" type="button" role="tab"
                        aria-controls="current-documents-pane" aria-selected="{{ $activeView === 'expired' ? 'false' : 'true' }}"
                        data-documents-tab="current">
                        <span>Documentos actuales</span>
                        <span class="documents-list-tabs__count">{{ $currentDocuments->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeView === 'expired' ? 'active' : '' }}" id="expired-documents-tab"
                        data-bs-toggle="tab" data-bs-target="#expired-documents-pane" type="button" role="tab"
                        aria-controls="expired-documents-pane" aria-selected="{{ $activeView === 'expired' ? 'true' : 'false' }}"
                        data-documents-tab="expired">
                        <span>Vencidos</span>
                        <span class="documents-list-tabs__count">{{ $expiredDocuments->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="min-w-250px documents-storage-widget">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Almacenamiento</span>
                    <span class="fw-bold">{{ $dossierStorage['used_label'] }} / {{ $dossierStorage['limit_label'] }}</span>
                </div>
                <div class="progress h-8px">
                    <div class="progress-bar bg-primary" style="width: {{ $storagePercentage }}%"></div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="documentsTabsContent">
            <div class="tab-pane fade {{ $activeView === 'expired' ? '' : 'show active' }}" id="current-documents-pane"
                role="tabpanel" aria-labelledby="current-documents-tab" tabindex="0">
                <div class="documents-list-table-card">
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle mb-0" id="currentDocumentsTable">
                            <thead>
                                <tr class="text-muted text-uppercase fs-8">
                                    <th class="ps-7 min-w-240px">Documento</th>
                                    <th class="min-w-260px">Archivo</th>
                                    <th class="min-w-130px">Vence</th>
                                    <th class="min-w-150px">Fecha</th>
                                    <th class="text-end pe-7 min-w-325px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($currentDocuments as $document)
                                @php
                                    [$icon, $iconColor, $iconBg] = $fileIcon($document['file_name']);
                                @endphp
                                <tr class="documents-list-row">
                                    <td class="ps-7">
                                        <div class="d-flex flex-column gap-1">
                                            <div class="documents-list-title">{{ $document['label'] }}</div>
                                            <div class="documents-list-meta">{{ $document['entity_name'] }}</div>
                                            <div class="documents-list-meta">{{ $document['entity_type_label'] }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <a href="{{ $document['file_url'] }}" target="_blank" rel="noopener"
                                                class="documents-file-open documents-file-icon rounded {{ $iconBg }} d-flex align-items-center justify-content-center"
                                                title="Abrir archivo">
                                                <i class="ki-outline {{ $icon }} fs-3 {{ $iconColor }}"></i>
                                            </a>
                                            <div class="min-w-0">
                                                <a href="{{ $document['file_url'] }}" target="_blank" rel="noopener"
                                                    class="documents-file-name fw-semibold text-gray-800 text-hover-primary text-break">
                                                    {{ $document['file_name'] }}
                                                </a>
                                                <div class="documents-list-meta">
                                                    {{ $document['is_expired'] ? 'Documento vencido' : 'Abrir en una nueva pestaña' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($document['expires_at'])
                                            <span @class(['fw-bold text-warning' => $document['is_expired']])>
                                                {{ $document['expires_at']->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><div class="documents-list-value">{{ $document['updated_at']?->format('d/m/Y H:i') ?: '-' }}</div></td>
                                    <td class="text-end pe-7">
                                        <div class="documents-list-actions">
                                            <a href="{{ $document['file_url'] }}" target="_blank" rel="noopener"
                                                class="btn btn-light btn-active-light-primary btn-sm" title="Ver archivo">
                                                <i class="ki-outline ki-eye fs-3"></i>
                                                <span>Ver</span>
                                            </a>
                                            <a href="{{ $document['file_url'] }}" download="{{ $document['file_name'] }}"
                                                class="btn btn-light btn-active-light-primary btn-sm" title="Descargar">
                                                <i class="ki-outline ki-file-down fs-3"></i>
                                                <span>Descargar</span>
                                            </a>
                                            @if ($document['entity_url'])
                                                <a href="{{ $document['entity_url'] }}" class="btn btn-primary btn-sm" title="Abrir expediente">
                                                    <i class="ki-outline ki-folder fs-3"></i>
                                                    <span>Expediente</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10" data-empty-row="true">
                                        <div class="text-center text-muted py-12">No hay documentos actuales.</div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $activeView === 'expired' ? 'show active' : '' }}" id="expired-documents-pane"
                role="tabpanel" aria-labelledby="expired-documents-tab" tabindex="0">
                <div class="documents-list-table-card">
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle mb-0" id="expiredDocumentsTable">
                            <thead>
                                <tr class="text-muted text-uppercase fs-8">
                                    <th class="ps-7 min-w-240px">Documento</th>
                                    <th class="min-w-260px">Archivo</th>
                                    <th class="min-w-130px">Vence</th>
                                    <th class="min-w-150px">Fecha</th>
                                    <th class="text-end pe-7 min-w-325px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($expiredDocuments as $document)
                                @php
                                    [$icon, $iconColor, $iconBg] = $fileIcon($document['file_name']);
                                @endphp
                                <tr class="documents-list-row">
                                    <td class="ps-7">
                                        <div class="d-flex flex-column gap-1">
                                            <div class="documents-list-title">{{ $document['label'] }}</div>
                                            <div class="documents-list-meta">{{ $document['entity_name'] }}</div>
                                            <div class="documents-list-meta">{{ $document['entity_type_label'] }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <a href="{{ $document['file_url'] }}" target="_blank" rel="noopener"
                                                class="documents-file-open documents-file-icon rounded {{ $iconBg }} d-flex align-items-center justify-content-center"
                                                title="Abrir archivo">
                                                <i class="ki-outline {{ $icon }} fs-3 {{ $iconColor }}"></i>
                                            </a>
                                            <div class="min-w-0">
                                                <a href="{{ $document['file_url'] }}" target="_blank" rel="noopener"
                                                    class="documents-file-name fw-semibold text-gray-800 text-hover-primary text-break">
                                                    {{ $document['file_name'] }}
                                                </a>
                                                <div class="documents-list-meta">Documento vencido</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($document['expires_at'])
                                            <span class="fw-bold text-warning">{{ $document['expires_at']->format('d/m/Y') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><div class="documents-list-value">{{ $document['updated_at']?->format('d/m/Y H:i') ?: '-' }}</div></td>
                                    <td class="text-end pe-7">
                                        <div class="documents-list-actions">
                                            <a href="{{ $document['file_url'] }}" target="_blank" rel="noopener"
                                                class="btn btn-light btn-active-light-primary btn-sm" title="Ver archivo">
                                                <i class="ki-outline ki-eye fs-3"></i>
                                                <span>Ver</span>
                                            </a>
                                            <a href="{{ $document['file_url'] }}" download="{{ $document['file_name'] }}"
                                                class="btn btn-light btn-active-light-primary btn-sm" title="Descargar">
                                                <i class="ki-outline ki-file-down fs-3"></i>
                                                <span>Descargar</span>
                                            </a>
                                            @if ($document['entity_url'])
                                                <a href="{{ $document['entity_url'] }}" class="btn btn-primary btn-sm" title="Abrir expediente">
                                                    <i class="ki-outline ki-folder fs-3"></i>
                                                    <span>Expediente</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10" data-empty-row="true">
                                        <div class="text-center text-muted py-12">No hay documentos vencidos.</div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('documentsSearchForm');
            const input = document.getElementById('documentsSearchInput');
            const resultCount = document.getElementById('documentsResultCount');
            const initialTab = @json($activeView === 'expired' ? 'expired' : 'current');
            const dataTables = {};
            let activeTableKey = initialTab;

            form?.addEventListener('submit', (event) => {
                event.preventDefault();
            });

            const initDataTable = (tableId, key) => {
                const table = document.getElementById(tableId);
                if (!table || typeof $ === 'undefined' || !$.fn.DataTable) {
                    return null;
                }

                table.querySelectorAll('td[data-empty-row="true"]').forEach((cell) => {
                    cell.closest('tr')?.remove();
                });

                dataTables[key] = $(table).DataTable({
                    dom: "rt<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
                    pageLength: 10,
                    lengthChange: false,
                    order: [],
                    info: true,
                    searching: true,
                    autoWidth: false,
                    language: {
                        info: 'Mostrando _START_ a _END_ de _TOTAL_ documentos',
                        infoEmpty: 'Mostrando 0 a 0 de 0 documentos',
                        paginate: {
                            first: 'Primera',
                            last: 'Ultima',
                            next: 'Siguiente',
                            previous: 'Anterior',
                        },
                        emptyTable: 'No hay documentos disponibles.',
                        zeroRecords: 'No se encontraron coincidencias con este filtro.',
                    },
                    columnDefs: [
                        {
                            targets: [4],
                            orderable: false,
                            searchable: false,
                        },
                    ],
                });

                return dataTables[key];
            };

            initDataTable('currentDocumentsTable', 'current');
            initDataTable('expiredDocumentsTable', 'expired');

            const syncResultCount = () => {
                const dataTable = dataTables[activeTableKey];
                if (!resultCount || !dataTable) {
                    return;
                }

                const count = dataTable.rows({ filter: 'applied' }).count();
                resultCount.textContent = `${count} ${count === 1 ? 'resultado' : 'resultados'}`;
            };

            Object.values(dataTables).forEach((dataTable) => {
                dataTable.on('draw', syncResultCount);
            });

            input?.addEventListener('input', (event) => {
                const dataTable = dataTables[activeTableKey];
                if (!dataTable) {
                    return;
                }

                dataTable.search(event.target.value || '').draw();
                syncResultCount();
            });

            document.querySelectorAll('[data-documents-tab]').forEach((tab) => {
                tab.addEventListener('shown.bs.tab', () => {
                    activeTableKey = tab.dataset.documentsTab || 'current';
                    const dataTable = dataTables[activeTableKey];

                    if (dataTable) {
                        dataTable.search(input?.value || '').draw();
                        dataTable.columns.adjust();
                    }

                    syncResultCount();
                });
            });

            syncResultCount();
        })();
    </script>
@endpush
