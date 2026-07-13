@extends('layouts.app')

@section('title', 'Almacén | SuWork')

@push('styles')
    <style>
        .storage-list-module {
            --sl-surface: #ffffff;
            --sl-ink: #172033;
            --sl-text: #334155;
            --sl-muted: #7b879d;
            --sl-line: #e5eaf3;
            --sl-accent: #b54708;
            --sl-accent-soft: #fff1e8;
            --sl-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            color: var(--sl-text);
        }

        .storage-list-shell {
            border: 0;
            background: transparent;
            box-shadow: none !important;
        }

        .storage-list-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .storage-list-search {
            position: relative;
            min-width: min(100%, 360px);
            flex: 1 1 300px;
        }

        .storage-list-search i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--sl-muted);
            font-size: 1rem;
            pointer-events: none;
            z-index: 1;
        }

        .storage-list-search .form-control {
            height: 52px;
            padding-left: 46px !important;
            border-radius: 16px;
            border: 1px solid var(--sl-line);
            background: #fbfcfe;
            color: var(--sl-ink);
            font-weight: 600;
            box-shadow: none;
            min-width: 100% !important;
        }

        .storage-list-search .form-control:focus {
            border-color: rgba(181, 71, 8, 0.35);
            box-shadow: 0 0 0 4px rgba(181, 71, 8, 0.08);
        }

        .storage-list-view-toggle .btn {
            border-radius: 12px;
            font-weight: 700;
        }

        .storage-list-table-card {
            margin-top: 20px;
            border: 1px solid var(--sl-line);
            border-radius: 20px;
            overflow: hidden;
            background: var(--sl-surface);
        }

        .storage-list-table-card .table-responsive {
            overflow-x: auto;
        }

        .storage-list-table-card table.dataTable {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0;
        }

        .storage-list-table-card thead th {
            padding-top: 20px;
            padding-bottom: 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--sl-line) !important;
            color: #94a3b8 !important;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
        }

        .storage-list-row {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .storage-list-row td {
            padding-top: 12px;
            padding-bottom: 12px;
            border-top: 1px solid var(--sl-line) !important;
            vertical-align: middle;
            background: #fff;
        }

        .storage-list-row:hover td {
            background: #fcf8f6;
        }

        .storage-list-title {
            color: var(--sl-ink);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .storage-list-meta {
            color: var(--sl-muted);
            font-size: 0.88rem;
            margin-top: 4px;
            line-height: 1.4;
        }

        .storage-list-value {
            color: var(--sl-ink);
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .storage-list-actions {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .storage-list-actions .btn,
        .storage-grid-actions .btn {
            border-radius: 12px;
            font-weight: 700;
        }

        .storage-list-table-card .dataTables_info,
        .storage-list-table-card .dataTables_paginate {
            padding: 18px 28px 0;
            color: var(--sl-muted) !important;
            font-weight: 700;
        }

        .storage-list-table-card .dataTables_paginate .pagination {
            gap: 6px;
        }

        .storage-list-table-card .page-link {
            border-radius: 10px !important;
            border-color: var(--sl-line) !important;
            color: var(--sl-text) !important;
            min-width: 38px;
            text-align: center;
            font-weight: 700;
        }

        .storage-list-table-card .page-item.active .page-link {
            background: var(--sl-accent) !important;
            border-color: var(--sl-accent) !important;
            color: #fff !important;
        }

        .storage-thumb {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            border: 1px solid var(--sl-line);
            background: #f8fafc;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .storage-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 5px;
        }

        .storage-grid {
            align-items: stretch;
        }

        .storage-grid-card {
            border: 1px solid var(--sl-line);
            border-radius: 20px;
            overflow: hidden;
            background: #fff;
            box-shadow: var(--sl-shadow);
        }

        .storage-grid-media {
            position: relative;
            aspect-ratio: 4 / 3;
            background: #f8fafc;
            border-bottom: 1px solid var(--sl-line);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .storage-grid-media img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 14px;
        }

        .storage-grid-media-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
        }

        .storage-grid-qty {
            position: absolute;
            top: 14px;
            right: 14px;
        }

        .storage-grid-card .card-body {
            padding: 20px;
        }

        .storage-grid-title {
            color: var(--sl-ink);
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.25;
            margin: 0;
        }

        .storage-grid-meta {
            color: var(--sl-muted);
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .storage-grid-description {
            min-height: 42px;
        }

        @media (max-width: 991px) {
            .storage-list-table-card .dataTables_info,
            .storage-list-table-card .dataTables_paginate {
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-6 storage-list-module">

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-6">
                <i class="ki-duotone ki-check-circle fs-2hx text-success me-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>

                <div class="fw-semibold fs-6">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <div class="card storage-list-shell">

            {{-- HEADER --}}
            <div class="card-header border-0 pt-7 pb-4">

                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center w-100 gap-5">

                    <div class="d-flex align-items-center gap-4">
                        <div class="symbol symbol-70px flex-shrink-0">
                            <div class="bg-light-primary">
                                <i class="ki-duotone ki-package fs-1 text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>

                        <div>
                            <h1 class="fw-bold text-dark mb-1">
                                Gestión de Almacén
                            </h1>

                            <div class="text-muted fw-semibold fs-6">
                                Administración y control de inventario
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-light-info" data-bs-toggle="modal" data-bs-target="#warehouseCatalogModal">
                            <i class="ki-duotone ki-home fs-6 me-1"></i>
                            Catálogo
                        </button>

                        <a href="{{ route('storage_items.trashed') }}" class="btn btn-light-warning">
                            <i class="ki-duotone ki-trash fs-6 me-1"></i>
                            Eliminados
                        </a>

                        <a href="{{ route('storage_items.create') }}" class="btn btn-primary">
                            <i class="ki-duotone ki-plus fs-6 me-1"></i>
                            Nuevo Item
                        </a>

                    </div>

                </div>

            </div>

            {{-- FILTROS --}}
            <div class="card-body pt-0">

                <div class="storage-list-toolbar">

                    <div class="d-flex flex-wrap gap-3 align-items-center storage-list-search">
                        <div class="position-relative w-100">
                            <i
                                class="ki-duotone ki-magnifier">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>

                            <input type="search" id="storageItemsSearch" value="{{ $search }}" class="form-control"
                                placeholder="Buscar item..." autocomplete="off">
                        </div>
                    </div>

                    <div class="btn-group storage-list-view-toggle">

                        <a href="{{ route('storage_items.index', ['view' => 'grid']) }}" class="btn {{ $viewMode === 'grid'
        ? 'btn-primary'
        : 'btn-light-primary' }}">

                            <i class="ki-duotone ki-category fs-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>

                        </a>

                        <a href="{{ route('storage_items.index', ['view' => 'table']) }}" class="btn {{ $viewMode === 'table'
        ? 'btn-primary'
        : 'btn-light-primary' }}">

                            <i class="ki-duotone ki-row-horizontal fs-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>

                        </a>


                    </div>

                </div>

                {{-- EMPTY --}}
                @if ($items->isEmpty())

                    <div class="text-center py-20">

                        <div class="mb-5">
                            <i class="ki-duotone ki-package fs-5tx text-gray-300">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>

                        <h3 class="fw-bold text-dark mb-3">
                            No hay items registrados
                        </h3>

                        <div class="text-muted fs-6 mb-7">
                            Comienza agregando productos al almacén
                        </div>

                        <a href="{{ route('storage_items.create') }}" class="btn btn-primary fw-bold">
                            Agregar Item
                        </a>

                    </div>

                    {{-- TABLE --}}
                @elseif ($viewMode === 'table')

                    <div class="storage-list-table-card">
                        <div class="table-responsive">

                            <table class="table table-row-bordered align-middle mb-0" id="storageItemsTable">

                            <thead>
                                <tr class="text-start text-muted fw-bold text-uppercase fs-8">
                                    <th class="ps-7 min-w-260px">Item</th>
                                    <th>Categoría</th>
                                    <th>Almacén</th>
                                    <th>Zona</th>
                                    <th>Marca</th>
                                    <th>Cantidad</th>
                                    <th>Estado</th>
                                    <th class="text-end pe-7">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach ($items as $item)

                                    <tr class="storage-list-row" data-search="{{ Str::lower($item->name.' '.$item->product_type.' '.$item->brand.' '.$item->description.' '.($item->warehouse?->name ?? '').' '.($item->zone?->name ?? '')) }}">

                                        <td class="ps-7">
                                            <div class="d-flex align-items-center gap-4">

                                                <div class="storage-thumb">

                                                    @if ($item->photo)

                                                        <img src="{{ asset('storage/' . $item->photo) }}" alt="{{ $item->name }}">

                                                    @else

                                                        <i class="ki-duotone ki-picture fs-2x text-primary">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>

                                                    @endif

                                                </div>

                                                <div>
                                                    <div class="storage-list-title">
                                                        {{ $item->name }}
                                                    </div>

                                                    <div class="storage-list-meta">
                                                        {{ Str::limit($item->description, 70) }}
                                                    </div>
                                                </div>

                                            </div>
                                        </td>

                                        <td><div class="storage-list-value">{{ $item->product_type }}</div></td>
                                        <td><div class="storage-list-value">{{ $item->warehouse?->name ?? '-' }}</div></td>
                                        <td><div class="storage-list-value">{{ $item->zone?->name ?? '-' }}</div></td>

                                        <td><div class="storage-list-value">{{ $item->brand ?: '-' }}</div></td>

                                        <td>
                                            <span class="badge badge-light-primary fs-7 fw-bold">
                                                {{ $item->quantity }}
                                            </span>
                                        </td>

                                        <td>

                                            @if ($item->condition === 'bueno')
                                                <span class="badge badge-light-success">
                                                    Bueno
                                                </span>
                                            @elseif($item->condition === 'regular')
                                                <span class="badge badge-light-warning">
                                                    Regular
                                                </span>
                                            @else
                                                <span class="badge badge-light-danger">
                                                    Malo
                                                </span>
                                            @endif

                                        </td>

                                        <td class="text-end pe-7">

                                            <div class="storage-list-actions">

                                                <a href="{{ route('storage_items.show', $item) }}"
                                                    class="btn btn-sm btn-light-dark">
                                                    Ver
                                                </a>

                                                <a href="{{ route('storage_items.edit', $item) }}"
                                                    class="btn btn-sm btn-light-primary">
                                                    Editar
                                                </a>

                                                <button type="button" class="btn btn-sm btn-light-info" data-bs-toggle="modal"
                                                    data-bs-target="#noteModal{{ $item->id }}">
                                                    Nota
                                                </button>

                                                <button type="button" class="btn btn-sm btn-light-danger" data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal{{ $item->id }}">
                                                    Eliminar
                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                            </table>

                        </div>
                    </div>

                    {{-- GRID --}}
                @else

                    <div class="row g-6 storage-grid">

                        @foreach ($items as $item)

                            <div class="col-xl-3 col-lg-4 col-md-6 storage-item-card" data-search="{{ Str::lower($item->name.' '.$item->product_type.' '.$item->brand.' '.$item->description.' '.($item->warehouse?->name ?? '').' '.($item->zone?->name ?? '')) }}">

                                <div class="card storage-grid-card h-100">

                                    <div class="storage-grid-media">

                                        @if ($item->photo)

                                            <img src="{{ asset('storage/' . $item->photo) }}" alt="{{ $item->name }}">

                                        @else

                                            <div class="storage-grid-media-placeholder">

                                                <i class="ki-duotone ki-picture fs-5x text-primary opacity-50">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>

                                            </div>

                                        @endif

                                        <div class="storage-grid-qty">

                                            <span class="badge badge-primary fw-bold px-4 py-3">
                                                {{ $item->quantity }}
                                            </span>

                                        </div>

                                    </div>

                                    <div class="card-body d-flex flex-column">

                                        <div class="mb-4">

                                            <div class="text-muted text-uppercase fw-bold fs-8 mb-1">
                                                {{ $item->product_type }}
                                            </div>

                                            <h3 class="storage-grid-title">
                                                {{ $item->name }}
                                            </h3>

                                        </div>

                                        @if ($item->brand)
                                            <div class="storage-grid-meta mb-4">
                                                Marca:
                                                <span class="fw-bold">
                                                    {{ $item->brand }}
                                                </span>
                                            </div>
                                        @endif

                                        <div class="storage-grid-meta mb-2">
                                            Almacén: <span class="fw-bold">{{ $item->warehouse?->name ?? '-' }}</span>
                                        </div>
                                        <div class="storage-grid-meta mb-4">
                                            Zona: <span class="fw-bold">{{ $item->zone?->name ?? '-' }}</span>
                                        </div>

                                        <div class="mb-5">

                                            @if ($item->condition === 'bueno')
                                                <span class="badge badge-light-success">
                                                    Bueno
                                                </span>
                                            @elseif($item->condition === 'regular')
                                                <span class="badge badge-light-warning">
                                                    Regular
                                                </span>
                                            @else
                                                <span class="badge badge-light-danger">
                                                    Malo
                                                </span>
                                            @endif

                                        </div>

                                        <div class="storage-grid-meta storage-grid-description mb-6 flex-grow-1">
                                            {{ Str::limit($item->description, 90) }}
                                        </div>

                                        <div class="d-grid gap-2 storage-grid-actions">

                                            <a href="{{ route('storage_items.show', $item) }}" class="btn btn-light-dark">
                                                Ver
                                            </a>

                                            <a href="{{ route('storage_items.edit', $item) }}" class="btn btn-light-primary">
                                                Editar
                                            </a>

                                            <button type="button" class="btn btn-light-info" data-bs-toggle="modal"
                                                data-bs-target="#noteModal{{ $item->id }}">
                                                Nota
                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

            </div>

        </div>

        <div class="modal fade" id="warehouseCatalogModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">Catálogo de almacenes y zonas</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-6">
                            <div class="col-lg-6">
                                <div class="card card-flush border">
                                    <div class="card-header">
                                        <h3 class="card-title">Nuevo almacén</h3>
                                    </div>
                                    <div class="card-body">
                                        <form id="warehouseCreateFormCatalog">
                                            <div class="mb-4">
                                                <label class="form-label required">Nombre</label>
                                                <input type="text" name="name" class="form-control" maxlength="190" required>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">Ubicación</label>
                                                <input type="text" name="location" class="form-control" maxlength="255">
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">URL Maps</label>
                                                <input type="url" name="maps_url" class="form-control" maxlength="500">
                                            </div>
                                            <button type="submit" class="btn btn-primary">Crear almacén</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card card-flush border">
                                    <div class="card-header">
                                        <h3 class="card-title">Nueva zona</h3>
                                    </div>
                                    <div class="card-body">
                                        <form id="zoneCreateFormCatalog">
                                            <div class="mb-4">
                                                <label class="form-label required">Almacén</label>
                                                <select name="storage_warehouse_id" class="form-select" required>
                                                    @foreach ($warehouses as $warehouse)
                                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label required">Nombre zona</label>
                                                <input type="text" name="name" class="form-control" maxlength="190" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Crear zona</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card card-flush border">
                                    <div class="card-header">
                                        <h3 class="card-title">Almacenes</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table align-middle">
                                                <thead>
                                                    <tr class="text-muted">
                                                        <th>Nombre</th>
                                                        <th>Ubicación</th>
                                                        <th>URL Maps</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($warehouses as $warehouse)
                                                        <tr>
                                                            <form method="POST" action="{{ route('storage_items.warehouses.update', $warehouse) }}">
                                                                @csrf
                                                                @method('PUT')
                                                                <td>
                                                                    <input type="text" name="name" value="{{ $warehouse->name }}" class="form-control" maxlength="190" required>
                                                                </td>
                                                                <td>
                                                                    <input type="text" name="location" value="{{ $warehouse->location }}" class="form-control" maxlength="255">
                                                                </td>
                                                                <td>
                                                                    <input type="url" name="maps_url" value="{{ $warehouse->maps_url }}" class="form-control" maxlength="500">
                                                                </td>
                                                                <td>
                                                                    <button class="btn btn-sm btn-light-primary" type="submit">Guardar</button>
                                                                </td>
                                                            </form>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card card-flush border">
                                    <div class="card-header">
                                        <h3 class="card-title">Zonas</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table align-middle">
                                                <thead>
                                                    <tr class="text-muted">
                                                        <th>Almacén</th>
                                                        <th>Nombre zona</th>
                                                        <th class="text-end">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($zones as $zone)
                                                        <tr>
                                                            <td>
                                                                <select name="storage_warehouse_id" class="form-select" form="zoneUpdateForm{{ $zone->id }}">
                                                                    @foreach ($warehouses as $warehouse)
                                                                        <option value="{{ $warehouse->id }}" {{ (int) $zone->storage_warehouse_id === (int) $warehouse->id ? 'selected' : '' }}>
                                                                            {{ $warehouse->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text" name="name" value="{{ $zone->name }}" class="form-control" maxlength="190" required form="zoneUpdateForm{{ $zone->id }}">
                                                            </td>
                                                            <td class="text-end">
                                                                <form id="zoneUpdateForm{{ $zone->id }}" method="POST" action="{{ route('storage_items.zones.update', $zone) }}" class="d-inline-block">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <button class="btn btn-sm btn-light-primary" type="submit">Guardar</button>
                                                                </form>
                                                                <form method="POST" action="{{ route('storage_items.zones.destroy', $zone) }}" class="d-inline-block ms-2">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button class="btn btn-sm btn-light-danger" type="submit">Eliminar</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODALS --}}
        @foreach ($items as $item)

            {{-- DELETE --}}
            <div class="modal fade" id="deleteModal{{ $item->id }}" tabindex="-1">

                <div class="modal-dialog modal-dialog-centered">

                    <div class="modal-content border-0 shadow">

                        <div class="modal-header border-0">
                            <h2 class="fw-bold">
                                Eliminar Item
                            </h2>

                            <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">

                                <i class="ki-duotone ki-cross fs-2"></i>

                            </button>
                        </div>

                        <form action="{{ route('storage_items.deleteWithNote', $item) }}" method="POST">

                            @csrf

                            <div class="modal-body">

                                <div class="alert alert-light-danger mb-5">
                                    ¿Deseas eliminar
                                    <strong>{{ $item->name }}</strong>?
                                </div>

                                <textarea name="delete_note" rows="4" required class="form-control form-control-solid"
                                    placeholder="Razón o nota..."></textarea>

                            </div>

                            <div class="modal-footer border-0">

                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    Cancelar
                                </button>

                                <button type="submit" class="btn btn-danger fw-bold">
                                    Eliminar
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

            {{-- NOTE --}}
            <div class="modal fade" id="noteModal{{ $item->id }}" tabindex="-1">

                <div class="modal-dialog modal-dialog-centered">

                    <div class="modal-content border-0 shadow">

                        <div class="modal-header border-0">

                            <h2 class="fw-bold">
                                Agregar Nota
                            </h2>

                            <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">

                                <i class="ki-duotone ki-cross fs-2"></i>

                            </button>

                        </div>

                        <form action="{{ route('storage_items.addNote', $item) }}" method="POST">

                            @csrf

                            <div class="modal-body">

                                <div class="mb-5">
                                    <div class="fw-bold fs-4">
                                        {{ $item->name }}
                                    </div>

                                    <div class="text-muted fs-7">
                                        {{ $item->product_type }}
                                    </div>
                                </div>

                                <textarea name="note" rows="4" required class="form-control form-control-solid"
                                    placeholder="Escribe una nota..."></textarea>

                            </div>

                            <div class="modal-footer border-0">

                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    Cancelar
                                </button>

                                <button type="submit" class="btn btn-info fw-bold">
                                    Guardar Nota
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

@endforeach

    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const input = document.getElementById('storageItemsSearch');
            if (!input) return;
            const table = document.getElementById('storageItemsTable');
            if (table && typeof $ !== 'undefined' && $.fn.DataTable) {
                const dataTable = $(table).DataTable({
                    dom: "rt<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
                    pageLength: 10,
                    lengthChange: false,
                    order: [],
                    info: true,
                    searching: true,
                    autoWidth: false,
                    language: {
                        info: 'Mostrando _START_ a _END_ de _TOTAL_ items',
                        infoEmpty: 'Mostrando 0 a 0 de 0 items',
                        paginate: {
                            first: 'Primera',
                            last: 'Ultima',
                            next: 'Siguiente',
                            previous: 'Anterior',
                        },
                        emptyTable: 'No hay items registrados.',
                        zeroRecords: 'No se encontraron coincidencias con este filtro.',
                    },
                    columnDefs: [
                        {
                            targets: [7],
                            orderable: false,
                            searchable: false,
                        },
                    ],
                });
                dataTable.search(input.value || '').draw();
                input.addEventListener('input', (event) => {
                    dataTable.search(event.target.value || '').draw();
                });
            } else {
                const cards = document.querySelectorAll('.storage-item-card');
                const filterCards = () => {
                    const term = (input.value || '').trim().toLowerCase();
                    cards.forEach((card) => {
                        const text = (card.dataset.search || '').toLowerCase();
                        card.classList.toggle('d-none', term !== '' && !text.includes(term));
                    });
                };
                filterCards();
                input.addEventListener('input', filterCards);
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const postCatalog = async (formId, url) => {
                const form = document.getElementById(formId);
                form?.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    }).catch(() => null);
                    if (!response?.ok) return;
                    window.location.reload();
                });
            };
            postCatalog('warehouseCreateFormCatalog', @json(route('storage_items.warehouses.store')));
            postCatalog('zoneCreateFormCatalog', @json(route('storage_items.zones.store')));
        })();
    </script>
@endpush
