@extends('layouts.app')

@section('title', 'Control de Propiedades | SuWork')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/assets/css/propiedades.css') }}">
    <style>
        .property-control-module {
            --pc-surface: #ffffff;
            --pc-bg: #f5f7fb;
            --pc-ink: #172033;
            --pc-text: #334155;
            --pc-muted: #7b879d;
            --pc-line: #e5eaf3;
            --pc-accent: #b54708;
            --pc-accent-strong: #9a3412;
            --pc-accent-soft: #fff1e8;
            --pc-success: #15803d;
            --pc-success-soft: #edfdf3;
            --pc-danger: #c2410c;
            --pc-danger-soft: #fff3ee;
            --pc-warning: #b45309;
            --pc-warning-soft: #fff7e8;
            --pc-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            color: var(--pc-text);
        }

        .property-control-module .property-tabs-wrap {
            border-color: var(--pc-line);
            box-shadow: var(--pc-shadow);
        }

        .property-control-module .property-tabs-nav {
            gap: 12px;
        }

        .property-control-module .property-tabs-nav .nav-link {
            background: #f8fafc;
            color: var(--pc-text);
            border: 1px solid transparent;
            padding: 12px 18px;
        }

        .property-control-module .property-tabs-nav .nav-link:hover {
            background: var(--pc-accent-soft);
            color: var(--pc-accent);
            border-color: rgba(181, 71, 8, 0.15);
        }

        .property-control-module .property-tabs-nav .nav-link.active {
            background: var(--pc-accent);
            color: #fff !important;
            box-shadow: 0 12px 28px rgba(181, 71, 8, 0.22);
        }

        .property-control-module .property-tabs-nav__count {
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
            font-weight: 700;
        }

        .property-control-module .property-tabs-nav .nav-link.active .property-tabs-nav__count {
            background: rgba(255, 255, 255, 0.18);
        }

        .property-control-hero {
            border: 0;
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 34%),
                linear-gradient(135deg, #111827 0%, #9a3412 100%);
            box-shadow: var(--pc-shadow);
        }

        .property-control-hero .card-body {
            position: relative;
        }

        .property-control-hero__eyebrow {
            color: var(--pc-muted);
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .property-control-hero__percent {
            display: inline-flex;
            align-items: baseline;
            gap: 4px;
            padding: 10px 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.12);
            color: var(--pc-muted);
            line-height: 1;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
        }

        .property-control-hero__percent strong {
            font-size: clamp(2.6rem, 5vw, 4.35rem);
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .property-control-hero__percent span {
            font-size: 1.25rem;
            font-weight: 700;
            opacity: 0.82;
        }

        .property-control-hero__description {
            color: var(--pc-muted);
            font-size: 1rem;
            max-width: 22rem;
        }

        .property-control-hero__ring {
            width: 108px;
            height: 108px;
            border-radius: 50%;
            border: 8px solid rgba(255, 255, 255, 0.18);
            border-top-color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.08);
            color: var(--pc-muted);
            font-weight: 800;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .property-control-summary-card {
            border: 1px solid var(--pc-line);
            border-radius: 22px;
            background: var(--pc-surface);
            box-shadow: var(--pc-shadow);
        }

        .property-control-summary-card.is-success {
            background: linear-gradient(180deg, #ffffff 0%, #f4fbf7 100%);
            border-color: rgba(21, 128, 61, 0.18);
        }

        .property-control-summary-card.is-danger {
            background: linear-gradient(180deg, #ffffff 0%, #fff7f2 100%);
            border-color: rgba(194, 65, 12, 0.18);
        }

        .property-control-summary-card__label {
            color: var(--pc-muted);
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .property-control-summary-card__value {
            color: var(--pc-ink);
            font-size: clamp(2rem, 3vw, 2.8rem);
            font-weight: 800;
            line-height: 1;
        }

        .property-control-summary-card__note {
            color: var(--pc-muted);
            font-size: 0.95rem;
            font-weight: 600;
        }

        .property-control-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .property-control-search {
            position: relative;
            min-width: min(100%, 360px);
            flex: 1 1 300px;
        }

        .property-control-search i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--pc-muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .property-control-search .form-control {
            height: 52px;
            padding-left: 46px;
            border-radius: 16px;
            border: 1px solid var(--pc-line);
            background: #fbfcfe;
            color: var(--pc-ink);
            font-weight: 600;
            box-shadow: none;
        }

        .property-control-search .form-control:focus {
            border-color: rgba(181, 71, 8, 0.35);
            box-shadow: 0 0 0 4px rgba(181, 71, 8, 0.08);
        }

        .property-control-results {
            color: var(--pc-muted);
            font-size: 1rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .property-control-table-card {
            margin-top: 20px;
            border: 1px solid var(--pc-line);
            border-radius: 20px;
            overflow: hidden;
            background: var(--pc-surface);
        }

        .property-control-table-card .table-responsive {
            overflow-x: auto;
        }

        .property-control-table-card table.dataTable {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0;
        }

        .property-control-table-card thead th {
            padding-top: 20px;
            padding-bottom: 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--pc-line) !important;
            color: #94a3b8 !important;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
        }

        .property-control-row {
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .property-control-row td {
            padding-top: 10px;
            padding-bottom: 06px;
            border-top: 1px solid var(--pc-line) !important;
            vertical-align: middle;
            background: #fff;
        }

        .property-control-row:hover td {
            background: #fcf8f6;
        }

        .property-control-row.is-expanded td {
            background: #fff8f2;
        }

        .property-control-property {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .property-control-expander {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            color: var(--pc-muted);
            border: 1px solid var(--pc-line);
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .property-control-row:hover .property-control-expander,
        .property-control-row.is-expanded .property-control-expander {
            color: var(--pc-accent);
            border-color: rgba(181, 71, 8, 0.18);
            background: var(--pc-accent-soft);
        }

        .property-control-row.is-expanded .property-control-expander i {
            transform: rotate(90deg);
        }

        .property-control-expander i {
            transition: transform 0.2s ease;
        }

        .property-control-property__title {
            color: var(--pc-ink);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .property-control-property__address {
            color: var(--pc-muted);
            font-size: 0.88rem;
            margin-top: 4px;
            line-height: 1.4;
        }

        .property-control-inline-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .property-control-inline-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #f8fafc;
            color: var(--pc-text);
            font-size: 0.68rem;
            font-weight: 700;
        }

        .property-control-party__label {
            color: var(--pc-muted);
            font-size: 0.73rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .property-control-party__value {
            color: var(--pc-ink);
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .property-control-party__value.is-missing {
            color: var(--pc-danger);
        }

        .property-control-progress__fraction {
            color: var(--pc-ink);
            font-size: 1.05rem;
            font-weight: 800;
        }

        .property-control-actions {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .property-control-actions .btn {
            border-radius: 12px;
            font-weight: 700;
            min-width: 76px;
        }

        .property-control-action-mobile {
            display: none;
        }

        .property-control-child-row td {
            padding: 0 !important;
            border-top: 0 !important;
            background: #fffaf5 !important;
        }

        .property-control-child {
            padding: 0 28px 24px;
        }

        .property-control-detail {
            border: 1px solid rgba(181, 71, 8, 0.12);
            border-radius: 20px;
            background: linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
            padding: 24px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
        }

        .property-control-detail-card {
            border: 1px solid var(--pc-line);
            border-radius: 16px;
            background: #fff;
            padding: 18px;
        }

        .property-control-check {
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
        }

        .property-control-check i {
            font-size: 1rem;
        }

        .property-control-check.is-complete {
            border-color: rgba(21, 128, 61, 0.08);
            background: var(--pc-success-soft);
        }

        .property-control-check.is-missing {
            border-color: rgba(148, 163, 184, 0.14);
            color: var(--pc-text);
            background: #fffaf5;
        }

        .property-control-table-card .dataTables_info,
        .property-control-table-card .dataTables_paginate {
            padding: 18px 28px 0;
            color: var(--pc-muted) !important;
            font-weight: 700;
        }

        .property-control-table-card .dataTables_paginate .pagination {
            gap: 6px;
        }

        .property-control-table-card .page-link {
            border-radius: 10px !important;
            border-color: var(--pc-line) !important;
            color: var(--pc-text) !important;
            min-width: 38px;
            text-align: center;
            font-weight: 700;
        }

        .property-control-table-card .page-item.active .page-link {
            background: var(--pc-accent) !important;
            border-color: var(--pc-accent) !important;
            color: #fff !important;
        }

        @media (max-width: 991px) {
            .property-control-hero__ring {
                width: 86px;
                height: 86px;
                font-size: 1rem;
            }

            .property-control-detail {
                padding: 18px;
            }

            .property-control-child {
                padding: 0 14px 16px;
            }

            .property-control-table-card .dataTables_info,
            .property-control-table-card .dataTables_paginate {
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        @media (max-width: 767.98px) {
            .property-control-module {
                --pc-card-radius: 8px;
            }

            .property-control-module.py-10 {
                padding-top: 1.25rem !important;
                padding-bottom: 1.25rem !important;
            }

            .property-control-module > .d-flex.flex-wrap.justify-content-between {
                align-items: flex-start !important;
                gap: 8px !important;
                margin-bottom: 18px !important;
            }

            .property-control-module h1 {
                font-size: 1.35rem;
                line-height: 1.2;
                overflow-wrap: anywhere;
            }

            .property-control-module h1 + .text-muted {
                font-size: 0.84rem !important;
                line-height: 1.35;
            }

            .property-control-module > .d-flex.flex-wrap.justify-content-between > .text-muted {
                width: 100%;
                font-size: 0.78rem;
            }

            .property-control-summary-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                margin-bottom: 18px !important;
            }

            .property-control-summary-grid > [class*="col-"] {
                width: auto;
                max-width: none;
                padding: 0 !important;
            }

            .property-control-summary-grid > .col-xl-5 {
                grid-column: 1 / -1;
            }

            .property-control-hero,
            .property-control-summary-card {
                border-radius: var(--pc-card-radius);
                min-width: 0;
            }

            .property-control-hero .card-body {
                padding: 16px !important;
            }

            .property-control-hero__eyebrow {
                margin-bottom: 10px !important;
                font-size: 0.74rem;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .property-control-hero__percent {
                border-radius: 8px;
                padding: 8px 10px;
            }

            .property-control-hero__percent strong {
                font-size: clamp(2rem, 12vw, 3rem);
                letter-spacing: 0;
            }

            .property-control-hero__percent span {
                font-size: 1rem;
            }

            .property-control-hero__description {
                max-width: none;
                font-size: 0.86rem;
                line-height: 1.4;
            }

            .property-control-summary-card .card-body {
                min-width: 0;
                padding: 14px !important;
            }

            .property-control-summary-card__label {
                min-height: 2rem;
                margin-bottom: 8px !important;
                font-size: 0.68rem;
                line-height: 1.2;
                letter-spacing: 0.03em;
                text-transform: uppercase;
            }

            .property-control-summary-card__value {
                font-size: clamp(1.45rem, 8vw, 2rem);
                overflow-wrap: anywhere;
            }

            .property-control-summary-card__note {
                margin-top: 8px !important;
                font-size: 0.72rem;
                line-height: 1.3;
                overflow-wrap: anywhere;
            }

            .property-control-module .property-tabs-wrap {
                padding: 12px;
                border: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
                overflow: visible;
            }

            .property-control-toolbar {
                gap: 10px;
                margin-bottom: 14px;
            }

            .property-control-search {
                flex-basis: 100%;
                min-width: 0;
            }

            .property-control-search .form-control {
                height: 46px;
                border-radius: 8px;
                font-size: 0.86rem;
            }

            .property-control-results {
                width: 100%;
                font-size: 0.8rem;
                white-space: normal;
            }

            .property-control-module .property-tabs-nav {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .property-control-module .property-tabs-nav .nav-item,
            .property-control-module .property-tabs-nav .nav-link {
                width: 100%;
            }

            .property-control-module .property-tabs-nav .nav-link {
                justify-content: center;
                min-height: 44px;
                border-radius: 8px;
                padding: 9px 10px;
                font-size: 0.78rem;
                line-height: 1.2;
                text-align: center;
                white-space: normal;
            }

            .property-control-module .property-tabs-nav__count {
                min-width: 24px;
                height: 24px;
                font-size: 0.7rem;
            }

            .property-tab-pane {
                padding: 14px 0 0 !important;
            }

            .property-control-table-card {
                margin-top: 0;
                border: 0;
                border-radius: 0;
                overflow: visible;
                background: transparent;
            }

            .property-control-table-card .table-responsive {
                overflow: visible;
            }

            .property-control-table-card table,
            .property-control-table-card table.dataTable,
            .property-control-table-card tbody {
                display: block;
                width: 100% !important;
            }

            .property-control-table-card thead {
                display: none;
            }

            .property-control-table-card tbody {
                display: grid;
                gap: 14px;
            }

            .property-control-row {
                display: block;
                padding: 18px;
                border: 1px solid #e8eef7;
                border-radius: var(--pc-card-radius);
                background: #fff !important;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
                overflow: hidden;
            }

            .property-control-row.is-expanded {
                border-bottom-right-radius: 0;
                border-bottom-left-radius: 0;
            }

            .property-control-table-card table:not(.table-bordered) tr.property-control-row {
                padding: 18px !important;
            }

            .property-control-row:hover td,
            .property-control-row.is-expanded td {
                background: transparent;
            }

            .property-control-row td {
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

            .property-control-row td::before {
                flex: 0 0 82px;
                color: #8b96b2;
                font-size: 0.66rem;
                font-weight: 800;
                letter-spacing: 0.04em;
                line-height: 1.25;
                text-align: left;
                text-transform: uppercase;
            }

            .property-control-row td:nth-child(1) {
                display: block;
                padding-top: 0 !important;
                padding-bottom: 14px !important;
                border-top: 0 !important;
                text-align: left !important;
            }

            .property-control-row td:nth-child(1)::before,
            .property-control-row td:nth-child(3)::before {
                content: none;
            }

            .property-control-row td:nth-child(2)::before {
                content: 'Asesor';
            }

            .property-control-row td:nth-child(3) {
                margin-top: 6px;
                padding-top: 14px !important;
                border-top: 1px solid #e8eef7 !important;
            }

            .property-control-property {
                gap: 12px;
            }

            .property-control-expander {
                width: 34px;
                height: 34px;
                border-radius: 8px;
            }

            .property-control-property__body {
                min-width: 0;
            }

            .property-control-property__title {
                font-size: 1rem;
                line-height: 1.25;
                overflow-wrap: anywhere;
            }

            .property-control-inline-meta {
                margin-top: 8px;
            }

            .property-control-inline-meta span {
                border-radius: 8px;
                font-size: 0.72rem;
                line-height: 1.25;
                white-space: normal;
            }

            .property-control-party__label {
                display: none;
            }

            .property-control-party__value {
                max-width: 58%;
                min-width: 0;
                font-size: 0.84rem;
                line-height: 1.35;
                overflow-wrap: anywhere;
                text-align: right;
            }

            .property-control-actions {
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;
                width: 100%;
            }

            .property-control-action-desktop {
                display: none;
            }

            .property-control-action-mobile {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
            }

            .property-control-actions .btn {
                width: 100%;
                min-width: 0;
                border-radius: 8px;
                padding: 9px 10px;
                font-size: 0.8rem;
                line-height: 1.2;
            }

            .property-control-child-row {
                display: block;
                margin-top: -14px;
                border: 1px solid #e8eef7;
                border-top: 0;
                border-radius: 0 0 var(--pc-card-radius) var(--pc-card-radius);
                background: #fffaf5 !important;
                overflow: hidden;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
            }

            .property-control-child-row td {
                display: block;
                width: 100%;
                background: transparent !important;
            }

            .property-control-child {
                padding: 0 12px 12px;
            }

            .property-control-detail {
                border-radius: 8px;
                padding: 12px;
            }

            .property-control-detail-card {
                border-radius: 8px;
                padding: 12px;
            }

            .property-control-check {
                border-radius: 8px;
                padding: 10px 12px;
                font-size: 0.82rem;
                line-height: 1.3;
            }

            .property-control-table-card .dataTables_info {
                padding: 14px 2px 0;
                font-size: 0.78rem;
                text-align: center;
            }

            .property-control-table-card .dataTables_paginate {
                padding: 12px 2px 0;
            }

            .property-control-table-card .dataTables_paginate .pagination {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 property-control-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Control de Alta de Propiedades</h1>
                <div class="text-muted fs-6">Seguimiento de configuración operativa y documental</div>
            </div>
            <div class="text-muted fw-semibold">{{ now()->translatedFormat('d M Y') }}</div>
        </div>

        <div class="row g-5 mb-8 property-control-summary-grid">
            <div class="col-xl-5">
                <div class="card h-100 property-control-hero">
                    <div class="card-body p-8 p-xl-10">
                        <div class="property-control-hero__eyebrow mb-4">Avance general del sistema</div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-6">
                            <div>
                                <div class="property-control-hero__percent mb-4">
                                    <strong>{{ $summary['overall_progress'] }}</strong>
                                    <span>%</span>
                                </div>
                                <div class="property-control-hero__description">
                                    {{ $summary['complete'] }} de {{ $summary['total'] }} propiedades ya están configuradas correctamente.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-2">
                <div class="card h-100 property-control-summary-card">
                    <div class="card-body p-7">
                        <div class="property-control-summary-card__label mb-3">Total propiedades</div>
                        <div class="property-control-summary-card__value">{{ $summary['total'] }}</div>
                        <div class="property-control-summary-card__note mt-3">Base operativa auditada</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-2">
                <div class="card h-100 property-control-summary-card is-success">
                    <div class="card-body p-7">
                        <div class="property-control-summary-card__label mb-3">Completas</div>
                        <div class="property-control-summary-card__value text-success">{{ $summary['complete'] }}</div>
                        <div class="property-control-summary-card__note mt-3">Sin pendientes críticos</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 property-control-summary-card is-danger">
                    <div class="card-body p-7">
                        <div class="property-control-summary-card__label mb-3">Incompletas</div>
                        <div class="property-control-summary-card__value text-danger">{{ $summary['incomplete'] }}</div>
                        <div class="property-control-summary-card__note mt-3">
                            {{ $summary['without_advisor'] }} propiedades aún sin asesor asignado
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="property-tabs-wrap">
            <div class="property-control-toolbar">
                <label class="property-control-search mb-0" for="property_control_search">
                    <i class="bi bi-search"></i>
                    <input
                        id="property_control_search"
                        type="search"
                        class="form-control"
                        value="{{ $filters['q'] }}"
                        placeholder="Buscar propiedad, asesor o inquilino..."
                        autocomplete="off">
                </label>

                <div id="propertyControlResultCount" class="property-control-results">{{ $resultCount }} resultados</div>
            </div>

            <ul class="nav property-tabs-nav" id="propertyControlStatusTabs" role="tablist">
                @foreach ($statusOptions as $value => $label)
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link {{ $filters['status'] === $value ? 'active' : '' }}"
                            type="button"
                            role="tab"
                            aria-selected="{{ $filters['status'] === $value ? 'true' : 'false' }}"
                            data-status-filter="{{ $value }}">
                            <span>{{ $label }}</span>
                            <span class="property-tabs-nav__count">{{ $statusCounts[$value] ?? 0 }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="property-tab-pane pt-6">
                <div class="property-control-table-card">
                    <div class="table-responsive">
                        <table id="property_control_table" class="table table-row-bordered align-middle mb-0">
                            <thead>
                                <tr class="fw-bold text-muted text-uppercase fs-8">
                                    <th class="ps-7 min-w-280px">Propiedad</th>
                                    <th class="min-w-220px">Responsables</th>
                                    <th class="min-w-100px text-end pe-7">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($snapshots as $row)
                                    @php
                                        $property = $row['property'];
                                        $rowFilters = ['all'];

                                        if ($row['is_complete']) {
                                            $rowFilters[] = 'complete';
                                        } else {
                                            $rowFilters[] = 'incomplete';
                                        }

                                        if (!($row['checks']['advisor'] ?? false)) {
                                            $rowFilters[] = 'no_advisor';
                                        }

                                        if (!($row['checks']['contract'] ?? false)) {
                                            $rowFilters[] = 'no_contract';
                                        }

                                        if (!($row['checks']['charges'] ?? false)) {
                                            $rowFilters[] = 'no_charges';
                                        }

                                        if ($row['has_dossier_gap']) {
                                            $rowFilters[] = 'no_dossier';
                                        }

                                    @endphp
                                    <tr
                                        class="property-control-row"
                                        data-status-filters="{{ implode(' ', $rowFilters) }}"
                                        tabindex="0"
                                        role="button"
                                        aria-expanded="false">
                                        <td class="ps-7">
                                            <div class="property-control-property">
                                                <span class="property-control-expander">
                                                    <i class="bi bi-chevron-right"></i>
                                                </span>

                                                <div class="property-control-property__body">
                                                    <div class="property-control-property__title">{{ $property->internal_name }}</div>

                                                    @if ($row['tenant_name'])
                                                        <div class="property-control-inline-meta">
                                                            <span>
                                                                <i class="bi bi-person-badge"></i>
                                                                {{ $row['tenant_name'] }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="property-control-party__label">Asesor responsable</div>
                                            <div class="property-control-party__value {{ $row['advisor_name'] ? '' : 'is-missing' }}">
                                                {{ $row['advisor_name'] ?: 'Sin asesor asignado' }}
                                            </div>
                                        </td>
                                        <td class="text-end pe-7">
                                            <div class="property-control-actions">
                                                <a href="{{ route('properties.show', $property) }}"
                                                    class="btn btn-sm btn-primary js-property-control-action property-control-action-desktop">
                                                    Ver
                                                </a>
                                                <a href="{{ route('properties.show', $property) }}"
                                                    class="btn btn-sm btn-primary js-property-control-action property-control-action-mobile">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                    Ir a la propiedad
                                                </a>
                                            </div>

                                            <script type="text/template" class="js-property-control-detail-template">
                                                <div class="property-control-detail">
                                                    <div class="property-control-detail-card">
                                                        <div class="row g-3">
                                                            @foreach ($checkLabels as $key => $label)
                                                                <div class="col-md-6">
                                                                    <div class="property-control-check {{ ($row['checks'][$key] ?? false) ? 'is-complete' : 'is-missing' }}">
                                                                        <i class="bi {{ ($row['checks'][$key] ?? false) ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                                                        <span>{{ $label }}</span>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                            </div>
                                                </div>
                                            </script>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" data-empty-row="true" class="text-center text-muted py-15">
                                            No hay propiedades disponibles para mostrar.
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
            const tableElement = document.getElementById('property_control_table');
            const searchInput = document.getElementById('property_control_search');
            const resultCountElement = document.getElementById('propertyControlResultCount');
            const tabButtons = Array.from(document.querySelectorAll('#propertyControlStatusTabs [data-status-filter]'));

            if (!tableElement || typeof $ === 'undefined' || !$.fn.DataTable) {
                return;
            }

            const emptyCell = tableElement.querySelector('td[data-empty-row="true"]');
            if (emptyCell) {
                emptyCell.closest('tr')?.remove();
            }

            let activeStatus = @json($filters['status'] ?: 'all');
            let openRow = null;
            let searchDebounce = null;

            const updateUrlState = () => {
                const nextUrl = new URL(window.location.href);
                const searchValue = searchInput?.value.trim() || '';

                if (searchValue) {
                    nextUrl.searchParams.set('q', searchValue);
                } else {
                    nextUrl.searchParams.delete('q');
                }

                if (activeStatus && activeStatus !== 'all') {
                    nextUrl.searchParams.set('status', activeStatus);
                } else {
                    nextUrl.searchParams.delete('status');
                }

                window.history.replaceState({}, '', nextUrl);
            };

            const closeExpandedRow = () => {
                if (!openRow) {
                    return;
                }

                const rowApi = dataTable.row(openRow);
                if (rowApi?.child?.isShown()) {
                    rowApi.child.hide();
                }

                openRow.classList.remove('is-expanded');
                openRow.setAttribute('aria-expanded', 'false');
                openRow = null;
            };

            const syncResultCount = () => {
                if (!resultCountElement) {
                    return;
                }

                const count = dataTable.rows({ filter: 'applied' }).count();
                resultCountElement.textContent = `${count} resultados`;
            };

            const rowMatchesStatus = (rowNode) => {
                if (activeStatus === 'all') {
                    return true;
                }

                const filters = (rowNode?.dataset.statusFilters || '').split(/\s+/).filter(Boolean);
                return filters.includes(activeStatus);
            };

            $.fn.dataTable.ext.search.push((settings, data, dataIndex) => {
                if (settings.nTable !== tableElement) {
                    return true;
                }

                const rowNode = settings.aoData[dataIndex]?.nTr;
                return rowMatchesStatus(rowNode);
            });

            const dataTable = $(tableElement).DataTable({
                dom: "rt<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
                pageLength: 10,
                lengthChange: false,
                ordering: false,
                info: true,
                searching: true,
                autoWidth: false,
                language: {
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ propiedades',
                    infoEmpty: 'Mostrando 0 a 0 de 0 propiedades',
                    paginate: {
                        first: 'Primera',
                        last: 'Última',
                        next: 'Siguiente',
                        previous: 'Anterior',
                    },
                    emptyTable: 'Aún no hay propiedades registradas.',
                    zeroRecords: 'No se encontraron coincidencias con este filtro.',
                },
                columnDefs: [
                    {
                        targets: [2],
                        orderable: false,
                        searchable: false,
                    },
                ],
            });

            const toggleRowDetail = (rowNode) => {
                if (!rowNode) {
                    return;
                }

                const rowApi = dataTable.row(rowNode);
                const template = rowNode.querySelector('.js-property-control-detail-template');
                if (!template) {
                    return;
                }

                if (rowApi.child.isShown()) {
                    rowApi.child.hide();
                    rowNode.classList.remove('is-expanded');
                    rowNode.setAttribute('aria-expanded', 'false');
                    openRow = null;
                    return;
                }

                closeExpandedRow();
                rowApi.child(`<div class="property-control-child">${template.innerHTML}</div>`, 'property-control-child-row').show();
                rowNode.classList.add('is-expanded');
                rowNode.setAttribute('aria-expanded', 'true');
                openRow = rowNode;
            };

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    if (activeStatus === button.dataset.statusFilter) {
                        return;
                    }

                    activeStatus = button.dataset.statusFilter || 'all';

                    tabButtons.forEach((tabButton) => {
                        const isActive = tabButton === button;
                        tabButton.classList.toggle('active', isActive);
                        tabButton.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });

                    closeExpandedRow();
                    dataTable.draw();
                    syncResultCount();
                    updateUrlState();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    window.clearTimeout(searchDebounce);
                    searchDebounce = window.setTimeout(() => {
                        closeExpandedRow();
                        dataTable.search(searchInput.value).draw();
                        syncResultCount();
                        updateUrlState();
                    }, 120);
                });
            }

            tableElement.querySelector('tbody')?.addEventListener('click', (event) => {
                if (event.target.closest('.js-property-control-action')) {
                    return;
                }

                const rowNode = event.target.closest('tr.property-control-row');
                if (!rowNode) {
                    return;
                }

                toggleRowDetail(rowNode);
            });

            tableElement.querySelector('tbody')?.addEventListener('keydown', (event) => {
                const rowNode = event.target.closest('tr.property-control-row');
                if (!rowNode || event.target.closest('.js-property-control-action')) {
                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    toggleRowDetail(rowNode);
                }
            });

            dataTable.on('draw', () => {
                closeExpandedRow();
                syncResultCount();
            });

            if (searchInput?.value) {
                dataTable.search(searchInput.value);
            }

            dataTable.draw();
            syncResultCount();
            updateUrlState();
        })();
    </script>
@endpush
