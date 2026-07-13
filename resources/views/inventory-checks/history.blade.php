@extends('layouts.app')

@section('title', 'Histórico de Checks | ' . $property->internal_name . ' | SuWork')

@section('content')
    <div class="py-10 inventory-checks-history">
        <div class="mb-8">
            <a href="{{ route('inventory-checks.index', $property) }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver al inventario
            </a>
        </div>

        <div class="mb-9">
            <h1 class="mb-1 fw-bold">Histórico de Checks</h1>
            <p class="text-muted mb-0">Visualiza todos los checks de entrada y salida realizados.</p>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Checks completados ({{ $checks->total() }})</h3>
            </div>
            <div class="card-body pt-0">
                @forelse ($checks as $check)
                    <div class="border rounded p-6 mb-4">
                        <div class="row g-4 align-items-center">
                            <div class="col-lg-8">
                                <div class="d-flex gap-3 align-items-center mb-2">
                                    @if ($check->type === 'entry')
                                        <span class="badge badge-success">✓ Entrada</span>
                                    @else
                                        <span class="badge badge-danger">✕ Salida</span>
                                    @endif
                                    <span class="text-muted">{{ $check->completed_at?->format('d/m/Y H:i') ?? $check->created_at->format('d/m/Y H:i') }}</span>
                                </div>

                                @if ($check->tenant)
                                    <p class="mb-2">
                                        <strong>Inquilino:</strong> {{ $check->tenant->full_name }}
                                    </p>
                                @endif

                                @if ($check->notes)
                                    <p class="text-muted mb-0">{{ $check->notes }}</p>
                                @endif
                            </div>

                            <div class="col-lg-4 text-end">
                                <a href="{{ route('inventory-checks.show', [$property, $check]) }}" class="btn btn-sm btn-light-primary">
                                    <i class="ki-outline ki-eye fs-7"></i> Ver detalles
                                </a>
                            </div>
                        </div>

                        <!-- Resumen de items -->
                        <div class="mt-4 pt-4 border-top">
                            <div class="d-flex gap-3 flex-wrap">
                                <div>
                                    <span class="text-muted">Total de elementos:</span>
                                    <strong class="ms-2">{{ $check->items->count() }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted">OK:</span>
                                    <strong class="ms-2 text-success">{{ $check->items->where('status', 'ok')->count() }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted">Dañados:</span>
                                    <strong class="ms-2 text-danger">{{ $check->items->where('status', 'damaged')->count() }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted">Faltantes:</span>
                                    <strong class="ms-2 text-warning">{{ $check->items->where('status', 'missing')->count() }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Elementos con problemas -->
                        @php
                            $problemItems = $check->items->whereIn('status', ['damaged', 'missing']);
                        @endphp
                        @if ($problemItems->isNotEmpty())
                            <div class="mt-4 pt-4 border-top">
                                <strong class="d-block mb-3">Elementos con problemas:</strong>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($problemItems as $item)
                                        <span class="badge {{ $item->status === 'damaged' ? 'badge-danger' : 'badge-warning' }}">
                                            {{ $item->item_name }} 
                                            @if ($item->status === 'damaged')
                                                <i class="ki-outline ki-warning ms-1"></i> Dañado
                                            @elseif ($item->status === 'missing')
                                                <i class="ki-outline ki-close ms-1"></i> Faltante
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="alert alert-light-info mb-0">No hay checks completados aún.</div>
                @endforelse

                <!-- Paginación -->
                @if ($checks->hasPages())
                    <div class="d-flex justify-content-center mt-6">
                        {{ $checks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
