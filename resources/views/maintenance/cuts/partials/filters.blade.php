@php
    $filterProperties = $filterTickets->flatMap(fn ($ticket) => collect([$ticket->property])->merge($ticket->childTickets->pluck('property')))->filter()->unique('id')->sortBy('internal_name');
    $filterTechnicians = $filterTickets->map(fn ($ticket) => [
        'key' => $ticket->current_provider_id ? 'technician-'.$ticket->current_provider_id : 'unassigned',
        'name' => $ticket->currentProvider?->name ?? 'Sin técnico asignado',
    ])->unique('key')->sortBy('name');
@endphp
<div class="d-flex flex-wrap align-items-end gap-3 p-4 border-bottom" aria-label="Filtros de tickets para corte">
    <label class="flex-grow-1"><span class="form-label">Propiedad</span>
        <select class="form-select" data-cut-property-filter aria-label="Filtrar tickets por propiedad">
            <option value="all">Todas las propiedades</option>
            @foreach ($filterProperties as $option)
                <option value="{{ $option->id }}">{{ $option->internal_name }}{{ $option->internal_reference ? ' · '.$option->internal_reference : '' }}</option>
            @endforeach
        </select>
    </label>
    <label class="flex-grow-1"><span class="form-label">Proveedor / técnico</span>
        <select class="form-select" data-cut-technician-filter aria-label="Filtrar tickets por proveedor o técnico">
            <option value="all">Todos los proveedores / técnicos</option>
            @foreach ($filterTechnicians as $option)
                <option value="{{ $option['key'] }}">{{ $option['name'] }}</option>
            @endforeach
        </select>
    </label>
    <label class="flex-grow-1"><span class="form-label">Buscar</span>
        <input class="form-control" type="search" data-cut-search placeholder="Buscar folio, ticket, propiedad, técnico..." aria-label="Buscar tickets en tiempo real">
    </label>
    <span class="text-muted fw-semibold pb-3" data-cut-filter-count>{{ $filterTickets->count() }} tickets</span>
</div>
