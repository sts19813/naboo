@extends('layouts.app')
@section('title', 'Mis propiedades | Naboo')
@section('content')
<div class="container py-8">
    <div class="d-flex justify-content-between align-items-center mb-5"><div><h1 class="fw-bold">Mis propiedades</h1><p class="text-muted mb-0">Propiedades asignadas para mantenimiento.</p></div><a class="btn btn-light-primary" href="{{ route('maintenance.index') }}">Mis tickets</a></div>
    <div class="card"><div class="card-body table-responsive"><table class="table align-middle"><thead><tr><th>Propiedad</th><th>Dirección</th><th></th></tr></thead><tbody>
        @forelse ($properties as $property)<tr><td>{{ $property->internal_name }} @if ($property->internal_reference)<small class="text-muted">· {{ $property->internal_reference }}</small>@endif</td><td>{{ $property->full_address }}</td><td><a href="{{ route('properties.show', $property) }}" class="btn btn-sm btn-light-primary">Ver mantenimiento</a></td></tr>
        @empty<tr><td colspan="3" class="text-muted">No tienes propiedades asignadas.</td></tr>@endforelse
    </tbody></table></div></div>
</div>
@endsection
