@extends('layouts.app')

@section('title', 'Mapa | Naboo')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        .property-map-module {
            --map-border: var(--border-soft);
            --map-muted: var(--text-muted);
            --map-ink: var(--text-main);
            --map-panel: #ffffff;
            --map-soft: #f8fafc;
            padding: 24px 0 10px;
        }

        .property-map-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }

        .property-map-title {
            margin: 0;
            color: var(--map-ink);
            font-size: 1.85rem;
            font-weight: 800;
        }

        .property-map-subtitle {
            color: var(--map-muted);
            font-size: .95rem;
            font-weight: 600;
            margin-top: 4px;
        }

        .property-map-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .property-map-stat {
            min-width: 118px;
            border: 1px solid var(--map-border);
            border-radius: 8px;
            background: var(--map-panel);
            padding: 12px 14px;
            text-align: right;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
        }

        .property-map-stat strong {
            display: block;
            color: var(--map-ink);
            font-size: 1.25rem;
            line-height: 1;
        }

        .property-map-stat span {
            display: block;
            color: var(--map-muted);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .02em;
            margin-top: 6px;
            text-transform: uppercase;
        }

        .property-map-workspace {
            position: relative;
            min-height: 720px;
            border: 1px solid var(--map-border);
            border-radius: 8px;
            background: var(--map-soft);
            overflow: hidden;
            box-shadow: 0 16px 36px rgba(15, 23, 42, .06);
        }

        .property-map-toolbar {
            position: absolute;
            z-index: 1100;
            top: 16px;
            left: 16px;
            right: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            pointer-events: none;
        }

        .property-map-search,
        .property-map-filters,
        .property-map-results {
            pointer-events: auto;
            border: 1px solid rgba(226, 232, 240, .95);
            background: rgba(255, 255, 255, .94);
            backdrop-filter: blur(14px);
            box-shadow: 0 14px 32px rgba(15, 23, 42, .10);
        }

        .property-map-search {
            width: min(390px, 36vw);
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 8px;
            padding: 0 12px;
        }

        .property-map-search i {
            color: #64748b;
            font-size: 1rem;
        }

        .property-map-search input {
            width: 100%;
            height: 44px;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--map-ink);
            font-weight: 600;
        }

        .property-map-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            border-radius: 8px;
            padding: 6px;
        }

        .property-map-filter {
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #475569;
            font-size: .78rem;
            font-weight: 800;
            padding: 8px 10px;
            white-space: nowrap;
        }

        .property-map-filter.active {
            background: var(--primary);
            color: #ffffff;
        }

        .property-map-results {
            margin-left: auto;
            border-radius: 8px;
            color: #334155;
            font-size: .78rem;
            font-weight: 800;
            padding: 12px 14px;
            white-space: nowrap;
        }

        #properties-map {
            width: 100%;
            min-height: 720px;
            height: calc(100vh - 210px);
        }

        .su-map-marker {
            position: relative;
            width: 22px;
            height: 22px;
            border: 3px solid #ffffff;
            border-radius: 999px;
            background: #64748b;
            box-shadow: 0 10px 22px rgba(15, 23, 42, .28);
        }

        .su-map-marker-shell {
            background: transparent;
            border: 0;
        }

        .su-map-marker::after {
            position: absolute;
            inset: -9px;
            border: 2px solid currentColor;
            border-radius: 999px;
            content: "";
            opacity: .18;
        }

        .su-map-marker.available { background: #f59e0b; color: #f59e0b; }
        .su-map-marker.occupied,
        .su-map-marker.rented { background: #16a34a; color: #16a34a; }
        .su-map-marker.blocked { background: #dc2626; color: #dc2626; }
        .su-map-marker.in_process { background: #0284c7; color: #0284c7; }
        .su-map-marker.draft { background: #64748b; color: #64748b; }

        .property-map-popup {
            width: 245px;
        }

        .property-map-popup img {
            width: 100%;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
        }

        .property-map-popup-title {
            color: var(--map-ink);
            font-size: .98rem;
            font-weight: 800;
            margin-top: 10px;
        }

        .property-map-popup-meta {
            color: var(--map-muted);
            font-size: .78rem;
            font-weight: 600;
            margin-top: 4px;
        }

        .property-map-popup-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .property-map-popup .btn {
            min-width: 72px;
        }

        .property-map-workspace .leaflet-bottom.leaflet-left {
            left: 8px;
            bottom: 8px;
        }

        .property-map-workspace .leaflet-control-zoom {
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, .95);
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .14);
        }

        .property-map-workspace .leaflet-popup-pane {
            z-index: 1120;
        }

        .property-map-drawer-backdrop {
            position: absolute;
            z-index: 1150;
            inset: 0;
            border: 0;
            background: rgba(15, 23, 42, .16);
            opacity: 0;
            pointer-events: none;
            transition: opacity .22s ease;
        }

        .property-map-drawer-backdrop.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .property-map-drawer {
            position: absolute;
            z-index: 1200;
            top: 0;
            right: 0;
            width: min(430px, 100%);
            height: 100%;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border-left: 1px solid var(--map-border);
            box-shadow: -18px 0 38px rgba(15, 23, 42, .14);
            transform: translateX(104%);
            transition: transform .22s ease;
        }

        .property-map-drawer.is-open {
            transform: translateX(0);
        }

        .property-map-drawer__image {
            position: relative;
            height: 210px;
            background: #e5e7eb;
        }

        .property-map-drawer__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .property-map-drawer__close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 999px;
            background: rgba(15, 23, 42, .78);
            color: #ffffff;
            display: grid;
            place-items: center;
        }

        .property-map-drawer__body {
            min-height: 0;
            flex: 1;
            overflow: auto;
            padding: 22px;
        }

        .property-map-drawer__title {
            color: var(--map-ink);
            font-size: 1.35rem;
            font-weight: 850;
            line-height: 1.2;
            margin: 0;
        }

        .property-map-drawer__address {
            color: var(--map-muted);
            font-size: .88rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .property-map-steps {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin: 20px 0;
        }

        .property-map-step {
            border: 1px solid var(--map-border);
            border-radius: 8px;
            background: #ffffff;
            color: #475569;
            font-size: .78rem;
            font-weight: 800;
            padding: 10px 8px;
        }

        .property-map-step.active {
            border-color: var(--primary);
            background: var(--primary);
            color: #ffffff;
        }

        .property-map-section {
            display: none;
        }

        .property-map-section.active {
            display: block;
        }

        .property-map-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .property-map-info {
            border: 1px solid var(--map-border);
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px;
        }

        .property-map-info span {
            display: block;
            color: #64748b;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .property-map-info strong {
            display: block;
            color: var(--map-ink);
            font-size: .94rem;
            font-weight: 800;
            margin-top: 5px;
            word-break: break-word;
        }

        .property-map-owner-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .property-map-owner-list span {
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: .78rem;
            font-weight: 800;
            padding: 7px 10px;
        }

        .property-map-charge-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .property-map-charge-card {
            min-width: 0;
            border: 1px solid var(--map-border);
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px;
        }

        .property-map-charge-card span {
            display: block;
            color: #64748b;
            font-size: .69rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .property-map-charge-card strong {
            display: block;
            margin-top: 5px;
            color: var(--map-ink);
            font-size: 1rem;
            font-weight: 850;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .property-map-charge-card.is-danger strong { color: #dc2626; }
        .property-map-charge-card.is-success strong { color: #15803d; }

        .property-map-charge-list {
            display: grid;
            gap: 8px;
            margin-top: 16px;
        }

        .property-map-charge-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 6px 12px;
            align-items: center;
            border-bottom: 1px solid #eef2f7;
            padding: 10px 2px;
        }

        .property-map-charge-item__concept {
            overflow: hidden;
            color: var(--map-ink);
            font-size: .82rem;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .property-map-charge-item__meta {
            color: var(--map-muted);
            font-size: .72rem;
            font-weight: 650;
        }

        .property-map-charge-item__amount {
            color: var(--map-ink);
            font-size: .82rem;
            font-weight: 850;
            text-align: right;
        }

        .property-map-charge-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: var(--map-muted);
            font-size: .8rem;
            font-weight: 650;
            padding: 18px;
            text-align: center;
        }

        .property-map-empty {
            position: absolute;
            z-index: 1250;
            inset: 0;
            display: grid;
            place-items: center;
            background: #f8fafc;
            text-align: center;
            padding: 24px;
        }

        .property-map-empty[hidden] {
            display: none;
        }

        .property-map-spinner {
            width: 38px;
            height: 38px;
            margin: 0 auto 14px;
            border: 4px solid var(--primary-soft);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: property-map-spin .8s linear infinite;
        }

        @keyframes property-map-spin {
            to { transform: rotate(360deg); }
        }

        .leaflet-container {
            font: inherit;
        }

        .property-map-empty i {
            color: #94a3b8;
            font-size: 3rem;
        }

        .property-map-empty h2 {
            color: var(--map-ink);
            font-size: 1.2rem;
            font-weight: 800;
            margin: 12px 0 6px;
        }

        @media (max-width: 991.98px) {
            .property-map-head,
            .property-map-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .property-map-stats {
                justify-content: stretch;
            }

            .property-map-stat {
                flex: 1;
                text-align: left;
            }

            .property-map-toolbar {
                position: static;
                gap: 8px;
                padding: 12px;
                background: #ffffff;
                border-bottom: 1px solid var(--map-border);
            }

            .property-map-search {
                width: 100%;
            }

            .property-map-results {
                margin-left: 0;
                align-self: flex-start;
                padding: 8px 10px;
            }

            .property-map-filters {
                width: 100%;
                flex-wrap: nowrap;
                overflow-x: auto;
                overscroll-behavior-x: contain;
                scrollbar-width: thin;
            }

            #properties-map {
                height: max(460px, calc(100dvh - 360px));
                min-height: 460px;
            }

            .property-map-workspace {
                min-height: auto;
            }

            .property-map-drawer-backdrop {
                position: fixed;
                z-index: 1290;
                background: rgba(15, 23, 42, .42);
            }

            .property-map-drawer {
                position: fixed;
                z-index: 1300;
                top: auto;
                bottom: 0;
                width: 100%;
                height: min(82dvh, 760px);
                border-top: 1px solid var(--map-border);
                border-left: 0;
                border-radius: 16px 16px 0 0;
                box-shadow: 0 -18px 44px rgba(15, 23, 42, .22);
                transform: translateY(104%);
            }

            .property-map-drawer.is-open {
                transform: translateY(0);
            }

            .property-map-drawer__image {
                height: 170px;
            }

            body.property-map-drawer-open {
                overflow: hidden;
            }
        }

        @media (max-width: 767.98px) {
            .property-map-module {
                padding-top: 10px;
            }

            .property-map-head {
                gap: 12px;
                margin-bottom: 12px;
            }

            .property-map-title {
                font-size: 1.55rem;
            }

            .property-map-subtitle {
                font-size: .82rem;
                line-height: 1.35;
            }

            .property-map-stats {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 6px;
            }

            .property-map-stat {
                min-width: 0;
                padding: 9px 8px;
            }

            .property-map-stat strong {
                font-size: 1rem;
            }

            .property-map-stat span {
                font-size: .6rem;
                line-height: 1.2;
            }

            .property-map-search input {
                height: 42px;
                font-size: .82rem;
            }

            .property-map-toolbar {
                padding: 9px;
            }

            .property-map-filter {
                min-height: 38px;
                padding: 8px 11px;
            }

            .property-map-workspace .leaflet-control-zoom a {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
                line-height: 40px;
            }

            #properties-map {
                height: max(440px, calc(100dvh - 345px));
                min-height: 440px;
            }

            .property-map-popup {
                width: 215px;
            }

            .property-map-popup img {
                height: 96px;
            }

            .property-map-drawer {
                height: min(86dvh, 720px);
            }

            .property-map-drawer__image {
                height: 145px;
            }

            .property-map-drawer__body {
                padding: 16px;
            }

            .property-map-drawer__title {
                font-size: 1.1rem;
            }

            .property-map-steps {
                gap: 5px;
                margin: 14px 0;
            }

            .property-map-step {
                min-height: 40px;
                padding: 8px 4px;
                font-size: .68rem;
            }

            .property-map-info-grid,
            .property-map-charge-grid {
                gap: 7px;
            }

            .property-map-info,
            .property-map-charge-card {
                padding: 10px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="property-map-module">
        <div class="property-map-head">
            <div>
                <h1 class="property-map-title">Mapa</h1>
                <div class="property-map-subtitle">Vista geografica de propiedades con link de ubicacion registrado.</div>
            </div>

            <div class="property-map-stats">
                <div class="property-map-stat">
                    <strong data-map-pin-count>{{ $markers->count() }}</strong>
                    <span>Con pin</span>
                </div>
                <div class="property-map-stat">
                    <strong data-map-pending-count>{{ $pendingCoordinatesCount }}</strong>
                    <span>Por resolver</span>
                </div>
                <div class="property-map-stat">
                    <strong>{{ $totalWithMapUrl }}</strong>
                    <span>Con link</span>
                </div>
            </div>
        </div>

        <div class="property-map-workspace" data-property-map-module>
            <div class="property-map-toolbar">
                <label class="property-map-search" for="propertyMapSearch">
                    <i class="bi bi-search"></i>
                    <input id="propertyMapSearch" type="search" autocomplete="off"
                        placeholder="Buscar propiedad, zona, inquilino...">
                </label>

                <div class="property-map-filters" aria-label="Filtrar por estatus">
                    <button type="button" class="property-map-filter active" data-status-filter="all">Todos</button>
                    <button type="button" class="property-map-filter" data-status-filter="available">Disponibles</button>
                    <button type="button" class="property-map-filter" data-status-filter="occupied">Ocupadas</button>
                    <button type="button" class="property-map-filter" data-status-filter="blocked">Bloqueadas</button>
                    <button type="button" class="property-map-filter" data-status-filter="in_process">En proceso</button>
                </div>

                <div class="property-map-results" data-map-result-count>{{ $markers->count() }} propiedades</div>
            </div>

            <div id="properties-map"></div>

            <button type="button" class="property-map-drawer-backdrop" data-drawer-backdrop aria-label="Cerrar detalle"></button>

            <div class="property-map-empty" data-map-empty hidden>
                <div>
                    <i class="bi bi-map"></i>
                    <h2 data-map-empty-title>No hay coordenadas disponibles</h2>
                    <p class="text-muted fw-semibold mb-0" data-map-empty-message>Agrega o corrige los links de ubicación de las propiedades.</p>
                </div>
            </div>

            <div class="property-map-empty" data-map-sync hidden>
                <div>
                    <div class="property-map-spinner"></div>
                    <h2 data-map-sync-title>Preparando ubicaciones</h2>
                    <p class="text-muted fw-semibold mb-0" data-map-sync-message>Buscando coordenadas para las propiedades nuevas.</p>
                </div>
            </div>

            <aside class="property-map-drawer" data-map-drawer aria-live="polite" aria-hidden="true">
                <div class="property-map-drawer__image">
                    <img src="" alt="" data-drawer-photo>
                    <button type="button" class="property-map-drawer__close" data-drawer-close aria-label="Cerrar detalle">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="property-map-drawer__body">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                        <div>
                            <h2 class="property-map-drawer__title" data-drawer-name></h2>
                            <div class="property-map-drawer__address" data-drawer-address></div>
                        </div>
                        <span class="badge" data-drawer-status></span>
                    </div>

                    <div class="property-map-steps">
                        <button type="button" class="property-map-step active" data-detail-step="summary">Resumen</button>
                        <button type="button" class="property-map-step" data-detail-step="operation">Operacion</button>
                        <button type="button" class="property-map-step" data-detail-step="charges">Cobranza</button>
                        <button type="button" class="property-map-step" data-detail-step="location">Ubicacion</button>
                    </div>

                    <section class="property-map-section active" data-detail-section="summary">
                        <div class="property-map-info-grid">
                            <div class="property-map-info">
                                <span>Tipo</span>
                                <strong data-drawer-type></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Zona</span>
                                <strong data-drawer-zone></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Renta</span>
                                <strong data-drawer-rent></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Referencia</span>
                                <strong data-drawer-reference></strong>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="text-muted fw-bold fs-8 text-uppercase mb-2">Propietarios</div>
                            <div class="property-map-owner-list" data-drawer-owners></div>
                        </div>
                    </section>

                    <section class="property-map-section" data-detail-section="operation">
                        <div class="property-map-info-grid">
                            <div class="property-map-info">
                                <span>Inquilino</span>
                                <strong data-drawer-tenant></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Asesor</span>
                                <strong data-drawer-advisor></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Cargos pendientes</span>
                                <strong data-drawer-pending-count></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Tickets abiertos</span>
                                <strong data-drawer-tickets></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Inicio contrato</span>
                                <strong data-drawer-contract-start></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Fin contrato</span>
                                <strong data-drawer-contract-end></strong>
                            </div>
                        </div>
                    </section>

                    <section class="property-map-section" data-detail-section="charges">
                        <div class="property-map-charge-grid">
                            <div class="property-map-charge-card">
                                <span>Pendiente</span>
                                <strong data-drawer-charge-pending></strong>
                            </div>
                            <div class="property-map-charge-card is-danger">
                                <span>Vencido</span>
                                <strong data-drawer-charge-overdue></strong>
                            </div>
                            <div class="property-map-charge-card is-success">
                                <span>Cobrado este mes</span>
                                <strong data-drawer-charge-collected></strong>
                            </div>
                            <div class="property-map-charge-card">
                                <span>En validacion</span>
                                <strong data-drawer-charge-validation></strong>
                            </div>
                        </div>

                        <div class="text-muted fw-bold fs-8 text-uppercase mt-5 mb-1">Cargos recientes</div>
                        <div class="property-map-charge-list" data-drawer-charge-list></div>

                        <a href="#" class="btn btn-light-primary fw-bold w-100 mt-4" data-drawer-charges-url>
                            <i class="bi bi-wallet2 me-2"></i> Ver cobranza completa
                        </a>
                    </section>

                    <section class="property-map-section" data-detail-section="location">
                        <div class="property-map-info-grid">
                            <div class="property-map-info">
                                <span>Latitud</span>
                                <strong data-drawer-latitude></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Longitud</span>
                                <strong data-drawer-longitude></strong>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <a href="#" target="_blank" rel="noopener" class="btn btn-light-primary fw-bold" data-drawer-map-url>
                                <i class="bi bi-geo-alt me-2"></i> Abrir ubicacion
                            </a>
                            <a href="#" class="btn btn-primary fw-bold" data-drawer-show-url>
                                <i class="bi bi-house-door me-2"></i> Ver propiedad
                            </a>
                            <a href="#" class="btn btn-light fw-bold" data-drawer-edit-url>
                                <i class="bi bi-pencil-square me-2"></i> Editar propiedad
                            </a>
                        </div>
                    </section>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        (() => {
            const properties = @json($markers);
            const initialPendingCount = Number(@json($pendingCoordinatesCount));
            const syncUrl = @json(route('properties.map.sync-pending'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const defaultCenter = [20.9674, -89.5926];
            const mapElement = document.getElementById('properties-map');

            if (!mapElement || typeof L === 'undefined') {
                return;
            }

            const statusMeta = {
                draft: { label: 'Borrador' },
                available: { label: 'Disponible' },
                in_process: { label: 'En proceso' },
                blocked: { label: 'Bloqueada' },
                occupied: { label: 'Ocupada' },
                rented: { label: 'Rentada' },
            };

            const map = L.map(mapElement, {
                zoomControl: false,
            }).setView(defaultCenter, 12);
            L.control.zoom({ position: 'bottomleft' }).addTo(map);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(map);

            const markerLayer = L.layerGroup().addTo(map);
            const markerById = new Map();
            const searchInput = document.getElementById('propertyMapSearch');
            const resultCount = document.querySelector('[data-map-result-count]');
            const pinCount = document.querySelector('[data-map-pin-count]');
            const pendingCount = document.querySelector('[data-map-pending-count]');
            const filterButtons = Array.from(document.querySelectorAll('[data-status-filter]'));
            const drawer = document.querySelector('[data-map-drawer]');
            const drawerBackdrop = document.querySelector('[data-drawer-backdrop]');
            const emptyState = document.querySelector('[data-map-empty]');
            const syncState = document.querySelector('[data-map-sync]');
            const canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            let popupCloseTimer = null;
            let activeStatus = 'all';

            function text(value) {
                return value === null || value === undefined ? '' : String(value).trim();
            }

            function escapeHtml(value) {
                return text(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function searchableText(property) {
                return [
                    property.name,
                    property.reference,
                    property.address,
                    property.zone,
                    property.type,
                    property.tenant,
                    property.advisor,
                    ...(property.owner_names || []),
                    property.status_label,
                ].map(text).join(' ').toLowerCase();
            }

            function popupFor(property) {
                const statusLabel = statusMeta[property.status]?.label || property.status_label || 'Sin estatus';

                return `
                    <div class="property-map-popup">
                        <img src="${escapeHtml(property.photo_url)}" alt="${escapeHtml(property.name)}">
                        <div class="property-map-popup-title">${escapeHtml(property.name)}</div>
                        <div class="property-map-popup-meta">${escapeHtml(property.zone || property.address || '-')}</div>
                        <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
                            <span class="badge ${escapeHtml(property.status_badge_class || 'badge-light-secondary')}">${escapeHtml(statusLabel)}</span>
                            <button type="button" class="btn btn-sm btn-primary" data-map-property-view="${Number(property.id)}">Ver</button>
                        </div>
                    </div>
                `;
            }

            function withDuplicateOffset(items) {
                const seen = new Map();

                return items.map(function (property) {
                    const key = `${Number(property.latitude).toFixed(6)},${Number(property.longitude).toFixed(6)}`;
                    const index = seen.get(key) || 0;
                    seen.set(key, index + 1);

                    if (index === 0) {
                        return { ...property, displayLatitude: property.latitude, displayLongitude: property.longitude };
                    }

                    const angle = index * 0.85;
                    const radius = 0.00009 * Math.ceil(index / 6);

                    return {
                        ...property,
                        displayLatitude: Number(property.latitude) + Math.sin(angle) * radius,
                        displayLongitude: Number(property.longitude) + Math.cos(angle) * radius,
                    };
                });
            }

            function filteredProperties() {
                const query = text(searchInput?.value).toLowerCase();

                return properties.filter(function (property) {
                    const statusMatches = activeStatus === 'all' || property.status === activeStatus;
                    const textMatches = query === '' || searchableText(property).includes(query);

                    return statusMatches && textMatches;
                });
            }

            function renderMarkers(fitBounds = false) {
                const visible = withDuplicateOffset(filteredProperties());
                markerLayer.clearLayers();
                markerById.clear();
                const bounds = L.latLngBounds();

                visible.forEach(function (property) {
                    const marker = L.marker([Number(property.displayLatitude), Number(property.displayLongitude)], {
                        title: property.name,
                        icon: L.divIcon({
                            className: 'su-map-marker-shell',
                            html: `<div class="su-map-marker ${escapeHtml(property.status)}"></div>`,
                            iconSize: [22, 22],
                            iconAnchor: [11, 11],
                        }),
                    }).bindPopup(popupFor(property), { maxWidth: 280, closeButton: true });

                    marker.on('popupopen', function (event) {
                        const popupElement = event.popup.getElement();
                        if (!popupElement) return;

                        const viewButton = popupElement.querySelector('[data-map-property-view]');
                        if (viewButton && viewButton.dataset.viewBound !== 'true') {
                            viewButton.dataset.viewBound = 'true';
                            viewButton.addEventListener('click', function (clickEvent) {
                                clickEvent.preventDefault();
                                clickEvent.stopPropagation();
                                openDrawer(property);
                            });
                        }

                        if (!canHover || popupElement.dataset.hoverBound === 'true') return;

                        popupElement.dataset.hoverBound = 'true';
                        popupElement.addEventListener('mouseenter', () => window.clearTimeout(popupCloseTimer));
                        popupElement.addEventListener('mouseleave', () => {
                            popupCloseTimer = window.setTimeout(() => marker.closePopup(), 180);
                        });
                    });

                    if (canHover) {
                        marker.on('mouseover', function () {
                            window.clearTimeout(popupCloseTimer);
                            marker.openPopup();
                        });
                        marker.on('mouseout', function () {
                            popupCloseTimer = window.setTimeout(() => marker.closePopup(), 240);
                        });
                    }
                    marker.addTo(markerLayer);
                    bounds.extend(marker.getLatLng());
                    markerById.set(property.id, marker);
                });

                if (resultCount) {
                    resultCount.textContent = `${visible.length} ${visible.length === 1 ? 'propiedad' : 'propiedades'}`;
                }

                if (fitBounds && visible.length > 0) {
                    map.fitBounds(bounds, { padding: [60, 60], maxZoom: 15 });
                } else if (visible.length === 0) {
                    closeDrawer();
                }
            }

            function setText(selector, value, fallback = '-') {
                const element = document.querySelector(selector);

                if (element) {
                    element.textContent = text(value) || fallback;
                }
            }

            function setLink(selector, href) {
                const element = document.querySelector(selector);

                if (element) {
                    try {
                        const url = new URL(href || '#', window.location.origin);
                        element.setAttribute('href', ['http:', 'https:'].includes(url.protocol) ? url.href : '#');
                    } catch (_) {
                        element.setAttribute('href', '#');
                    }
                }
            }

            function money(value, currency = 'MXN') {
                return new Intl.NumberFormat('es-MX', { style: 'currency', currency }).format(Number(value || 0));
            }

            function activateDetailStep(step) {
                document.querySelectorAll('[data-detail-step]').forEach(function (button) {
                    button.classList.toggle('active', button.dataset.detailStep === step);
                });
                document.querySelectorAll('[data-detail-section]').forEach(function (section) {
                    section.classList.toggle('active', section.dataset.detailSection === step);
                });
            }

            function renderChargeSummary(property) {
                const summary = property.charge_summary || {};
                const chargeList = drawer?.querySelector('[data-drawer-charge-list]');

                setText('[data-drawer-charge-pending]', money(summary.pending_amount, property.billing_currency), money(0, property.billing_currency));
                setText('[data-drawer-charge-overdue]', money(summary.overdue_amount, property.billing_currency), money(0, property.billing_currency));
                setText('[data-drawer-charge-collected]', money(summary.collected_month, property.billing_currency), money(0, property.billing_currency));
                setText('[data-drawer-charge-validation]', Number(summary.pending_validation_count || 0), '0');
                setLink('[data-drawer-charges-url]', property.charges_url);

                if (!chargeList) return;

                const recentCharges = Array.isArray(summary.recent_charges) ? summary.recent_charges : [];
                chargeList.innerHTML = '';

                if (!recentCharges.length) {
                    chargeList.innerHTML = '<div class="property-map-charge-empty">Esta propiedad no tiene cargos pendientes ni movimientos del mes.</div>';
                    return;
                }

                recentCharges.forEach(function (charge) {
                    const item = document.createElement('a');
                    item.href = charge.show_url || '#';
                    item.className = 'property-map-charge-item text-decoration-none';
                    item.innerHTML = `
                        <div class="property-map-charge-item__concept">${escapeHtml(charge.concept || 'Cargo')}</div>
                        <div class="property-map-charge-item__amount">${escapeHtml(money(charge.outstanding_amount, property.billing_currency))}</div>
                        <div class="property-map-charge-item__meta">Vence ${escapeHtml(charge.due_date || '-')}</div>
                        <span class="badge ${escapeHtml(charge.status_badge_class || 'badge-light-secondary')}">${escapeHtml(charge.status_label || '-')}</span>
                    `;
                    chargeList.appendChild(item);
                });
            }

            function openDrawer(property) {
                if (!drawer) {
                    return;
                }

                const photo = drawer.querySelector('[data-drawer-photo]');
                const status = drawer.querySelector('[data-drawer-status]');
                const owners = drawer.querySelector('[data-drawer-owners]');

                if (photo) {
                    photo.src = property.photo_url;
                    photo.alt = property.name;
                }

                if (status) {
                    status.className = `badge ${property.status_badge_class || 'badge-light-secondary'}`;
                    status.textContent = property.status_label || 'Sin estatus';
                }

                setText('[data-drawer-name]', property.name);
                setText('[data-drawer-address]', property.address);
                setText('[data-drawer-type]', property.type);
                setText('[data-drawer-zone]', property.zone);
                setText('[data-drawer-rent]', property.rent ? `${property.billing_currency || 'MXN'} $${property.rent}` : null);
                setText('[data-drawer-reference]', property.reference);
                setText('[data-drawer-tenant]', property.tenant);
                setText('[data-drawer-advisor]', property.advisor);
                setText('[data-drawer-pending-count]', property.pending_charges_count, '0');
                setText('[data-drawer-tickets]', property.open_tickets_count);
                setText('[data-drawer-contract-start]', property.contract_starts_at);
                setText('[data-drawer-contract-end]', property.contract_expires_at);
                setText('[data-drawer-latitude]', Number(property.latitude).toFixed(7));
                setText('[data-drawer-longitude]', Number(property.longitude).toFixed(7));
                setLink('[data-drawer-map-url]', property.map_url);
                setLink('[data-drawer-show-url]', property.show_url);
                setLink('[data-drawer-edit-url]', property.edit_url);
                renderChargeSummary(property);

                if (owners) {
                    owners.innerHTML = '';
                    const names = property.owner_names && property.owner_names.length ? property.owner_names : ['Sin propietario'];
                    names.forEach(function (name) {
                        const item = document.createElement('span');
                        item.textContent = name;
                        owners.appendChild(item);
                    });
                }

                activateDetailStep('summary');
                map.closePopup();
                drawer.classList.add('is-open');
                drawer.setAttribute('aria-hidden', 'false');
                drawerBackdrop?.classList.add('is-open');
                document.body.classList.add('property-map-drawer-open');
            }

            function closeDrawer() {
                if (drawer) {
                    drawer.classList.remove('is-open');
                    drawer.setAttribute('aria-hidden', 'true');
                }
                drawerBackdrop?.classList.remove('is-open');
                document.body.classList.remove('property-map-drawer-open');
            }

            function showSync(visible, title, message) {
                if (!syncState) {
                    return;
                }

                syncState.hidden = !visible;
                syncState.querySelector('[data-map-sync-title]').textContent = title;
                syncState.querySelector('[data-map-sync-message]').textContent = message;
            }

            function showEmpty(visible, title, message) {
                if (!emptyState) {
                    return;
                }

                emptyState.hidden = !visible;
                emptyState.querySelector('[data-map-empty-title]').textContent = title;
                emptyState.querySelector('[data-map-empty-message]').textContent = message;
            }

            function updateCounts(remaining) {
                if (pinCount) pinCount.textContent = properties.length;
                if (pendingCount) pendingCount.textContent = remaining;
            }

            async function syncPendingLocations() {
                let remaining = initialPendingCount;
                if (remaining === 0) {
                    showEmpty(properties.length === 0, 'No hay coordenadas disponibles', 'Agrega un link de ubicación a una propiedad para verla aquí.');
                    return;
                }

                showSync(true, 'Preparando ubicaciones', `Buscando coordenadas para ${remaining} ${remaining === 1 ? 'propiedad nueva' : 'propiedades nuevas'}.`);

                try {
                    while (remaining > 0) {
                        const response = await fetch(syncUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                        });
                        if (!response.ok) {
                            throw new Error('No se pudieron actualizar las ubicaciones.');
                        }

                        const result = await response.json();
                        properties.push(...(result.markers || []));
                        remaining = Number(result.remaining || 0);
                        updateCounts(remaining);
                        renderMarkers(properties.length === (result.markers || []).length);

                        if (remaining > 0) {
                            showSync(true, 'Preparando ubicaciones', `Aún faltan ${remaining} ${remaining === 1 ? 'ubicación' : 'ubicaciones'}.`);
                        }
                        if (Number(result.processed || 0) === 0) {
                            break;
                        }
                    }

                    showSync(false, '', '');
                    showEmpty(properties.length === 0, 'No hay coordenadas disponibles', 'No fue posible obtener coordenadas de los links registrados.');
                } catch (error) {
                    showSync(false, '', '');
                    showEmpty(properties.length === 0, 'No se pudieron cargar las ubicaciones', 'Intenta recargar la página en unos momentos.');
                }
            }

            filterButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    activeStatus = button.dataset.statusFilter || 'all';
                    filterButtons.forEach((item) => item.classList.toggle('active', item === button));
                    renderMarkers(false);
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', () => renderMarkers(false));
            }

            document.addEventListener('click', function (event) {
                if (event.target.closest('[data-drawer-close], [data-drawer-backdrop]')) {
                    closeDrawer();
                    return;
                }

                const viewButton = event.target.closest('[data-map-property-view]');
                if (viewButton) {
                    event.preventDefault();
                    const property = properties.find((item) => Number(item.id) === Number(viewButton.dataset.mapPropertyView));
                    if (property) openDrawer(property);
                    return;
                }

                const stepButton = event.target.closest('[data-detail-step]');
                if (stepButton) {
                    activateDetailStep(stepButton.dataset.detailStep);
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeDrawer();
            });

            renderMarkers(properties.length > 0);
            syncPendingLocations();
        })();
    </script>
@endpush
