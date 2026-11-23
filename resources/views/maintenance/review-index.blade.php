@extends('layouts.app')
@section('title', 'Revisión de tickets | Naboo')
@section('content')
<div class="container py-8">
    <h1 class="fw-bold">Revisión de tickets</h1>
    <p class="text-muted">Consulta y valida los tickets completados dentro del periodo autorizado.
        @if ($range) Rango: {{ $range[0]->format('d/m/Y') }} – {{ $range[1]->format('d/m/Y') }}. @else No tienes un rango configurado. @endif
    </p>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body table-responsive"><table class="table align-middle">
        <thead><tr><th>Ticket</th><th>Propiedad</th><th>Proveedor</th><th>Reportado</th><th>Validación</th></tr></thead><tbody>
        @forelse ($tickets as $ticket)
            <tr><td><a href="{{ route('maintenance.show', $ticket) }}">#{{ $ticket->display_reference }} · {{ $ticket->title }}</a></td><td>{{ $ticket->property?->internal_name ?? '-' }}</td><td>{{ $ticket->currentProvider?->name ?? '-' }}</td><td>{{ $ticket->reported_at?->format('d/m/Y H:i') }}</td><td>{{ $ticket->validation ? 'Validado · '.$ticket->validation->validated_at->format('d/m/Y H:i') : 'Pendiente' }}</td></tr>
        @empty <tr><td colspan="5" class="text-muted">No hay tickets en el periodo autorizado.</td></tr> @endforelse
        </tbody></table>{{ $tickets->links() }}</div></div>
</div>
@endsection
