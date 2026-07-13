@extends('layouts.app')

@section('title', 'Cobranza | SuWork')

@push('styles')
    <style>
        .charges-list-module {
            --cl-surface: #ffffff;
            --cl-ink: #172033;
            --cl-text: #334155;
            --cl-muted: #7b879d;
            --cl-line: #e5eaf3;
            --cl-accent: #b54708;
            --cl-accent-soft: #fff1e8;
            --cl-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            color: var(--cl-text);
        }

        .charges-list-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .charges-list-search {
            position: relative;
            min-width: min(100%, 360px);
            flex: 1 1 300px;
        }

        .charges-list-search i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--cl-muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .charges-list-search .form-control {
            height: 52px;
            padding-left: 46px;
            border-radius: 16px;
            border: 1px solid var(--cl-line);
            background: #fbfcfe;
            color: var(--cl-ink);
            font-weight: 600;
            box-shadow: none;
        }

        .charges-list-search .form-control:focus {
            border-color: rgba(181, 71, 8, 0.35);
            box-shadow: 0 0 0 4px rgba(181, 71, 8, 0.08);
        }

        .charges-list-results {
            color: var(--cl-muted);
            font-size: 1rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .charges-list-tabs {
            gap: 12px;
            margin-bottom: 20px;
        }

        .charges-list-tabs .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 12px 18px;
            background: #f8fafc;
            color: var(--cl-text);
            font-weight: 800;
        }

        .charges-list-tabs .nav-link:hover {
            background: var(--cl-accent-soft);
            color: var(--cl-accent);
            border-color: rgba(181, 71, 8, 0.15);
        }

        .charges-list-tabs .nav-link.active {
            background: var(--cl-accent);
            color: #fff !important;
            box-shadow: 0 12px 28px rgba(181, 71, 8, 0.22);
        }

        .charges-list-tabs__count {
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

        .charges-list-tabs .nav-link.active .charges-list-tabs__count {
            background: rgba(255, 255, 255, 0.18);
        }

        .charges-list-table-card {
            margin-top: 20px;
            border: 1px solid var(--cl-line);
            border-radius: 20px;
            overflow: hidden;
            background: var(--cl-surface);
        }

        .charges-list-table-card .table-responsive {
            overflow-x: auto;
        }

        .charges-list-table-card table.dataTable {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0;
        }

        .charges-list-table-card thead th {
            padding-top: 20px;
            padding-bottom: 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--cl-line) !important;
            color: #94a3b8 !important;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
        }

        .charges-list-row {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .charges-list-row td {
            padding-top: 12px;
            padding-bottom: 12px;
            border-top: 1px solid var(--cl-line) !important;
            vertical-align: middle;
            background: #fff;
        }

        .charges-list-row:hover td {
            background: #fcf8f6;
        }

        .charges-list-title {
            color: var(--cl-ink);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .charges-list-meta {
            color: var(--cl-muted);
            font-size: 0.88rem;
            margin-top: 4px;
            line-height: 1.4;
        }

        .charges-list-value {
            color: var(--cl-ink);
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .charges-list-actions {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .charges-list-actions .btn {
            border-radius: 12px;
            font-weight: 700;
        }

        .charges-list-table-card .dataTables_info,
        .charges-list-table-card .dataTables_paginate {
            padding: 18px 28px 0;
            color: var(--cl-muted) !important;
            font-weight: 700;
        }

        .charges-list-table-card .dataTables_paginate .pagination {
            gap: 6px;
        }

        .charges-list-table-card .page-link {
            border-radius: 10px !important;
            border-color: var(--cl-line) !important;
            color: var(--cl-text) !important;
            min-width: 38px;
            text-align: center;
            font-weight: 700;
        }

        .charges-list-table-card .page-item.active .page-link {
            background: var(--cl-accent) !important;
            border-color: var(--cl-accent) !important;
            color: #fff !important;
        }

        @media (max-width: 991px) {
            .charges-list-table-card .dataTables_info,
            .charges-list-table-card .dataTables_paginate {
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        @media (max-width: 767.98px) {
            .charges-list-module {
                --cl-card-radius: 8px;
            }

            .charges-list-module.py-10 {
                padding-top: 1.25rem !important;
                padding-bottom: 1.25rem !important;
            }

            .charges-list-module > .d-flex.flex-wrap.justify-content-between {
                align-items: stretch !important;
                gap: 12px !important;
                margin-bottom: 18px !important;
            }

            .charges-list-module > .d-flex.flex-wrap.justify-content-between > div:first-child {
                min-width: 0;
                width: 100%;
            }

            .charges-list-module h1 {
                font-size: 1.35rem;
                line-height: 1.2;
                overflow-wrap: anywhere;
            }

            .charges-list-module > .d-flex.flex-wrap.justify-content-between > .d-flex {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px !important;
                width: 100%;
            }

            .charges-list-module > .d-flex.flex-wrap.justify-content-between > .d-flex .btn {
                min-width: 0;
                border-radius: 8px;
                padding-left: 10px;
                padding-right: 10px;
                white-space: normal;
            }

            .charges-kpi-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                margin-bottom: 18px !important;
            }

            .charges-kpi-grid > [class*="col-"] {
                width: auto;
                max-width: none;
                padding: 0 !important;
            }

            .charges-kpi-grid .card {
                border-radius: 8px;
                min-width: 0;
            }

            .charges-kpi-grid .card-body {
                align-items: flex-start !important;
                flex-direction: column;
                gap: 8px !important;
                min-width: 0;
                padding: 14px !important;
            }

            .charges-kpi-grid .card-body > div:last-child {
                min-width: 0;
                width: 100%;
            }

            .charges-kpi-grid .symbol,
            .charges-kpi-grid .symbol-label {
                width: 34px !important;
                height: 34px !important;
                min-width: 34px !important;
            }

            .charges-kpi-grid .text-muted {
                font-size: 0.68rem !important;
                font-weight: 800;
                letter-spacing: 0.02em;
                line-height: 1.15;
                min-height: 1.55rem;
                text-transform: uppercase;
            }

            .charges-kpi-grid .fw-bold.fs-2 {
                max-width: 100%;
                color: var(--cl-ink);
                font-size: clamp(1rem, 4.4vw, 1.35rem) !important;
                line-height: 1.08;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            .charges-list-toolbar {
                gap: 10px;
                margin-bottom: 14px;
            }

            .charges-list-search {
                flex-basis: 100%;
                min-width: 0;
            }

            .charges-list-search .form-control {
                height: 46px;
                border-radius: 8px;
                font-size: 0.86rem;
            }

            .charges-list-results {
                width: 100%;
                font-size: 0.8rem;
            }

            .charges-list-tabs {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                margin-bottom: 14px;
            }

            .charges-list-tabs .nav-item,
            .charges-list-tabs .nav-link {
                width: 100%;
            }

            .charges-list-tabs .nav-link {
                justify-content: center;
                border-radius: 8px;
                padding: 10px;
                font-size: 0.82rem;
                white-space: normal;
            }

            .charges-list-table-card {
                margin-top: 14px;
                border: 0;
                border-radius: 0;
                overflow: visible;
                background: transparent;
            }

            .charges-list-table-card .table-responsive {
                overflow: visible;
            }

            .charges-list-table-card table,
            .charges-list-table-card table.dataTable,
            .charges-list-table-card tbody {
                display: block;
                width: 100% !important;
            }

            .charges-list-table-card thead {
                display: none;
            }

            .charges-list-table-card tbody {
                display: grid;
                gap: 14px;
            }

            .charges-list-row {
                display: block;
                padding: 18px;
                border: 1px solid #e8eef7;
                border-radius: 8px;
                background: #fff !important;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
                overflow: hidden;
            }

            .charges-list-table-card table:not(.table-bordered) tr.charges-list-row {
                padding: 18px !important;
            }

            .charges-list-row:hover td {
                background: transparent;
            }

            .charges-list-row td {
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

            .charges-list-row td::before {
                content: attr(data-mobile-label);
                flex: 0 0 94px;
                color: #8b96b2;
                font-size: 0.66rem;
                font-weight: 800;
                letter-spacing: 0.04em;
                line-height: 1.25;
                text-align: left;
                text-transform: uppercase;
            }

            .charges-list-row td:first-child {
                display: block;
                padding-top: 0 !important;
                padding-bottom: 14px !important;
                border-top: 0 !important;
                text-align: left !important;
            }

            .charges-list-row td:first-child::before {
                content: none;
            }

            .charges-list-title {
                display: block;
                font-size: 1rem;
                line-height: 1.25;
                overflow-wrap: anywhere;
            }

            .charges-list-meta {
                font-size: 0.78rem;
                overflow-wrap: anywhere;
            }

            .charges-list-value,
            .charges-list-row .badge {
                max-width: 58%;
                min-width: 0;
                font-size: 0.84rem;
                line-height: 1.3;
                overflow-wrap: anywhere;
                text-align: right;
                white-space: normal;
            }

            #chargesTable .charges-list-row td:nth-child(2)::before,
            #paymentsTable .charges-list-row td:nth-child(2)::before {
                content: 'Inquilino / propiedad';
            }

            #chargesTable .charges-list-row td:nth-child(3)::before {
                content: 'Vence';
            }

            #chargesTable .charges-list-row td:nth-child(4)::before,
            #paymentsTable .charges-list-row td:nth-child(4)::before {
                content: 'Monto';
            }

            #chargesTable .charges-list-row td:nth-child(5)::before,
            #paymentsTable .charges-list-row td:nth-child(6)::before {
                content: 'Estado';
            }

            #paymentsTable .charges-list-row td:nth-child(3)::before {
                content: 'Fecha';
            }

            #paymentsTable .charges-list-row td:nth-child(5)::before {
                content: 'Metodo';
            }

            #chargesTable .charges-list-row td:last-child {
                margin-top: 6px;
                padding-top: 14px !important;
                border-top: 1px solid #e8eef7 !important;
            }

            #chargesTable .charges-list-row td:last-child::before {
                content: none;
            }

            .charges-list-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                width: 100%;
            }

            .charges-list-actions .btn {
                min-width: 0;
                width: 100%;
                border-radius: 8px;
                padding: 8px 10px;
                font-size: 0.76rem;
                line-height: 1.2;
                white-space: normal;
            }

            .charges-list-table-card .dataTables_info {
                padding: 14px 2px 0;
                font-size: 0.78rem;
                text-align: center;
            }

            .charges-list-table-card .dataTables_paginate {
                padding: 12px 2px 0;
            }

            .charges-list-table-card .dataTables_paginate .pagination {
                justify-content: center;
                flex-wrap: wrap;
            }
        }

        #registerPaymentModal .modal-content {
            max-height: calc(100dvh - 2rem);
            overflow: hidden;
        }

        #registerPaymentModal form {
            min-height: 0;
            max-height: inherit;
        }

        #registerPaymentModal .modal-header,
        #registerPaymentModal .modal-footer {
            flex: 0 0 auto;
        }

        #registerPaymentModal .modal-body {
            min-height: 0;
            overflow-y: auto;
        }

        @supports not (height: 100dvh) {
            #registerPaymentModal .modal-content {
                max-height: calc(100vh - 2rem);
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 charges-module charges-list-module">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-information fs-2hx text-warning me-4"></i>
                <div class="fw-semibold">{{ session('warning') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-cross-circle fs-2hx text-danger me-4"></i>
                <div class="fw-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">
                    @if ($selectedProperty)
                        Cobranza de {{ $selectedProperty->internal_name }}
                    @else
                        Cobranza
                    @endif
                </h1>
                <div class="text-muted fs-6">
                    @if ($selectedProperty)
                        Cargos, pagos y conciliacion de esta propiedad
                    @else
                        Cargos, pagos y conciliacion
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-3">
                @if ($selectedProperty && $canManageCharges)
                    <a href="{{ route('properties.show', $selectedProperty) }}" class="btn btn-light fw-bold">
                        <i class="ki-outline ki-home fs-4 me-1"></i> Ver propiedad
                    </a>
                @endif
                @if ($canManageCharges)
                    <button type="button" class="btn btn-light-primary fw-bold" data-bs-toggle="modal"
                        data-bs-target="#bulkChargeModal">
                        <i class="ki-outline ki-calendar-add fs-4 me-1"></i> Generar cobranza
                    </button>
                    <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal"
                        data-bs-target="#createChargeModal">
                        <i class="ki-outline ki-plus fs-4 me-1"></i> Nuevo cargo
                    </button>
                @endif
            </div>
        </div>

        @if ($canManageCharges)
        <div class="row g-5 mb-8 charges-kpi-grid">
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-4 py-6">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-light-warning">
                                <i class="ki-outline ki-time text-warning fs-3"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-7">Por cobrar</div>
                            <div class="fw-bold fs-2">${{ number_format(max(0, $stats['pending_amount']), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-4 py-6">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-light-danger">
                                <i class="ki-outline ki-information-3 text-danger fs-3"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-7">Vencido</div>
                            <div class="fw-bold fs-2">${{ number_format(max(0, $stats['overdue_amount']), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-4 py-6">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-light-success">
                                <i class="ki-outline ki-check text-success fs-3"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-7">Cobrado ({{ $currentMonthLabel }})</div>
                            <div class="fw-bold fs-2">${{ number_format(max(0, $stats['collected_month']), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-4 py-6">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-light-info">
                                <i class="ki-outline ki-dollar text-info fs-3"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-7">Pend. validacion</div>
                            <div class="fw-bold fs-2">{{ $stats['pending_validation'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="charges-list-toolbar">
            <form method="GET" action="{{ route('charges.index', $selectedProperty ? ['property' => $selectedProperty->uuid] : []) }}"
                id="chargesSearchForm" class="charges-list-search mb-0">
                <i class="bi bi-search"></i>
                <input
                    id="chargesSearchInput"
                    type="search"
                    class="form-control"
                    placeholder="Buscar concepto, inquilino, propiedad, estado..."
                    autocomplete="off">
            </form>

            <div id="chargesResultCount" class="charges-list-results">{{ $charges->count() }} resultados</div>
        </div>

        <ul class="nav charges-list-tabs" id="chargesTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="charges-tab" data-bs-toggle="tab" data-bs-target="#charges-pane"
                    type="button" role="tab" aria-controls="charges-pane" aria-selected="true" data-charges-tab="charges">
                    <span>Pagos pendientes</span>
                    <span class="charges-list-tabs__count">{{ $stats['charges_count'] }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-pane"
                    type="button" role="tab" aria-controls="payments-pane" aria-selected="false" data-charges-tab="payments">
                    <span>Cobrados</span>
                    <span class="charges-list-tabs__count">{{ $stats['payments_count'] }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="chargesTabsContent">
            <div class="tab-pane fade show active" id="charges-pane" role="tabpanel" aria-labelledby="charges-tab" tabindex="0">
                <div class="charges-list-table-card">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle mb-0" id="chargesTable">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase fs-8">
                                <th class="ps-7 min-w-220px">Concepto</th>
                                <th class="min-w-220px">Inquilino / Propiedad</th>
                                <th class="min-w-130px">Vencimiento</th>
                                <th class="min-w-140px">Monto</th>
                                <th class="min-w-120px">Estado</th>
                                <th class="min-w-320px text-end pe-7">Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($charges as $charge)
                                @php
                                    $canRegisterPayment = $canManageCharges && in_array(
                                        $charge->status,
                                        [\App\Models\Charge::STATUS_PENDING, \App\Models\Charge::STATUS_PARTIAL, \App\Models\Charge::STATUS_IN_VALIDATION],
                                        true,
                                    );
                                    $canEditCharge = $canManageCharges && in_array(
                                        $charge->status,
                                        [\App\Models\Charge::STATUS_PENDING, \App\Models\Charge::STATUS_PARTIAL, \App\Models\Charge::STATUS_IN_VALIDATION],
                                        true,
                                    );
                                    $canDeleteCharge = $canManageCharges
                                        && $charge->status !== \App\Models\Charge::STATUS_CANCELED
                                        && ($charge->status !== \App\Models\Charge::STATUS_PAID || $canDeletePaidCharges);
                                @endphp
                                <tr class="charges-list-row" data-charge-row>
                                    <td class="ps-7">
                                        <div class="charges-list-title">{{ $charge->concept }}</div>
                                        <div class="charges-list-meta">{{ $charge->type_label }}</div>
                                    </td>
                                    <td>
                                        <div class="charges-list-value">{{ $charge->tenant?->full_name ?? '-' }}</div>
                                        <div class="charges-list-meta">
                                            {{ $charge->property?->internal_reference ?: $charge->property?->internal_name ?: '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="charges-list-value">{{ $charge->due_date?->format('d M Y') ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="charges-list-value">${{ number_format((float) $charge->amount, 2) }}
                                        </div>
                                        @if ($charge->outstanding_amount > 0 && $charge->status !== \App\Models\Charge::STATUS_CANCELED)
                                            <div class="charges-list-meta">Saldo: ${{ number_format($charge->outstanding_amount, 2) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $charge->status_badge_class }}">
                                            {{ $charge->display_status_label }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-7">
                                        <div class="charges-list-actions">
                                            <a href="{{ route('charges.show', array_filter([
                                                'charge' => $charge,
                                                'property' => $selectedProperty?->uuid,
                                                'return_to' => request()->fullUrl(),
                                            ])) }}"
                                                class="btn btn-sm btn-light">
                                                Ver
                                            </a>

                                            @if ($canEditCharge)
                                                <button type="button" class="btn btn-sm btn-light-primary" data-edit-charge
                                                    data-action="{{ route('charges.update', $charge) }}"
                                                    data-charge="{{ $charge->uuid }}" data-type="{{ $charge->type }}"
                                                    data-due-date="{{ $charge->due_date?->format('Y-m-d') }}"
                                                    data-amount="{{ number_format((float) $charge->amount, 2, '.', '') }}"
                                                    data-period-month="{{ $charge->period_month }}"
                                                    data-period-year="{{ $charge->period_year }}"
                                                    data-concept="{{ $charge->concept }}" data-notes="{{ $charge->notes }}">
                                                    Editar cargo
                                                </button>
                                            @endif

                                            @if ($canDeleteCharge)
                                                <form method="POST" action="{{ route('charges.destroy', $charge) }}"
                                                    class="d-inline js-delete-charge-form"
                                                    data-charge-concept="{{ $charge->concept }}"
                                                    data-charge-paid="{{ $charge->status === \App\Models\Charge::STATUS_PAID ? 'true' : 'false' }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="deletion_note" value="">
                                                    <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                                                    <button type="submit" class="btn btn-sm btn-light-danger">Eliminar cargo</button>
                                                </form>
                                            @endif

                                            @if ($canRegisterPayment)
                                                <button type="button" class="btn btn-sm btn-success" data-register-payment
                                                    data-charge="{{ $charge->uuid }}"
                                                    data-action="{{ route('charges.payments.store', $charge) }}"
                                                    data-concept="{{ $charge->concept }}"
                                                    data-outstanding="{{ number_format($charge->outstanding_amount, 2, '.', '') }}">
                                                    Registrar pago
                                                </button>
                                            @endif

                                            <a href="{{ route('charges.public.show', ['token' => $charge->payment_token]) }}"
                                                target="_blank" class="btn btn-sm btn-light-primary">
                                                Abrir link
                                            </a>
                                            <button type="button" class="btn btn-sm btn-light"
                                                data-copy-link="{{ route('charges.public.show', ['token' => $charge->payment_token]) }}">
                                                Copiar link
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-16 text-muted" data-empty-row="true">No hay cargos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            </div>

            <div class="tab-pane fade" id="payments-pane" role="tabpanel" aria-labelledby="payments-tab" tabindex="0">
                <div class="charges-list-table-card">
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle mb-0" id="paymentsTable">
                            <thead>
                                <tr class="fw-bold text-muted text-uppercase fs-8">
                                    <th class="ps-7 min-w-220px">Pago</th>
                                    <th class="min-w-220px">Inquilino / Propiedad</th>
                                    <th class="min-w-170px">Fecha</th>
                                    <th class="min-w-140px">Monto</th>
                                    <th class="min-w-150px">Metodo</th>
                                    <th class="min-w-120px">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $payment)
                                    <tr class="charges-list-row" data-payment-row>
                                        <td class="ps-7">
                                            <div class="charges-list-title">{{ $payment->charge?->concept ?? '-' }}</div>
                                            <div class="charges-list-meta">{{ $payment->reference ?: 'Sin referencia' }}</div>
                                        </td>
                                        <td>
                                            <div class="charges-list-value">{{ $payment->charge?->tenant?->full_name ?? '-' }}</div>
                                            <div class="charges-list-meta">
                                                {{ $payment->charge?->property?->internal_reference ?: $payment->charge?->property?->internal_name ?: '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="charges-list-value">{{ $payment->paid_at?->format('d M Y') ?? $payment->payment_date?->format('d M Y') ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="charges-list-value">${{ number_format((float) $payment->amount, 2) }}</div>
                                        </td>
                                        <td>
                                            <div class="charges-list-value">{{ $payment->method_label }}</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-success text-success">{{ $payment->status_label }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-16 text-muted" data-empty-row="true">No hay pagos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canManageCharges)
    <div class="modal fade" id="createChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('charges.store') }}" class="h-100 d-flex flex-column">
                    @csrf
                    @if ($selectedProperty)
                        <input type="hidden" name="property_context" value="{{ $selectedProperty->uuid }}">
                    @endif
                    <div class="modal-header">
                        <h3 class="modal-title">Nuevo cargo</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->createCharge->any())
                            <div class="alert alert-danger mb-6">
                                Revisa los datos del formulario.
                            </div>
                        @endif

                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Propiedad</label>
                                <select name="property_id" class="form-select" id="chargeProperty" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($properties as $property)
                                        <option value="{{ $property->id }}" data-tenant-id="{{ $property->tenant_id }}" {{ (string) old('property_id', $selectedProperty?->id) === (string) $property->id ? 'selected' : '' }}>
                                            {{ $property->internal_name }}{{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('property_id', 'createCharge')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Inquilino</label>
                                <select name="tenant_id" class="form-select" id="chargeTenant" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" {{ (string) old('tenant_id') === (string) $tenant->id ? 'selected' : '' }}>
                                            {{ $tenant->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tenant_id', 'createCharge')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Tipo</label>
                                <select name="type" class="form-select" required>
                                    @foreach ($typeOptions as $typeValue => $typeLabel)
                                        <option value="{{ $typeValue }}" {{ old('type', 'rent') === $typeValue ? 'selected' : '' }}>
                                            {{ $typeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha vencimiento</label>
                                <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Monto (MXN)</label>
                                <input type="number" min="0.01" step="0.01" name="amount" class="form-control"
                                    value="{{ old('amount') }}" placeholder="0.00" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Mes (periodo)</label>
                                <input type="number" min="1" max="12" name="period_month" class="form-control"
                                    value="{{ old('period_month', now()->month) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Anio (periodo)</label>
                                <input type="number" min="2000" max="2200" name="period_year" class="form-control"
                                    value="{{ old('period_year', now()->year) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Concepto</label>
                                <input type="text" name="concept" class="form-control" value="{{ old('concept') }}"
                                    placeholder="Ej. Renta Enero 2026" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" class="form-control" rows="4"
                                    placeholder="Notas internas">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear cargo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="registerPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" id="registerPaymentForm" enctype="multipart/form-data" class="h-100 d-flex flex-column">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Registrar pago</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="bg-light rounded p-4 mb-6">
                            <div class="text-muted fs-7 mb-1">Cargo a cubrir</div>
                            <div class="fw-bold fs-4 mb-1" id="registerPaymentConcept">-</div>
                            <div class="text-muted fs-6">
                                Saldo pendiente: <span class="text-danger fw-bold"
                                    id="registerPaymentOutstanding">$0.00</span>
                            </div>
                        </div>

                        @if ($errors->registerPayment->any())
                            <div class="alert alert-danger mb-5">
                                Revisa la captura del pago.
                            </div>
                        @endif

                        <input type="hidden" name="charge_uuid" id="registerPaymentChargeUuid"
                            value="{{ old('charge_uuid') }}">

                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Monto (MXN)</label>
                                <input type="number" min="0.01" step="0.01" name="amount" id="registerPaymentAmount"
                                    value="{{ old('amount') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha de pago</label>
                                <input type="date" name="payment_date"
                                    value="{{ old('payment_date', now()->toDateString()) }}" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Metodo de pago</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($paymentMethods as $methodValue => $methodLabel)
                                        <option value="{{ $methodValue }}" {{ old('payment_method') === $methodValue ? 'selected' : '' }}>
                                            {{ $methodLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Referencia / Folio</label>
                                <input type="text" name="reference" class="form-control" value="{{ old('reference') }}"
                                    placeholder="Numero de referencia">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Comprobante de pago (imagen)</label>
                                <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" class="form-control" rows="3"
                                    placeholder="Notas internas">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Registrar pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" id="editChargeForm" class="h-100 d-flex flex-column">
                    @csrf
                    @method('PUT')
                    @if ($selectedProperty)
                        <input type="hidden" name="property_context" value="{{ $selectedProperty->uuid }}">
                    @endif
                    <div class="modal-header">
                        <h3 class="modal-title">Editar cargo</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->updateCharge->any())
                            <div class="alert alert-danger mb-5">
                                Revisa la informacion del cargo.
                            </div>
                        @endif

                        <input type="hidden" name="charge_uuid" id="editChargeUuid" value="{{ old('charge_uuid') }}">

                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Tipo</label>
                                <select name="type" id="editChargeType" class="form-select" required>
                                    @foreach ($typeOptions as $typeValue => $typeLabel)
                                        <option value="{{ $typeValue }}" {{ old('type') === $typeValue ? 'selected' : '' }}>
                                            {{ $typeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha vencimiento</label>
                                <input type="date" name="due_date" id="editChargeDueDate" class="form-control"
                                    value="{{ old('due_date') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Monto (MXN)</label>
                                <input type="number" min="0.01" step="0.01" name="amount" id="editChargeAmount"
                                    class="form-control" value="{{ old('amount') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Mes (periodo)</label>
                                <input type="number" min="1" max="12" name="period_month" id="editChargePeriodMonth"
                                    class="form-control" value="{{ old('period_month') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Anio (periodo)</label>
                                <input type="number" min="2000" max="2200" name="period_year" id="editChargePeriodYear"
                                    class="form-control" value="{{ old('period_year') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Concepto</label>
                                <input type="text" name="concept" id="editChargeConcept" class="form-control"
                                    value="{{ old('concept') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" id="editChargeNotes" class="form-control"
                                    rows="3">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:1200px; width:100%;">
            <div class="modal-content">

                <form method="POST" action="{{ route('charges.bulk.store') }}" id="bulkChargeForm"
                    class="h-100 d-flex flex-column">

                    @csrf

                    @if ($selectedProperty)
                        <input type="hidden" name="property_context" value="{{ $selectedProperty->uuid }}">
                    @endif

                    <!-- HEADER -->
                    <div class="modal-header">
                        <h3 class="modal-title">Generar cobranza mensual</h3>

                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <!-- BODY CON SCROLL -->
                    <div class="modal-body" style="max-height:75vh; overflow-y:auto;">

                        @if ($errors->generateCharges->any())
                            <div class="alert alert-danger mb-5">
                                Revisa los datos para generar la cobranza.
                            </div>
                        @endif

                        @php
                            $bulkChargeDay = old('charge_day', $selectedProperty?->charge_day ?: $selectedProperty?->contract_starts_at?->day);
                            $bulkChargeToleranceDays = old('charge_tolerance_days', (int) ($selectedProperty?->charge_tolerance_days ?? 0));
                        @endphp

                        <!-- FORM -->
                        <div class="row g-5 mb-6">

                            <div class="col-md-6">
                                <label class="form-label required">Propiedad</label>
                                <select name="property_id" id="bulkPropertyId" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($chargeableProperties as $property)
                                        <option value="{{ $property->id }}"
                                            data-tenant-name="{{ $property->tenant?->full_name }}"
                                            data-contract-start="{{ $property->contract_starts_at?->format('Y-m-d') }}"
                                            data-contract-expires="{{ $property->contract_expires_at?->format('Y-m-d') }}"
                                            data-monthly-rent="{{ number_format((float) ($property->monthly_rent_price ?? 0), 2, '.', '') }}"
                                            data-charge-day="{{ $property->charge_day }}"
                                            data-charge-tolerance-days="{{ (int) ($property->charge_tolerance_days ?? 0) }}" {{ (string) old('property_id', $selectedProperty?->id) === (string) $property->id ? 'selected' : '' }}>
                                            {{ $property->internal_name }}{{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Inquilino actual</label>
                                <input type="text" id="bulkTenantName" class="form-control" readonly value="">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Contrato inicia (opcional)</label>
                                <input type="date" name="contract_starts_at" id="bulkContractStartsAt" class="form-control"
                                    value="{{ old('contract_starts_at', $selectedProperty?->contract_starts_at?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Contrato vence (opcional)</label>
                                <input type="date" name="contract_expires_at" id="bulkContractExpiresAt"
                                    class="form-control"
                                    value="{{ old('contract_expires_at', $selectedProperty?->contract_expires_at?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Precio renta mensual</label>
                                <input type="number" name="monthly_rent_price" id="bulkMonthlyRentPrice"
                                    class="form-control" min="0" step="0.01"
                                    value="{{ old('monthly_rent_price', number_format((float) ($selectedProperty?->monthly_rent_price ?? 0), 2, '.', '')) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Día de cobro</label>
                                <input type="number" name="charge_day" id="bulkChargeDay" class="form-control" min="1"
                                    max="31" step="1" value="{{ $bulkChargeDay }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Tolerancia (días)</label>
                                <input type="number" name="charge_tolerance_days" id="bulkChargeToleranceDays"
                                    class="form-control" min="0" max="31" step="1" value="{{ $bulkChargeToleranceDays }}">
                            </div>

                        </div>

                        <!-- BOTONES -->
                        <div class="d-flex flex-wrap justify-content-end gap-2 mb-5">
                            <button type="button" class="btn btn-light-primary" id="bulkAddRowBtn">Agregar registro</button>
                            <button type="button" class="btn btn-light-primary" id="bulkGenerateDepositBtn">Generar
                                depósito</button>
                            <button type="button" class="btn btn-light-primary"
                                id="bulkGenerateNoGuarantorDepositBtn">Generar depósito sin aval</button>
                            <button type="button" class="btn btn-primary" id="previewBulkChargesBtn">Ver lista de
                                pagos</button>
                        </div>

                        <div id="bulkChargeRowsContainer"></div>

                        <!-- PREVIEW -->
                        <div class="border rounded p-4 d-none" id="bulkPreviewContainer">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0">Vista previa</h4>
                                <span class="text-muted fs-7" id="bulkSummaryText"></span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-row-bordered align-middle">
                                    <thead>
                                        <tr class="text-muted text-uppercase fs-8">
                                            <th>Propiedad</th>
                                            <th>Inquilino</th>
                                            <th>Tipo</th>
                                            <th>Periodo</th>
                                            <th>Vencimiento</th>
                                            <th>Monto</th>
                                            <th>Concepto</th>
                                            <th>Estado</th>
                                            <th class="text-end">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulkPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear cargos</button>
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

        document.addEventListener('DOMContentLoaded', () => {
            const hasNoCharges = @json($charges->isEmpty());
            const hasProperty = @json((bool) $selectedProperty);
            const canManageCharges = @json((bool) $canManageCharges);

            if (canManageCharges && hasNoCharges && hasProperty) {
                const modalEl = document.getElementById('bulkChargeModal');
                if (modalEl) {
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }
            }
        });
        (() => {
            const propertySelect = document.getElementById('chargeProperty');
            const tenantSelect = document.getElementById('chargeTenant');
            const registerPaymentForm = document.getElementById('registerPaymentForm');
            const registerPaymentConcept = document.getElementById('registerPaymentConcept');
            const registerPaymentOutstanding = document.getElementById('registerPaymentOutstanding');
            const registerPaymentAmount = document.getElementById('registerPaymentAmount');
            const registerPaymentChargeUuid = document.getElementById('registerPaymentChargeUuid');
            const searchForm = document.getElementById('chargesSearchForm');
            const searchInput = document.getElementById('chargesSearchInput');
            const resultCount = document.getElementById('chargesResultCount');
            const tableOptions = {
                dom: "rt<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
                pageLength: 25,
                lengthChange: false,
                order: [],
                info: true,
                searching: true,
                autoWidth: false,
                language: {
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    paginate: {
                        first: 'Primera',
                        last: 'Ultima',
                        next: 'Siguiente',
                        previous: 'Anterior',
                    },
                    emptyTable: 'No hay registros disponibles.',
                    zeroRecords: 'No se encontraron coincidencias con este filtro.',
                },
            };
            const dataTables = {};
            let activeTableKey = 'charges';

            const initDataTable = (tableId, key, extraOptions = {}) => {
                const table = document.getElementById(tableId);
                if (!table || typeof $ === 'undefined' || !$.fn.DataTable) {
                    return null;
                }

                table.querySelectorAll('td[data-empty-row="true"]').forEach((cell) => {
                    cell.closest('tr')?.remove();
                });

                dataTables[key] = $(table).DataTable({
                    ...tableOptions,
                    ...extraOptions,
                });

                return dataTables[key];
            };

            const syncResultCount = () => {
                const dataTable = dataTables[activeTableKey];
                if (!resultCount || !dataTable) {
                    return;
                }

                const count = dataTable.rows({ filter: 'applied' }).count();
                resultCount.textContent = `${count} ${count === 1 ? 'resultado' : 'resultados'}`;
            };

            searchForm?.addEventListener('submit', (event) => {
                event.preventDefault();
            });

            initDataTable('chargesTable', 'charges', {
                columnDefs: [
                    {
                        targets: [5],
                        orderable: false,
                        searchable: false,
                    },
                ],
            });
            initDataTable('paymentsTable', 'payments');

            Object.values(dataTables).forEach((dataTable) => {
                dataTable.on('draw', syncResultCount);
            });

            searchInput?.addEventListener('input', (event) => {
                const dataTable = dataTables[activeTableKey];
                if (!dataTable) {
                    return;
                }

                dataTable.search(event.target.value || '').draw();
                syncResultCount();
            });

            document.querySelectorAll('[data-charges-tab]').forEach((tab) => {
                tab.addEventListener('shown.bs.tab', () => {
                    activeTableKey = tab.dataset.chargesTab || 'charges';
                    const dataTable = dataTables[activeTableKey];

                    if (dataTable) {
                        dataTable.search(searchInput?.value || '').draw();
                        dataTable.columns.adjust();
                    }

                    syncResultCount();
                });
            });

            syncResultCount();

            if (propertySelect && tenantSelect) {
                propertySelect.addEventListener('change', () => {
                    const selected = propertySelect.options[propertySelect.selectedIndex];
                    const tenantId = selected?.dataset?.tenantId;
                    if (tenantId) {
                        tenantSelect.value = tenantId;
                    }
                });
            }

            const editChargeForm = document.getElementById('editChargeForm');
            const editChargeUuid = document.getElementById('editChargeUuid');
            const editChargeType = document.getElementById('editChargeType');
            const editChargeDueDate = document.getElementById('editChargeDueDate');
            const editChargeAmount = document.getElementById('editChargeAmount');
            const editChargePeriodMonth = document.getElementById('editChargePeriodMonth');
            const editChargePeriodYear = document.getElementById('editChargePeriodYear');
            const editChargeConcept = document.getElementById('editChargeConcept');
            const editChargeNotes = document.getElementById('editChargeNotes');

            document.addEventListener('click', async (event) => {
                const target = event.target instanceof Element ? event.target : null;
                if (!target) {
                    return;
                }

                const copyLinkButton = target.closest('[data-copy-link]');
                if (copyLinkButton) {
                    const link = copyLinkButton.getAttribute('data-copy-link');
                    if (!link) {
                        return;
                    }

                    try {
                        await navigator.clipboard.writeText(link);
                        copyLinkButton.textContent = 'Copiado';
                        setTimeout(() => {
                            copyLinkButton.textContent = 'Copiar link';
                        }, 1400);
                    } catch (error) {
                        window.prompt('Copia este link:', link);
                    }

                    return;
                }

                const registerPaymentButton = target.closest('[data-register-payment]');
                if (registerPaymentButton) {
                    if (!registerPaymentForm) {
                        return;
                    }

                    const action = registerPaymentButton.getAttribute('data-action') || '';
                    const chargeUuid = registerPaymentButton.getAttribute('data-charge') || '';
                    const concept = registerPaymentButton.getAttribute('data-concept') || '-';
                    const outstanding = parseFloat(registerPaymentButton.getAttribute('data-outstanding') || '0');

                    registerPaymentForm.setAttribute('action', action);
                    if (registerPaymentChargeUuid) {
                        registerPaymentChargeUuid.value = chargeUuid;
                    }
                    registerPaymentConcept.textContent = concept;
                    registerPaymentOutstanding.textContent = `$${outstanding.toFixed(2)}`;
                    registerPaymentAmount.value = outstanding.toFixed(2);
                    registerPaymentAmount.max = outstanding.toFixed(2);

                    const modalEl = document.getElementById('registerPaymentModal');
                    if (!modalEl) {
                        return;
                    }
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();

                    return;
                }

                const editChargeButton = target.closest('[data-edit-charge]');
                if (editChargeButton) {
                    if (!editChargeForm) {
                        return;
                    }

                    editChargeForm.setAttribute('action', editChargeButton.getAttribute('data-action') || '');
                    if (editChargeUuid) editChargeUuid.value = editChargeButton.getAttribute('data-charge') || '';
                    if (editChargeType) editChargeType.value = editChargeButton.getAttribute('data-type') || 'rent';
                    if (editChargeDueDate) editChargeDueDate.value = editChargeButton.getAttribute('data-due-date') || '';
                    if (editChargeAmount) editChargeAmount.value = editChargeButton.getAttribute('data-amount') || '';
                    if (editChargePeriodMonth) editChargePeriodMonth.value = editChargeButton.getAttribute('data-period-month') || '';
                    if (editChargePeriodYear) editChargePeriodYear.value = editChargeButton.getAttribute('data-period-year') || '';
                    if (editChargeConcept) editChargeConcept.value = editChargeButton.getAttribute('data-concept') || '';
                    if (editChargeNotes) editChargeNotes.value = editChargeButton.getAttribute('data-notes') || '';

                    const modalEl = document.getElementById('editChargeModal');
                    if (!modalEl) {
                        return;
                    }
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();

                    return;
                }

            });

            const previewBtn = document.getElementById('previewBulkChargesBtn');
            const bulkChargeForm = document.getElementById('bulkChargeForm');
            const bulkPropertyId = document.getElementById('bulkPropertyId');
            const bulkTenantName = document.getElementById('bulkTenantName');
            const bulkContractStartsAt = document.getElementById('bulkContractStartsAt');
            const bulkContractExpiresAt = document.getElementById('bulkContractExpiresAt');
            const bulkMonthlyRentPrice = document.getElementById('bulkMonthlyRentPrice');
            const bulkChargeDay = document.getElementById('bulkChargeDay');
            const bulkChargeToleranceDays = document.getElementById('bulkChargeToleranceDays');
            const bulkPreviewContainer = document.getElementById('bulkPreviewContainer');
            const bulkPreviewBody = document.getElementById('bulkPreviewBody');
            const bulkSummaryText = document.getElementById('bulkSummaryText');
            const bulkChargeRowsContainer = document.getElementById('bulkChargeRowsContainer');
            const bulkAddRowBtn = document.getElementById('bulkAddRowBtn');
            const bulkGenerateDepositBtn = document.getElementById('bulkGenerateDepositBtn');
            const bulkGenerateNoGuarantorDepositBtn = document.getElementById('bulkGenerateNoGuarantorDepositBtn');
            let bulkRows = [];
            const bulkTypeOptions = @json($typeOptions);

            const toMoney = (value, fallback = 0) => {
                const parsed = Number.parseFloat(String(value ?? '').replace(/,/g, ''));
                if (!Number.isFinite(parsed)) {
                    return fallback;
                }

                return Math.round(parsed * 100) / 100;
            };

            const propertySetupForm = document.getElementById('propertySetupForm');
            if (propertySetupForm) {
                const propertySetupTenant = document.getElementById('propertySetupTenant');
                const propertySetupForceAssignment = document.getElementById('propertySetupForceAssignment');
                const propertySetupContractStartsAt = document.getElementById('propertySetupContractStartsAt');
                const propertySetupContractExpiresAt = document.getElementById('propertySetupContractExpiresAt');
                const propertySetupMonthlyRentPrice = document.getElementById('propertySetupMonthlyRentPrice');
                const propertySetupChargeDay = document.getElementById('propertySetupChargeDay');
                const propertySetupPlanInputs = document.getElementById('property-setup-plan-inputs');
                const propertySetupPlanTableBody = document.getElementById('propertySetupPlanTableBody');
                const propertySetupPlanSummary = document.getElementById('propertySetupPlanSummary');
                const propertySetupPlanRowsCount = document.getElementById('propertySetupPlanRowsCount');
                const propertySetupPlanEmptyState = document.getElementById('propertySetupPlanEmptyState');
                const initialPropertySetupPlan = @json($selectedProperty ? ($initialPropertySetupPlan ?? []) : []);

                const monthNames = [
                    'Enero',
                    'Febrero',
                    'Marzo',
                    'Abril',
                    'Mayo',
                    'Junio',
                    'Julio',
                    'Agosto',
                    'Septiembre',
                    'Octubre',
                    'Noviembre',
                    'Diciembre',
                ];

                const parseIsoDate = (value) => {
                    const stringValue = String(value || '').trim();
                    const parts = stringValue.split('-');
                    if (parts.length !== 3) {
                        return null;
                    }

                    const year = Number.parseInt(parts[0], 10);
                    const month = Number.parseInt(parts[1], 10);
                    const day = Number.parseInt(parts[2], 10);
                    if (!year || month < 1 || month > 12 || day < 1 || day > 31) {
                        return null;
                    }

                    return { year, month, day };
                };
                const toDay = (value) => {
                    const parsed = Number.parseInt(String(value || ''), 10);
                    if (!Number.isInteger(parsed) || parsed < 1 || parsed > 31) {
                        return null;
                    }

                    return parsed;
                };

                const pad2 = (value) => String(value).padStart(2, '0');
                const periodKey = (year, month) => `${year}-${pad2(month)}`;
                const formatIsoDate = (year, month, day) => `${year}-${pad2(month)}-${pad2(day)}`;
                const buildConceptLabel = (periodMonth, periodYear) => {
                    const monthLabel = monthNames[periodMonth - 1] || String(periodMonth);
                    return `Renta ${monthLabel} ${periodYear}`;
                };

                const resolveDueDateForPeriod = (candidate, year, month, fallbackDay) => {
                    const parsedCandidate = parseIsoDate(candidate);
                    if (parsedCandidate && parsedCandidate.year === year && parsedCandidate.month === month) {
                        return formatIsoDate(year, month, parsedCandidate.day);
                    }

                    const daysInMonth = new Date(year, month, 0).getDate();
                    return formatIsoDate(year, month, Math.min(Math.max(1, fallbackDay), daysInMonth));
                };

                const normalizeExistingPlanRows = (rows) => {
                    if (!Array.isArray(rows)) {
                        return [];
                    }

                    return rows
                        .map((row) => {
                            const month = Number.parseInt(row?.period_month, 10);
                            const year = Number.parseInt(row?.period_year, 10);
                            if (!month || !year || month < 1 || month > 12) {
                                return null;
                            }

                            return {
                                period_month: month,
                                period_year: year,
                                due_date: String(row?.due_date || ''),
                                amount: toMoney(row?.amount, 0),
                                concept: String(row?.concept || '').trim(),
                                notes: row?.notes ? String(row.notes) : null,
                                is_custom_amount: Boolean(row?.is_custom_amount),
                            };
                        })
                        .filter(Boolean);
                };

                let propertySetupPlanRows = normalizeExistingPlanRows(initialPropertySetupPlan);

                const syncPropertySetupChargeDayFromContract = () => {
                    if (!propertySetupChargeDay) {
                        return;
                    }

                    const starts = parseIsoDate(propertySetupContractStartsAt?.value);
                    if (!starts) {
                        return;
                    }

                    const currentDay = toDay(propertySetupChargeDay.value);
                    if (currentDay !== null) {
                        return;
                    }

                    propertySetupChargeDay.value = String(starts.day);
                };

                const buildAutoPropertySetupPlan = () => {
                    const starts = parseIsoDate(propertySetupContractStartsAt?.value);
                    const expires = parseIsoDate(propertySetupContractExpiresAt?.value);
                    const currentMonthlyRentPrice = toMoney(propertySetupMonthlyRentPrice?.value, 0);
                    const setupChargeDay = toDay(propertySetupChargeDay?.value) ?? starts?.day ?? null;
                    if (!starts || !expires || currentMonthlyRentPrice <= 0 || !setupChargeDay) {
                        return [];
                    }

                    const startsDate = new Date(starts.year, starts.month - 1, 1);
                    const expiresDate = new Date(expires.year, expires.month - 1, 1);
                    if (startsDate > expiresDate) {
                        return [];
                    }

                    const baseContractDay = setupChargeDay;
                    const existingByPeriod = new Map(
                        propertySetupPlanRows.map((row) => [periodKey(row.period_year, row.period_month), row]),
                    );
                    const builtRows = [];
                    const cursor = new Date(startsDate.getFullYear(), startsDate.getMonth(), 1);

                    while (cursor <= expiresDate) {
                        const year = cursor.getFullYear();
                        const month = cursor.getMonth() + 1;
                        const key = periodKey(year, month);
                        const current = existingByPeriod.get(key);
                        const customAmount = Boolean(current?.is_custom_amount);
                        const amount = customAmount
                            ? toMoney(current?.amount, currentMonthlyRentPrice)
                            : currentMonthlyRentPrice;
                        const dueDate = resolveDueDateForPeriod(current?.due_date, year, month, baseContractDay);
                        const concept = (current?.concept || '').trim() || buildConceptLabel(month, year);

                        if (amount > 0) {
                            builtRows.push({
                                period_month: month,
                                period_year: year,
                                due_date: dueDate,
                                amount,
                                concept,
                                notes: current?.notes || null,
                                is_custom_amount: customAmount,
                            });
                        }

                        cursor.setMonth(cursor.getMonth() + 1);
                    }

                    return builtRows;
                };

                const syncPropertySetupPlanInputs = () => {
                    if (!propertySetupPlanInputs) {
                        return;
                    }

                    propertySetupPlanInputs.innerHTML = '';
                    propertySetupPlanRows.forEach((row, index) => {
                        const appendInput = (name, value) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `rent_charge_plan[${index}][${name}]`;
                            input.value = value;
                            propertySetupPlanInputs.appendChild(input);
                        };

                        appendInput('period_month', row.period_month);
                        appendInput('period_year', row.period_year);
                        appendInput('due_date', row.due_date);
                        appendInput('amount', toMoney(row.amount, 0).toFixed(2));
                        appendInput('concept', row.concept || '');
                        appendInput('is_custom_amount', row.is_custom_amount ? '1' : '0');
                        if (row.notes) {
                            appendInput('notes', row.notes);
                        }
                    });
                };

                const renderPropertySetupPlan = () => {
                    if (!propertySetupPlanTableBody) {
                        return;
                    }

                    propertySetupPlanTableBody.innerHTML = '';
                    if (!propertySetupPlanRows.length) {
                        if (propertySetupPlanEmptyState) {
                            propertySetupPlanTableBody.appendChild(propertySetupPlanEmptyState);
                        } else {
                            propertySetupPlanTableBody.innerHTML = `
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-8">No hay pagos configurados.</td>
                                    </tr>
                                `;
                        }
                    } else {
                        propertySetupPlanRows.forEach((row, index) => {
                            const tr = document.createElement('tr');

                            const periodCell = document.createElement('td');
                            periodCell.textContent = `${pad2(row.period_month)}/${row.period_year}`;
                            tr.appendChild(periodCell);

                            const dueDateCell = document.createElement('td');
                            const dueDateInput = document.createElement('input');
                            dueDateInput.type = 'date';
                            dueDateInput.className = 'form-control form-control-sm';
                            dueDateInput.value = row.due_date || '';
                            dueDateInput.dataset.planField = 'due_date';
                            dueDateInput.dataset.planIndex = String(index);
                            dueDateCell.appendChild(dueDateInput);
                            tr.appendChild(dueDateCell);

                            const amountCell = document.createElement('td');
                            const amountInput = document.createElement('input');
                            amountInput.type = 'number';
                            amountInput.min = '0.01';
                            amountInput.step = '0.01';
                            amountInput.className = 'form-control form-control-sm';
                            amountInput.value = toMoney(row.amount, 0).toFixed(2);
                            amountInput.dataset.planField = 'amount';
                            amountInput.dataset.planIndex = String(index);
                            amountCell.appendChild(amountInput);
                            tr.appendChild(amountCell);

                            const conceptCell = document.createElement('td');
                            const conceptInput = document.createElement('input');
                            conceptInput.type = 'text';
                            conceptInput.className = 'form-control form-control-sm';
                            conceptInput.maxLength = 190;
                            conceptInput.value = row.concept || '';
                            conceptInput.dataset.planField = 'concept';
                            conceptInput.dataset.planIndex = String(index);
                            conceptCell.appendChild(conceptInput);
                            tr.appendChild(conceptCell);

                            propertySetupPlanTableBody.appendChild(tr);
                        });
                    }

                    const total = propertySetupPlanRows.reduce((sum, row) => sum + toMoney(row.amount, 0), 0);
                    if (propertySetupPlanSummary) {
                        if (propertySetupPlanRows.length) {
                            propertySetupPlanSummary.textContent = `Total proyectado: $${total.toFixed(2)} en ${propertySetupPlanRows.length} cargos.`;
                        } else {
                            propertySetupPlanSummary.textContent = 'Configura contrato y renta mensual para generar la lista automatica.';
                        }
                    }
                    if (propertySetupPlanRowsCount) {
                        propertySetupPlanRowsCount.textContent = String(propertySetupPlanRows.length);
                    }
                };

                const rebuildPropertySetupPlan = () => {
                    propertySetupPlanRows = buildAutoPropertySetupPlan();
                    renderPropertySetupPlan();
                    syncPropertySetupPlanInputs();
                };

                propertySetupPlanTableBody?.addEventListener('change', (event) => {
                    const target = event.target.closest('[data-plan-field]');
                    if (!target) {
                        return;
                    }

                    const index = Number.parseInt(target.dataset.planIndex || '-1', 10);
                    if (!Number.isInteger(index) || !propertySetupPlanRows[index]) {
                        return;
                    }

                    const row = propertySetupPlanRows[index];
                    const field = target.dataset.planField;
                    if (field === 'amount') {
                        row.amount = toMoney(target.value, row.amount);
                        row.is_custom_amount = true;
                    } else if (field === 'due_date') {
                        row.due_date = String(target.value || '').trim();
                    } else if (field === 'concept') {
                        row.concept = String(target.value || '').trim();
                    }

                    syncPropertySetupPlanInputs();
                    renderPropertySetupPlan();
                });

                propertySetupContractStartsAt?.addEventListener('change', () => {
                    syncPropertySetupChargeDayFromContract();
                    rebuildPropertySetupPlan();
                });
                propertySetupContractExpiresAt?.addEventListener('change', rebuildPropertySetupPlan);
                propertySetupMonthlyRentPrice?.addEventListener('input', rebuildPropertySetupPlan);
                propertySetupMonthlyRentPrice?.addEventListener('change', rebuildPropertySetupPlan);
                propertySetupChargeDay?.addEventListener('input', rebuildPropertySetupPlan);
                propertySetupChargeDay?.addEventListener('change', rebuildPropertySetupPlan);
                syncPropertySetupChargeDayFromContract();
                rebuildPropertySetupPlan();

                propertySetupForm.addEventListener('submit', async (event) => {
                    syncPropertySetupPlanInputs();
                    if (propertySetupForceAssignment?.value === '1') {
                        return;
                    }

                    const selectedOption = propertySetupTenant?.options[propertySetupTenant.selectedIndex];
                    if (!selectedOption || !selectedOption.value) {
                        return;
                    }

                    let missing = [];
                    try {
                        missing = JSON.parse(selectedOption.dataset.missing || '[]');
                    } catch (error) {
                        missing = [];
                    }

                    if (!Array.isArray(missing) || !missing.length) {
                        return;
                    }

                    event.preventDefault();

                    const tenantName = selectedOption.textContent.trim();
                    const details = missing.join('\n- ');
                    const message = `El inquilino ${tenantName} tiene datos o documentos incompletos:\n- ${details}\n\n¿Deseas continuar con la asignacion?`;
                    let confirmed = false;

                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            title: 'Inquilino incompleto',
                            text: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Si, continuar',
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

                    if (propertySetupForceAssignment) {
                        propertySetupForceAssignment.value = '1';
                    }
                    propertySetupForm.submit();
                });
            }

            const syncBulkRowsInputs = () => {
                if (!bulkChargeRowsContainer) {
                    return;
                }

                bulkChargeRowsContainer.innerHTML = '';
                bulkRows.forEach((row, index) => {
                    const appendInput = (name, value) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `rows[${index}][${name}]`;
                        input.value = value;
                        bulkChargeRowsContainer.appendChild(input);
                    };

                    appendInput('type', row.type || 'rent');
                    appendInput('period_month', row.period_month);
                    appendInput('period_year', row.period_year);
                    appendInput('due_date', row.due_date);
                    appendInput('amount', toMoney(row.amount, 0).toFixed(2));
                    appendInput('concept', row.concept || '');
                    appendInput('notes', row.notes || '');
                });
            };

            const renderBulkRows = () => {
                if (!bulkPreviewBody) {
                    return;
                }

                bulkPreviewBody.innerHTML = '';
                if (!bulkRows.length) {
                    bulkPreviewBody.innerHTML = `
                            <tr>
                                <td colspan="9" class="text-center text-muted py-8">No se encontraron cargos para esta propiedad.</td>
                            </tr>
                        `;
                    bulkSummaryText.textContent = 'Total: 0 | Nuevos: 0 | Existentes: 0';
                    bulkPreviewContainer?.classList.remove('d-none');
                    syncBulkRowsInputs();
                    return;
                }

                const summary = {
                    total: bulkRows.length,
                    to_create: bulkRows.filter((row) => !row.already_exists).length,
                    already_exists: bulkRows.filter((row) => row.already_exists).length,
                };
                const typeOptionsHtml = Object.entries(bulkTypeOptions || {})
                    .map(([value, label]) => `<option value="${value}">${label}</option>`)
                    .join('');

                bulkRows.forEach((row, index) => {
                    const alreadyClass = row.already_exists ? 'badge-light-warning text-warning' : 'badge-light-success text-success';
                    const alreadyLabel = row.already_exists ? 'Ya existe' : 'Se creara';
                    bulkPreviewBody.insertAdjacentHTML('beforeend', `
                            <tr>
                                <td>${row.property_name}</td>
                                <td>${row.tenant_name || '-'}</td>
                                <td>
                                    <select class="form-select form-select-sm" data-bulk-row-index="${index}" data-bulk-field="type">
                                        ${typeOptionsHtml}
                                    </select>
                                </td>
                                <td>${String(row.period_month).padStart(2, '0')}/${row.period_year}</td>
                                <td>
                                    <input type="date" class="form-control form-control-sm"
                                        data-bulk-row-index="${index}" data-bulk-field="due_date"
                                        value="${row.due_date || ''}">
                                </td>
                                <td>
                                    <input type="number" min="0.01" step="0.01" class="form-control form-control-sm"
                                        data-bulk-row-index="${index}" data-bulk-field="amount"
                                        value="${toMoney(row.amount, 0).toFixed(2)}">
                                </td>
                                <td>
                                    <input type="text" maxlength="190" class="form-control form-control-sm"
                                        data-bulk-row-index="${index}" data-bulk-field="concept"
                                        value="${row.concept || ''}">
                                </td>
                                <td><span class="badge ${alreadyClass}">${alreadyLabel}</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-light-danger"
                                        data-bulk-row-index="${index}" data-bulk-remove="1">Quitar</button>
                                </td>
                            </tr>
                        `);
                });
                bulkPreviewBody.querySelectorAll('select[data-bulk-field="type"]').forEach((selectEl) => {
                    const index = Number.parseInt(selectEl.getAttribute('data-bulk-row-index') || '-1', 10);
                    if (!Number.isInteger(index) || !bulkRows[index]) {
                        return;
                    }
                    selectEl.value = bulkRows[index].type || 'rent';
                });

                bulkSummaryText.textContent = `Total: ${summary.total} | Nuevos: ${summary.to_create} | Existentes: ${summary.already_exists}`;
                bulkPreviewContainer?.classList.remove('d-none');
                syncBulkRowsInputs();
            };

            const resetBulkPreview = () => {
                bulkRows = [];
                if (bulkPreviewBody) {
                    bulkPreviewBody.innerHTML = '';
                }
                bulkPreviewContainer?.classList.add('d-none');
                syncBulkRowsInputs();
            };

            const parseDateDay = (value) => {
                const stringValue = String(value || '').trim();
                const parts = stringValue.split('-');
                if (parts.length !== 3) {
                    return null;
                }

                const day = Number.parseInt(parts[2], 10);
                if (!Number.isInteger(day) || day < 1 || day > 31) {
                    return null;
                }

                return day;
            };

            const normalizeChargeDay = (value) => {
                const day = Number.parseInt(String(value || ''), 10);
                if (!Number.isInteger(day) || day < 1 || day > 31) {
                    return null;
                }

                return day;
            };

            const syncBulkChargeDayFromContract = () => {
                if (!bulkChargeDay) {
                    return;
                }

                const currentDay = normalizeChargeDay(bulkChargeDay.value);
                if (currentDay !== null) {
                    return;
                }

                const startsDay = parseDateDay(bulkContractStartsAt?.value);
                if (startsDay !== null) {
                    bulkChargeDay.value = String(startsDay);
                }
            };

            const applyBulkPropertyDefaults = () => {
                if (!bulkPropertyId) {
                    return;
                }

                const selected = bulkPropertyId.options[bulkPropertyId.selectedIndex];
                if (!selected) {
                    return;
                }

                if (bulkTenantName) {
                    bulkTenantName.value = selected.dataset?.tenantName || '';
                }
                if (bulkContractStartsAt) {
                    bulkContractStartsAt.value = selected.dataset?.contractStart || '';
                }
                if (bulkContractExpiresAt) {
                    bulkContractExpiresAt.value = selected.dataset?.contractExpires || '';
                }
                if (bulkMonthlyRentPrice) {
                    bulkMonthlyRentPrice.value = selected.dataset?.monthlyRent || '';
                }
                if (bulkChargeDay) {
                    const dayFromProperty = normalizeChargeDay(selected.dataset?.chargeDay);
                    bulkChargeDay.value = dayFromProperty !== null ? String(dayFromProperty) : '';
                    if (dayFromProperty === null) {
                        syncBulkChargeDayFromContract();
                    }
                }
                if (bulkChargeToleranceDays) {
                    bulkChargeToleranceDays.value = String(selected.dataset?.chargeToleranceDays || '0');
                }
            };

            const parsePeriodFromDate = (dateValue) => {
                const parsed = new Date(`${dateValue}T00:00:00`);
                if (Number.isNaN(parsed.getTime())) {
                    const now = new Date();
                    return {
                        period_month: now.getMonth() + 1,
                        period_year: now.getFullYear(),
                    };
                }

                return {
                    period_month: parsed.getMonth() + 1,
                    period_year: parsed.getFullYear(),
                };
            };

            const createManualBulkRow = ({ amountMultiplier = 1, conceptPrefix = 'Renta', type = 'rent' } = {}) => {
                const selected = bulkPropertyId?.options[bulkPropertyId.selectedIndex];
                const dueDate = String(bulkContractStartsAt?.value || '').trim() || new Date().toISOString().slice(0, 10);
                const { period_month, period_year } = parsePeriodFromDate(dueDate);
                const monthlyRent = toMoney(bulkMonthlyRentPrice?.value, 0);
                const amountBase = monthlyRent > 0 ? monthlyRent : 0;
                const amount = amountBase > 0 ? amountBase * amountMultiplier : 0.01;
                const tenantName = selected?.dataset?.tenantName || bulkTenantName?.value || '-';

                return {
                    property_name: selected?.textContent?.trim() || '-',
                    tenant_name: tenantName,
                    type,
                    period_month,
                    period_year,
                    due_date: dueDate,
                    amount,
                    concept: `${conceptPrefix} ${String(period_month).padStart(2, '0')}/${period_year}`,
                    notes: null,
                    already_exists: false,
                };
            };

            bulkPropertyId?.addEventListener('change', () => {
                applyBulkPropertyDefaults();
                resetBulkPreview();
            });

            if (bulkTenantName && bulkPropertyId) {
                const selected = bulkPropertyId.options[bulkPropertyId.selectedIndex];
                bulkTenantName.value = selected?.dataset?.tenantName || '';
            }
            syncBulkChargeDayFromContract();

            bulkPreviewBody?.addEventListener('change', (event) => {
                const input = event.target.closest('[data-bulk-field]');
                if (!input) {
                    return;
                }

                const index = Number.parseInt(input.getAttribute('data-bulk-row-index') || '-1', 10);
                if (!Number.isInteger(index) || !bulkRows[index]) {
                    return;
                }

                const field = input.getAttribute('data-bulk-field');
                if (field === 'amount') {
                    bulkRows[index].amount = toMoney(input.value, bulkRows[index].amount);
                } else if (field === 'due_date') {
                    bulkRows[index].due_date = String(input.value || '').trim();
                    const period = parsePeriodFromDate(bulkRows[index].due_date);
                    bulkRows[index].period_month = period.period_month;
                    bulkRows[index].period_year = period.period_year;
                } else if (field === 'concept') {
                    bulkRows[index].concept = String(input.value || '').trim();
                } else if (field === 'type') {
                    bulkRows[index].type = String(input.value || 'rent');
                }
                bulkRows[index].already_exists = false;

                syncBulkRowsInputs();
                renderBulkRows();
            });

            bulkPreviewBody?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-bulk-remove="1"]');
                if (!button) {
                    return;
                }

                const index = Number.parseInt(button.getAttribute('data-bulk-row-index') || '-1', 10);
                if (!Number.isInteger(index) || !bulkRows[index]) {
                    return;
                }

                bulkRows.splice(index, 1);
                renderBulkRows();
            });

            bulkAddRowBtn?.addEventListener('click', () => {
                bulkRows.push(createManualBulkRow());
                renderBulkRows();
            });

            bulkGenerateDepositBtn?.addEventListener('click', () => {
                bulkRows.push(createManualBulkRow({
                    amountMultiplier: 1,
                    conceptPrefix: 'Deposito en garantia',
                    type: 'deposit_adjustment',
                }));
                renderBulkRows();
            });

            bulkGenerateNoGuarantorDepositBtn?.addEventListener('click', () => {
                bulkRows.push(createManualBulkRow({
                    amountMultiplier: 2,
                    conceptPrefix: 'Deposito sin aval',
                    type: 'deposit_adjustment',
                }));
                renderBulkRows();
            });

            bulkContractStartsAt?.addEventListener('change', () => {
                syncBulkChargeDayFromContract();
                resetBulkPreview();
            });
            bulkContractExpiresAt?.addEventListener('change', resetBulkPreview);
            bulkMonthlyRentPrice?.addEventListener('input', resetBulkPreview);
            bulkMonthlyRentPrice?.addEventListener('change', resetBulkPreview);
            bulkChargeDay?.addEventListener('input', resetBulkPreview);
            bulkChargeDay?.addEventListener('change', resetBulkPreview);
            bulkChargeToleranceDays?.addEventListener('input', resetBulkPreview);
            bulkChargeToleranceDays?.addEventListener('change', resetBulkPreview);

            previewBtn?.addEventListener('click', async () => {
                const propertyId = bulkPropertyId?.value;

                if (!propertyId) {
                    alert('Selecciona una propiedad.');
                    return;
                }

                previewBtn.disabled = true;
                previewBtn.textContent = 'Cargando...';

                try {
                    syncBulkChargeDayFromContract();
                    const response = await fetch("{{ route('charges.bulk.preview') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            property_id: propertyId,
                            contract_starts_at: bulkContractStartsAt?.value || null,
                            contract_expires_at: bulkContractExpiresAt?.value || null,
                            monthly_rent_price: bulkMonthlyRentPrice?.value || null,
                            charge_day: bulkChargeDay?.value || null,
                            charge_tolerance_days: bulkChargeToleranceDays?.value || null,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data?.message || 'No fue posible generar la vista previa.');
                    }

                    const preview = data.preview || {};
                    bulkRows = Array.isArray(preview.rows) ? preview.rows : [];
                    renderBulkRows();
                } catch (error) {
                    alert(error.message || 'No fue posible generar la vista previa.');
                } finally {
                    previewBtn.disabled = false;
                    previewBtn.textContent = 'Ver lista de pagos';
                }
            });

            bulkChargeForm?.addEventListener('submit', () => {
                syncBulkChargeDayFromContract();
                syncBulkRowsInputs();
            });
        })();
    </script>

    @if ($canManageCharges && $errors->createCharge->any())
        <script>
            (() => {
                const modalEl = document.getElementById('createChargeModal');
                if (!modalEl) return;
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif

    @if ($canManageCharges && $errors->registerPayment->any())
        <script>
            (() => {
                const modalEl = document.getElementById('registerPaymentModal');
                if (!modalEl) return;

                const form = document.getElementById('registerPaymentForm');
                const chargeUuid = @json(old('charge_uuid'));
                if (form && chargeUuid) {
                    form.setAttribute('action', "{{ url('/cobranza') }}/" + chargeUuid + "/pagos");
                }
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif

    @if ($canManageCharges && $errors->updateCharge->any())
        <script>
            (() => {
                const modalEl = document.getElementById('editChargeModal');
                if (!modalEl) return;

                const form = document.getElementById('editChargeForm');
                const chargeUuid = @json(old('charge_uuid'));
                if (form && chargeUuid) {
                    form.setAttribute('action', "{{ url('/cobranza') }}/" + chargeUuid);
                }

                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif

    @if ($canManageCharges && $errors->generateCharges->any())
        <script>
            (() => {
                const modalEl = document.getElementById('bulkChargeModal');
                if (!modalEl) return;
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif
@endpush
