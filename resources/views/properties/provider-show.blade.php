@extends('layouts.app')
@section('title', 'Mantenimiento de propiedad | Naboo')
@section('content')
<div class="container py-8">
    <a href="{{ route('properties.index') }}">← Mis propiedades</a>
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 mb-5"><div><h1 class="fw-bold">{{ $property->internal_name }}</h1><p class="text-muted mb-0">{{ $property->full_address }}</p></div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleProviderMaintenance">Programar visitas</button></div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body"><h2 class="fs-4">Mis tickets</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Ticket</th><th>Estado</th><th>Visita</th></tr></thead><tbody>
        @forelse ($tickets as $ticket)<tr><td><a href="{{ route('maintenance.show', $ticket) }}">#{{ $ticket->display_reference }} · {{ $ticket->title }}</a></td><td>{{ \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}</td><td>{{ $ticket->scheduled_visit_at?->format('d/m/Y H:i') ?? 'Sin programar' }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No hay tickets asignados.</td></tr>@endforelse
    </tbody></table></div></div></div>
    <div class="modal fade" id="scheduleProviderMaintenance" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="{{ route('properties.maintenance.schedule', $property) }}">
        @csrf <input type="hidden" name="provider_id" value="{{ $provider->id }}">
        <div class="modal-header"><h2 class="modal-title fs-4">Programar visitas</h2><button class="btn btn-icon btn-sm btn-light" type="button" data-bs-dismiss="modal">×</button></div>
        <div class="modal-body"><p class="text-muted">Se creará un ticket por visita, hasta 80.</p>
            @if ($errors->scheduledMaintenance->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->scheduledMaintenance->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="row g-4">
                <div class="col-md-6"><label class="form-label">Frecuencia</label><select class="form-select" name="frequency" required>@foreach (['weekly'=>'Semanal', 'biweekly'=>'Cada dos semanas', 'every_three_weeks'=>'Cada tres semanas', 'monthly'=>'Mensual'] as $key => $label)<option value="{{ $key }}" @selected(old('frequency') === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Hora</label><input class="form-control" type="time" name="visit_time" value="{{ old('visit_time', '09:00') }}" required></div>
                <div class="col-md-6"><label class="form-label">Inicio</label><input class="form-control" type="date" name="start_date" value="{{ old('start_date') }}" required></div><div class="col-md-6"><label class="form-label">Fin</label><input class="form-control" type="date" name="end_date" value="{{ old('end_date') }}" required></div>
                <div class="col-md-6"><label class="form-label">Categoría</label><select class="form-select" name="category">@foreach (\App\Models\MaintenanceTicket::CATEGORY_LABELS as $key => $label)<option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Prioridad</label><select class="form-select" name="priority">@foreach (\App\Models\MaintenanceTicket::PRIORITY_LABELS as $key => $label)<option value="{{ $key }}" @selected(old('priority', 'media') === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Título</label><input class="form-control" name="title" maxlength="190" value="{{ old('title') }}" required></div><div class="col-md-6"><label class="form-label">Ubicación</label><input class="form-control" name="exact_location" maxlength="255" value="{{ old('exact_location') }}" required></div>
                <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="description" maxlength="10000" required>{{ old('description') }}</textarea></div>
            </div>
        </div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Crear visitas</button></div>
    </form></div></div>
</div>
@endsection
@push('scripts')
@if ($errors->scheduledMaintenance->any())<script>new bootstrap.Modal(document.getElementById('scheduleProviderMaintenance')).show();</script>@endif
@endpush
