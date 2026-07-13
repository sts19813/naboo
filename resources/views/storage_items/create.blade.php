@extends('layouts.app')

@section('content')
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10">

                {{-- Header --}}
                <div class="card mb-5 border-0 shadow-sm">
                    <div
                        class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between py-5 px-5">
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="symbol symbol-60px me-4">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="ki-duotone ki-package fs-1 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </div>
                                </div>

                                <div>
                                    <h1 class="fw-bold text-dark mb-1">
                                        Nuevo Item de Almacén
                                    </h1>

                                    <div class="text-muted fw-semibold fs-6">
                                        Registra productos, herramientas o materiales del inventario.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('storage_items.index') }}" class="btn btn-light-primary fw-bold mt-4 mt-md-0">
                            <i class="ki-duotone ki-arrow-left fs-4 me-1"></i>
                            Volver
                        </a>
                    </div>
                </div>

                {{-- Errors --}}
                @if($errors->any())
                    <div class="alert alert-danger d-flex align-items-start p-5 mb-5">
                        <i class="ki-duotone ki-information-5 fs-2hx text-danger me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>

                        <div class="d-flex flex-column">
                            <h4 class="mb-2 text-danger fw-bold">
                                Se encontraron errores
                            </h4>

                            <ul class="mb-0 ps-5">
                                @foreach($errors->all() as $error)
                                    <li class="mb-1">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form action="{{ route('storage_items.store') }}" method="POST" enctype="multipart/form-data">

                    @csrf

                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-5">

                            {{-- Información General --}}
                            <div class="mb-10">
                                <h3 class="fw-bold text-dark mb-1">
                                    Información General
                                </h3>

                                <div class="text-muted fs-7">
                                    Datos principales del producto.
                                </div>
                            </div>

                            <div class="row g-5 mb-8">
                                <div class="col-md-6">
                                    <label class="form-label required fw-semibold fs-6">
                                        Almacén
                                    </label>
                                    <div class="d-flex gap-2">
                                        <select name="storage_warehouse_id" id="storageWarehouseSelect"
                                            class="form-select form-select-solid @error('storage_warehouse_id') is-invalid @enderror"
                                            required>
                                            @foreach ($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}" {{ (int) old('storage_warehouse_id', $defaultWarehouseId) === (int) $warehouse->id ? 'selected' : '' }}>
                                                    {{ $warehouse->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-light-primary" type="button" data-bs-toggle="modal"
                                            data-bs-target="#createWarehouseModal">+</button>
                                    </div>
                                    @error('storage_warehouse_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required fw-semibold fs-6">
                                        Zona
                                    </label>
                                    <div class="d-flex gap-2">
                                        <select name="storage_zone_id" id="storageZoneSelect"
                                            class="form-select form-select-solid @error('storage_zone_id') is-invalid @enderror"
                                            required></select>
                                        <button class="btn btn-light-primary" type="button" data-bs-toggle="modal"
                                            data-bs-target="#createZoneModal">+</button>
                                    </div>
                                    @error('storage_zone_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required fw-semibold fs-6">
                                        Tipo de producto
                                    </label>

                                    <input type="text" name="product_type"
                                        class="form-control form-control-solid @error('product_type') is-invalid @enderror"
                                        placeholder="Ej: Herramienta, Electrónico..." value="{{ old('product_type') }}"
                                        required>

                                    @error('product_type')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required fw-semibold fs-6">
                                        Nombre
                                    </label>

                                    <input type="text" name="name"
                                        class="form-control form-control-solid @error('name') is-invalid @enderror"
                                        placeholder="Nombre del producto" value="{{ old('name') }}" required>

                                    @error('name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold fs-6">
                                        Marca
                                    </label>

                                    <input type="text" name="brand" class="form-control form-control-solid"
                                        placeholder="Marca del producto" value="{{ old('brand') }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label required fw-semibold fs-6">
                                        Cantidad
                                    </label>

                                    <input type="number" name="quantity" min="1"
                                        class="form-control form-control-solid @error('quantity') is-invalid @enderror"
                                        value="{{ old('quantity', 1) }}" required>

                                    @error('quantity')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold fs-6">
                                        Estado
                                    </label>

                                    <select name="condition"
                                        class="form-select form-select-solid @error('condition') is-invalid @enderror"
                                        data-control="select2" data-hide-search="true">

                                        <option value="">Selecciona estado</option>

                                        <option value="bueno" {{ old('condition') === 'bueno' ? 'selected' : '' }}>
                                            Bueno
                                        </option>

                                        <option value="regular" {{ old('condition') === 'regular' ? 'selected' : '' }}>
                                            Regular
                                        </option>

                                        <option value="malo" {{ old('condition') === 'malo' ? 'selected' : '' }}>
                                            Malo
                                        </option>
                                    </select>

                                    @error('condition')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                            </div>

                            {{-- Imagen --}}
                            <div class="separator separator-dashed my-10"></div>

                            <div class="mb-10">
                                <h3 class="fw-bold text-dark mb-1">
                                    Imagen del Producto
                                </h3>

                                <div class="text-muted fs-7">
                                    Sube una fotografía del artículo.
                                </div>
                            </div>

                            <div class="row g-5 mb-8">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold fs-6">
                                        Foto desde galería
                                    </label>

                                    <input type="file" name="photo" accept="image/*"
                                        class="form-control form-control-solid @error('photo') is-invalid @enderror">

                                    @error('photo')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6 d-block d-md-none">
                                    <label class="form-label fw-semibold fs-6">
                                        Foto desde cámara
                                    </label>

                                    <input type="file" name="photo_camera" accept="image/*" capture="environment"
                                        class="form-control form-control-solid @error('photo_camera') is-invalid @enderror">

                                    @error('photo_camera')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="text-muted fs-7 mt-n3 mb-2">
                                Puedes elegir una imagen de galería o tomarla al momento con la cámara. Formatos permitidos:
                                JPG, PNG, WEBP. Máximo 5MB.
                            </div>

                            {{-- Descripción --}}
                            <div class="separator separator-dashed my-10"></div>

                            <div class="mb-10">
                                <h3 class="fw-bold text-dark mb-1">
                                    Descripción
                                </h3>

                                <div class="text-muted fs-7">
                                    Información adicional del item.
                                </div>
                            </div>

                            <div class="mb-5">
                                <textarea name="description" rows="5" class="form-control form-control-solid"
                                    placeholder="Agrega detalles importantes del producto...">{{ old('description') }}</textarea>
                            </div>

                        </div>

                        {{-- Footer --}}
                        <div class="card-footer border-0 d-flex justify-content-end gap-3 py-5 px-5">

                            <a href="{{ route('storage_items.index') }}" class="btn btn-light">
                                Cancelar
                            </a>

                            <button type="submit" class="btn btn-primary fw-bold">

                                <i class="ki-duotone ki-check fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>

                                Guardar Item
                            </button>

                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div class="modal fade" id="createWarehouseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="createWarehouseForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo almacén</h5>
                        <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label required">Nombre</label>
                            <input type="text" name="name" class="form-control" maxlength="190" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Ubicación</label>
                            <input type="text" name="location" class="form-control" maxlength="255">
                        </div>
                        <div>
                            <label class="form-label">URL Maps</label>
                            <input type="url" name="maps_url" class="form-control" maxlength="500">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createZoneModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="createZoneForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva zona</h5>
                        <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label required">Almacén</label>
                            <select name="storage_warehouse_id" id="zoneWarehouseSelect" class="form-select" required>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label required">Nombre de zona</label>
                            <input type="text" name="name" class="form-control" maxlength="190" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@php
    $warehousesJson = $warehouses->map(function ($warehouse) {
        return [
            'id' => $warehouse->id,
            'name' => $warehouse->name,
            'zones' => $warehouse->zones->map(function ($zone) {
                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

@push('scripts')
    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const warehouses = @json($warehousesJson);
            const defaultZoneId = @json((int) old('storage_zone_id', $defaultZoneId));
            const warehouseSelect = document.getElementById('storageWarehouseSelect');
            const zoneSelect = document.getElementById('storageZoneSelect');
            const zoneWarehouseSelect = document.getElementById('zoneWarehouseSelect');
            const renderZones = (warehouseId, preferred = null) => {
                const w = warehouses.find((item) => String(item.id) === String(warehouseId));
                const zones = w?.zones || [];
                zoneSelect.innerHTML = '';
                zones.forEach((z) => {
                    const option = document.createElement('option');
                    option.value = z.id;
                    option.textContent = z.name;
                    if (String(preferred ?? defaultZoneId) === String(z.id)) option.selected = true;
                    zoneSelect.appendChild(option);
                });
                if (!zones.length) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Sin zonas';
                    zoneSelect.appendChild(option);
                }
            };
            renderZones(warehouseSelect.value);
            zoneWarehouseSelect.value = warehouseSelect.value;
            warehouseSelect.addEventListener('change', () => {
                renderZones(warehouseSelect.value);
                zoneWarehouseSelect.value = warehouseSelect.value;
            });

            const createWarehouseForm = document.getElementById('createWarehouseForm');
            createWarehouseForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(createWarehouseForm);
                const response = await fetch(@json(route('storage_items.warehouses.store')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: formData,
                }).catch(() => null);
                if (!response?.ok) return;
                const payload = await response.json().catch(() => null);
                const warehouse = payload?.warehouse;
                const defaultZone = payload?.default_zone;
                if (!warehouse) return;
                warehouses.push({ id: warehouse.id, name: warehouse.name, zones: defaultZone ? [{ id: defaultZone.id, name: defaultZone.name }] : [] });
                const option = document.createElement('option');
                option.value = warehouse.id;
                option.textContent = warehouse.name;
                option.selected = true;
                warehouseSelect.appendChild(option);
                const zoneOption = document.createElement('option');
                zoneOption.value = warehouse.id;
                zoneOption.textContent = warehouse.name;
                zoneWarehouseSelect.appendChild(zoneOption);
                renderZones(warehouse.id);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createWarehouseModal')).hide();
                createWarehouseForm.reset();
            });

            const createZoneForm = document.getElementById('createZoneForm');
            createZoneForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(createZoneForm);
                const response = await fetch(@json(route('storage_items.zones.store')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: formData,
                }).catch(() => null);
                if (!response?.ok) return;
                const payload = await response.json().catch(() => null);
                const zone = payload?.zone;
                if (!zone) return;
                const warehouse = warehouses.find((item) => String(item.id) === String(zone.storage_warehouse_id));
                if (warehouse) {
                    if (!warehouse.zones.find((item) => String(item.id) === String(zone.id))) {
                        warehouse.zones.push({ id: zone.id, name: zone.name });
                    }
                }
                warehouseSelect.value = String(zone.storage_warehouse_id);
                renderZones(zone.storage_warehouse_id, zone.id);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createZoneModal')).hide();
                createZoneForm.reset();
            });
        })();
    </script>
@endpush