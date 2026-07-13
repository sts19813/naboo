@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-0"><i class="bi bi-trash"></i> Items Eliminados</h2>
            <small class="text-muted">Los items marcados como eliminados se pueden restaurar.</small>
        </div>
        <div class="col-auto">
            <a href="{{ route('storage_items.index') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Volver al almacén
            </a>
        </div>
    </div>

    @if($items->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle" style="font-size: 3rem; color: #28a745;"></i>
                <p class="text-muted mt-3">No hay items eliminados</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($items as $item)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 opacity-75">
                        @if($item->photo)
                            <img src="{{ asset('storage/' . $item->photo) }}" class="card-img-top" alt="{{ $item->name }}" style="height: 200px; object-fit: cover; opacity: 0.6;">
                        @else
                            <div class="bg-secondary" style="height: 200px; display: flex; align-items: center; justify-content: center; opacity: 0.6;">
                                <i class="bi bi-image text-white" style="font-size: 2rem;"></i>
                            </div>
                        @endif
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="card-title mb-1">{{ $item->name }}</h6>
                                    <small class="text-muted">{{ $item->product_type }}</small>
                                </div>
                                <span class="badge bg-secondary">{{ $item->quantity }}</span>
                            </div>
                            @if($item->brand)
                                <p class="card-text mb-2"><small><strong>Marca:</strong> {{ $item->brand }}</small></p>
                            @endif
                            <div class="mb-2">
                                @if($item->condition === 'bueno')
                                    <span class="badge bg-success">{{ ucfirst($item->condition) }}</span>
                                @elseif($item->condition === 'regular')
                                    <span class="badge bg-warning">{{ ucfirst($item->condition) }}</span>
                                @else
                                    <span class="badge bg-danger">{{ ucfirst($item->condition) }}</span>
                                @endif
                            </div>
                            <p class="card-text" style="font-size: 0.85rem;">
                                <small class="text-muted">Eliminado: {{ $item->deleted_at->format('d/m/Y H:i') }}</small>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-top">
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <a href="{{ route('storage_items.show', $item) }}" class="btn btn-outline-secondary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form action="{{ route('storage_items.restore', $item->id) }}" method="POST" style="flex: 1;">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success w-100" title="Restaurar">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <nav class="mt-4">
            {{ $items->links(data: ['view' => 'pagination::bootstrap-5']) }}
        </nav>
    @endif
</div>
@endsection
