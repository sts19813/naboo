@extends('layouts.app')
@section('title', 'Revisión de ticket | Naboo')
@section('content')
<div class="container py-8">
    <a href="{{ route('maintenance.index') }}">← Volver a revisión</a>
    <h1 class="fw-bold mt-4">#{{ $ticket->display_reference }} · {{ $ticket->title }}</h1>
    <p class="text-muted">{{ $ticket->property?->internal_name }} · {{ $ticket->currentProvider?->name ?? 'Sin proveedor' }} · {{ $ticket->reported_at?->format('d/m/Y H:i') }}</p>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card mb-4"><div class="card-body"><h2 class="fs-4">Detalle</h2><p>{{ $ticket->description }}</p><p>Estado: {{ \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}</p>
        @if ($ticket->validation)<span class="badge badge-light-success">Validado por {{ $ticket->validation->validatedBy?->name ?? 'Usuario' }} · {{ $ticket->validation->validated_at->format('d/m/Y H:i') }}</span>
        @elseif (auth()->user()->can('mantenimiento.validar_tickets'))<form action="{{ route('maintenance.validate') }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="ticket_uuids[]" value="{{ $ticket->uuid }}"><button class="btn btn-primary" type="submit">Validar ticket</button></form>@endif
    </div></div>
    <div class="card mb-4"><div class="card-body"><h2 class="fs-4">Costos y comprobantes</h2>
        @forelse ($ticket->costs as $cost)<div class="border-bottom py-2">Mano de obra: {{ $cost->currency }} {{ number_format((float) $cost->labor_cost, 2) }} · Materiales: {{ $cost->currency }} {{ number_format((float) $cost->material_cost, 2) }} · Total: {{ $cost->currency }} {{ number_format((float) $cost->final_cost, 2) }}
            @foreach ($cost->expense?->files ?? [] as $file)<div><a target="_blank" rel="noopener" href="{{ asset('storage/'.ltrim($file->path, '/')) }}">{{ $file->original_name }}</a></div>@endforeach
        </div>@empty<p class="text-muted">Sin costos registrados.</p>@endforelse
    </div></div>
    <div class="card"><div class="card-body"><h2 class="fs-4">Evidencias</h2>@forelse ($ticket->files as $file)@if ($file->url)<div><a target="_blank" rel="noopener" href="{{ $file->url }}">{{ $file->original_name }}</a></div>@endif @empty<p class="text-muted">Sin archivos.</p>@endforelse</div></div>
</div>
@endsection
