@extends('layouts.app')

@section('title', 'Inventario y Check | ' . $property->internal_name . ' | SuWork')

@section('content')
    <style>
        .inventory-thumb {
            max-width: 150px !important;
            max-height: 150px !important;
            object-fit: cover;
        }

        .inventory-photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
    </style>
    <div class="py-10 inventory-check-module">
        <div class="mb-8">
            <a href="{{ route('properties.show', $property) }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a propiedad
            </a>
        </div>

        <div class="mb-9">
            <h1 class="mb-1 fw-bold">{{ $property->internal_name }} - Inventario</h1>
            <p class="text-muted mb-0">Gestiona el inventario, crea checks de entrada/salida y visualiza el historico.</p>
        </div>

        <div class="row g-3 mb-8">
            <div class="col-lg-3">
                <a href="{{ route('inventory-checks.create', [$property, 'entry']) }}" class="btn btn-primary btn-lg w-100">
                    <i class="ki-outline ki-check-circle fs-4 me-2"></i> Check de Entrada
                </a>
            </div>
            <div class="col-lg-3">
                <a href="{{ route('inventory-checks.create', [$property, 'exit']) }}" class="btn btn-danger btn-lg w-100">
                    <i class="ki-outline ki-exit-right fs-4 me-2"></i> Check de Salida
                </a>
            </div>
            <div class="col-lg-3">
                <a href="{{ route('inventory-checks.history', $property) }}" class="btn btn-light-primary btn-lg w-100">
                    <i class="ki-outline ki-clock fs-4 me-2"></i> Ver Historico
                </a>
            </div>
            <div class="col-lg-3">
                <a href="{{ route('inventory-checks.export-pdf', $property) }}" class="btn btn-light-success btn-lg w-100">
                    <i class="ki-outline ki-file-down fs-4 me-2"></i> Exportar PDF
                </a>
            </div>
        </div>

        <div class="row g-6">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                        <h3 class="card-title fw-bold mb-0">Inventario de la propiedad</h3>

                        <a href="{{ route('properties.inventory.edit', $property) }}" class="btn btn-light-primary">
                            <i class="ki-outline ki-pencil fs-4 me-1"></i>
                            Editar inventario
                        </a>
                    </div>
                    <div class="card-body pt-0">
                        @if ($property->inventoryAreas->isEmpty())
                            <div class="alert alert-light-info mb-0">No hay inventario capturado todavia.</div>
                        @else
                            <div class="d-flex flex-column gap-6">
                                @foreach ($property->inventoryAreas as $area)
                                    <div class="border rounded p-5">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h4 class="mb-0">{{ $area->name }}</h4>
                                            <span class="text-muted">{{ $area->items->count() }} elementos</span>
                                        </div>

                                        @if ($area->notes)
                                            <p class="text-gray-700 mb-4">{{ $area->notes }}</p>
                                        @endif

                                        @if ($area->photos->isNotEmpty())
                                            <div class="mb-4">
                                                <strong class="d-block mb-3">Fotos del area:</strong>
                                                <div class="inventory-photo-grid">
                                                    @foreach ($area->photos as $photo)
                                                        <div class="position-relative">
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->file_path) }}"
                                                                class="rounded inventory-thumb cursor-pointer" alt="{{ $area->name }}"
                                                                data-bs-toggle="modal" data-bs-target="#photoModal"
                                                                onclick="document.getElementById('modalPhotoImg').src='{{ \Illuminate\Support\Facades\Storage::url($photo->file_path) }}'">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if ($area->items->isNotEmpty())
                                            <div class="table-responsive">
                                                <table class="table table-row-bordered align-middle mb-0">
                                                    <thead>
                                                        <tr class="text-muted text-uppercase fs-8">
                                                            <th>Elemento</th>
                                                            <th>Estado</th>
                                                            <th>Notas</th>
                                                            <th>Foto actual</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($area->items as $item)
                                                            <tr>
                                                                <td><strong>{{ $item->name }}</strong></td>
                                                                <td>{{ $item->condition ?: '-' }}</td>
                                                                <td>{{ $item->notes ?: '-' }}</td>
                                                                <td>
                                                                    @if ($item->photos->isNotEmpty())
                                                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($item->photos->first()->latestVersion->file_path) }}"
                                                                            class="rounded inventory-thumb cursor-pointer"
                                                                            alt="Foto {{ $item->name }}" data-bs-toggle="modal"
                                                                            data-bs-target="#photoModal"
                                                                            onclick="document.getElementById('modalPhotoImg').src='{{ \Illuminate\Support\Facades\Storage::url($item->photos->first()->latestVersion->file_path) }}'">
                                                                    @else
                                                                        <span class="text-muted">Sin foto</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold">Checks activos</h3>
                    </div>
                    <div class="card-body pt-0">
                        @php
                            $activeEntryCheck = $property->inventoryChecks()
                                ->where('type', \App\Models\InventoryCheck::TYPE_ENTRY)
                                ->where('status', \App\Models\InventoryCheck::STATUS_DRAFT)
                                ->latest()
                                ->first();
                            $activeExitCheck = $property->inventoryChecks()
                                ->where('type', \App\Models\InventoryCheck::TYPE_EXIT)
                                ->where('status', \App\Models\InventoryCheck::STATUS_DRAFT)
                                ->latest()
                                ->first();
                        @endphp

                        @if ($activeEntryCheck)
                            <div class="border rounded p-3 mb-3">
                                <strong class="d-block mb-2">Check de Entrada (En progreso)</strong>
                                <p class="text-muted mb-3">{{ $activeEntryCheck->created_at->diffForHumans() }}</p>
                                <a href="{{ route('inventory-checks.show', [$property, $activeEntryCheck]) }}"
                                    class="btn btn-sm btn-primary w-100">
                                    Continuar
                                </a>
                            </div>
                        @endif

                        @if ($activeExitCheck)
                            <div class="border rounded p-3">
                                <strong class="d-block mb-2">Check de Salida (En progreso)</strong>
                                <p class="text-muted mb-3">{{ $activeExitCheck->created_at->diffForHumans() }}</p>
                                <a href="{{ route('inventory-checks.show', [$property, $activeExitCheck]) }}"
                                    class="btn btn-sm btn-danger w-100">
                                    Continuar
                                </a>
                            </div>
                        @endif

                        @if (!$activeEntryCheck && !$activeExitCheck)
                            <p class="text-muted text-center">No hay checks en progreso</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center">
                    <img id="modalPhotoImg" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>
@endsection
