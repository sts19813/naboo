@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">

            {{-- Header --}}
            <div class="card border-0 shadow-sm mb-6">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between py-5 px-5">
                    <div class="d-flex align-items-center mb-5 mb-md-0">
                        <div class="symbol symbol-70px me-5">
                            <div class="symbol-label bg-light-primary">
                                <i class="ki-duotone ki-package fs-1 text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>

                        <div>
                            <div class="text-muted fw-semibold fs-7 text-uppercase">
                                {{ $item->product_type }}
                            </div>

                            <h1 class="fw-bold text-dark mb-1">
                                {{ $item->name }}
                            </h1>

                            <div class="text-muted fs-6">
                                Vista detallada del item de almacén.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <a href="{{ route('storage_items.index') }}"
                           class="btn btn-light-primary fw-bold">
                            <i class="ki-duotone ki-arrow-left fs-4 me-1"></i>
                            Volver
                        </a>

                        <a href="{{ route('storage_items.edit', $item) }}"
                           class="btn btn-primary fw-bold">
                            <i class="ki-duotone ki-pencil fs-4 me-1"></i>
                            Editar item
                        </a>
                    </div>
                </div>
            </div>

            {{-- Item Detail --}}
            <div class="card border-0 shadow-sm mb-6">
                <div class="card-body p-5">
                    <div class="row g-5 mb-8">
                        <div class="col-xl-5">
                            @if($item->photo)
                                <img src="{{ asset('storage/'.$item->photo) }}"
                                     alt="{{ $item->name }}"
                                     class="w-100 rounded"
                                     style="height: 440px; object-fit: contain;">
                            @else
                                <div class="bg-light-primary rounded d-flex align-items-center justify-content-center"
                                     style="height: 440px; object-fit: contain;">
                                    <i class="ki-duotone ki-picture fs-5x text-primary opacity-50">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            @endif
                        </div>

                        <div class="col-xl-7">
                            <div class="mb-8">
                                <h3 class="fw-bold text-dark mb-3">
                                    Información general
                                </h3>
                                <div class="text-muted fs-7">
                                    Detalles principales del producto almacenado.
                                </div>
                            </div>

                            <div class="row g-5">
                                <div class="col-md-6">
                                    <div class="border border-gray-200 rounded p-5 h-100">
                                        <div class="text-muted fs-7 fw-semibold mb-2">
                                            Almacén
                                        </div>
                                        <div class="fw-bold text-dark fs-5">
                                            {{ $item->warehouse?->name ?? '-' }}
                                        </div>
                                        <div class="text-muted fs-8 mt-1">
                                            {{ $item->warehouse?->location ?: '-' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border border-gray-200 rounded p-5 h-100">
                                        <div class="text-muted fs-7 fw-semibold mb-2">
                                            Zona
                                        </div>
                                        <div class="fw-bold text-dark fs-5">
                                            {{ $item->zone?->name ?? '-' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border border-gray-200 rounded p-5 h-100">
                                        <div class="text-muted fs-7 fw-semibold mb-2">
                                            Cantidad
                                        </div>
                                        <div class="fw-bold text-primary fs-3">
                                            {{ $item->quantity }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border border-gray-200 rounded p-5 h-100">
                                        <div class="text-muted fs-7 fw-semibold mb-2">
                                            Estado
                                        </div>
                                        <div>
                                            @if($item->condition === 'bueno')
                                                <span class="badge badge-light-success fw-bold px-4 py-3">
                                                    Bueno
                                                </span>
                                            @elseif($item->condition === 'regular')
                                                <span class="badge badge-light-warning fw-bold px-4 py-3">
                                                    Regular
                                                </span>
                                            @else
                                                <span class="badge badge-light-danger fw-bold px-4 py-3">
                                                    Malo
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border border-gray-200 rounded p-5 h-100">
                                        <div class="text-muted fs-7 fw-semibold mb-2">
                                            Marca
                                        </div>
                                        <div class="fw-bold text-dark fs-5">
                                            {{ $item->brand ?? 'No especificada' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border border-gray-200 rounded p-5 h-100">
                                        <div class="text-muted fs-7 fw-semibold mb-2">
                                            Tipo de producto
                                        </div>
                                        <div class="fw-bold text-dark fs-5">
                                            {{ $item->product_type }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($item->description)
                        <div class="mb-8">
                            <h3 class="fw-bold text-dark mb-4">
                                Descripción
                            </h3>
                            <div class="bg-light rounded p-5 text-gray-700 fs-6">
                                {{ $item->description }}
                            </div>
                        </div>
                    @endif

                    <div class="separator separator-dashed my-8"></div>

                    <div class="d-flex flex-column flex-md-row gap-6">
                        <div>
                            <div class="text-muted fs-7 fw-semibold mb-1">
                                Fecha de registro
                            </div>
                            <div class="fw-bold text-dark">
                                {{ $item->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-semibold mb-1">
                                Última actualización
                            </div>
                            <div class="fw-bold text-dark">
                                {{ $item->updated_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bitácora --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-6 px-6">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-info">
                                <i class="ki-duotone ki-time fs-2 text-info">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <div>
                            <h3 class="fw-bold text-dark mb-1">Bitácora de cambios</h3>
                            <div class="text-muted fs-7">Historial de movimientos y modificaciones.</div>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3 pb-6 px-6">
                    @php
                        $logs = \App\Models\StorageItemLog::where('storage_item_id', $item->id)
                            ->orderBy('created_at', 'desc')
                            ->get();
                    @endphp

                    @if($logs->isEmpty())
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-information fs-5x text-gray-300 mb-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="text-muted fw-semibold fs-6">
                                Sin registros de cambios
                            </div>
                        </div>
                    @else
                        <div class="timeline-label">
                            @foreach($logs as $log)
                                <div class="timeline-item">
                                    <div class="timeline-line w-40px"></div>
                                    <div class="timeline-icon symbol symbol-circle symbol-40px">
                                        @if($log->action === 'created')
                                            <div class="symbol-label bg-light-success">
                                                <i class="ki-duotone ki-plus fs-4 text-success"></i>
                                            </div>
                                        @elseif($log->action === 'updated')
                                            <div class="symbol-label bg-light-warning">
                                                <i class="ki-duotone ki-pencil fs-4 text-warning"></i>
                                            </div>
                                        @elseif($log->action === 'soft_deleted')
                                            <div class="symbol-label bg-light-danger">
                                                <i class="ki-duotone ki-trash fs-4 text-danger"></i>
                                            </div>
                                        @elseif($log->action === 'restored')
                                            <div class="symbol-label bg-light-primary">
                                                <i class="ki-duotone ki-arrow-circle-left fs-4 text-primary"></i>
                                            </div>
                                        @else
                                            <div class="symbol-label bg-light-secondary">
                                                <i class="ki-duotone ki-note fs-4 text-gray-700"></i>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="timeline-content mb-10 mt-n1">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="fw-bold text-dark fs-6">
                                                @switch($log->action)
                                                    @case('created')
                                                        Item creado
                                                        @break
                                                    @case('updated')
                                                        Información actualizada
                                                        @break
                                                    @case('soft_deleted')
                                                        Item eliminado
                                                        @break
                                                    @case('restored')
                                                        Item restaurado
                                                        @break
                                                    @default
                                                        Nota registrada
                                                @endswitch
                                            </div>
                                            <span class="text-muted fs-8">
                                                {{ $log->created_at->format('d/m/Y H:i') }}
                                            </span>
                                        </div>

                                        @if($log->note)
                                            <div class="bg-light rounded p-4 text-gray-700 fs-7 mb-3">
                                                {{ $log->note }}
                                            </div>
                                        @endif

                                        @if($log->changes && !empty($log->changes))
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-primary fw-semibold">
                                                    Ver detalles
                                                </summary>
                                                <div class="bg-light rounded p-4 mt-3">
                                                    <pre class="mb-0 text-gray-700" style="white-space: pre-wrap; font-size: 0.8rem;">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
