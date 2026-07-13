@extends('layouts.app')

@section('title', ($type === 'entry' ? 'Check de Entrada' : 'Check de Salida') . ' | ' . $property->internal_name . ' | SuWork')

@section('content')
    <div class="py-10 inventory-check-create">
        <div class="mb-8">
            <a href="{{ route('inventory-checks.index', $property) }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver al inventario
            </a>
        </div>

        <div class="mb-9">
            <h1 class="mb-1 fw-bold">
                {{ $type === 'entry' ? '✓ Check de Entrada' : '✕ Check de Salida' }}
            </h1>
            <p class="text-muted mb-0">
                {{ $type === 'entry' ? 'Valida el estado de todos los elementos al entregar la propiedad' : 'Documenta el estado final de todos los elementos al salir' }}
            </p>
        </div>

        <form method="POST" action="{{ route('inventory-checks.store', $property) }}" id="checkForm">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            <div class="card mb-8">
                <div class="card-body p-lg-10">
                    <div class="row g-4 mb-6">
                        <div class="col-lg-6">
                            <label class="form-label">Inquilino (opcional)</label>
                            <select name="tenant_id" class="form-select">
                                <option value="">-- Seleccionar inquilino --</option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}">{{ $tenant->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Notas (opcional)</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Observaciones generales..."></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('inventory-checks.index', $property) }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-outline ki-check-circle fs-4 me-2"></i>
                            Comenzar {{ $type === 'entry' ? 'Check de Entrada' : 'Check de Salida' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Elementos a validar ({{ $property->inventoryAreas->sum(fn($a) => $a->items->count()) }})</h3>
                </div>
                <div class="card-body pt-0">
                    @if ($property->inventoryAreas->isEmpty())
                        <div class="alert alert-light-info mb-0">No hay inventario capturado. Por favor, agrega elementos primero.</div>
                    @else
                        <div class="d-flex flex-column gap-8">
                            @foreach ($property->inventoryAreas as $area)
                                <div class="border rounded p-6">
                                    <h5 class="mb-4 fw-bold">{{ $area->name }}</h5>

                                    @if ($area->items->isEmpty())
                                        <p class="text-muted">No hay elementos en esta área.</p>
                                    @else
                                        <div class="d-flex flex-column gap-4">
                                            @foreach ($area->items as $item)
                                                <div class="border rounded p-4 bg-light-gray">
                                                    <div class="row g-4 align-items-center">
                                                        <div class="col-lg-3">
                                                            <strong>{{ $item->name }}</strong>
                                                            <div class="text-muted fs-8 mt-1">{{ $item->condition ? 'Estado: ' . $item->condition : 'Sin estado registrado' }}</div>
                                                            @if ($item->photos->isNotEmpty())
                                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($item->photos->first()->latestVersion->file_path) }}"
                                                                    class="rounded mt-2 cursor-pointer"
                                                                    alt="{{ $item->name }}"
                                                                    style="max-width: 100%; height: auto;"
                                                                    onclick="document.getElementById('modalPhotoImg').src='{{ \Illuminate\Support\Facades\Storage::url($item->photos->first()->latestVersion->file_path) }}';"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#photoModal">
                                                            @endif
                                                        </div>
                                                        <div class="col-lg-9">
                                                            <div class="row g-3">
                                                                <div class="col-lg-6">
                                                                    <label class="form-label">Estado</label>
                                                                    <select class="form-select" disabled>
                                                                        <option value="">Será rellenado en el check</option>
                                                                        <option value="ok">OK - Bien</option>
                                                                        <option value="damaged">Dañado</option>
                                                                        <option value="missing">Faltante</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <label class="form-label">Notas (opcional)</label>
                                                                    <input type="text" class="form-control" placeholder="Observaciones..." disabled>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Foto del inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhotoImg" src="" alt="Foto" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>
@endsection
