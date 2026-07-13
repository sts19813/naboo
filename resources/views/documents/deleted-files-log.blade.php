@extends('layouts.app')

@section('title', 'Bitácora de Archivos Eliminados | SuWork')

@section('content')
    <div class="py-10 property-module">
        <div class="d-flex justify-content-between align-items-center mb-8 gap-3 flex-wrap">
            <div>
                <h1 class="mb-1 fw-bold">Bitácora de archivos eliminados</h1>
                <div class="text-muted">Histórico general de expedientes (propiedad, inquilino y propietario).</div>
            </div>
            <a href="{{ route('documents.index') }}" class="btn btn-light-primary">Volver a documentos</a>
        </div>

        <div class="card mb-8">
            <div class="card-body">
                <form method="GET" action="{{ route('documents.deleted-files-log') }}" class="row g-4">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por entidad, documento, archivo o motivo...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="entity_type">
                            <option value="">Todas las entidades</option>
                            <option value="property" {{ $filters['entity_type'] === 'property' ? 'selected' : '' }}>Propiedad</option>
                            <option value="tenant" {{ $filters['entity_type'] === 'tenant' ? 'selected' : '' }}>Inquilino</option>
                            <option value="owner" {{ $filters['entity_type'] === 'owner' ? 'selected' : '' }}>Propietario</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="document_group">
                            <option value="">Todos los tipos</option>
                            <option value="property" {{ $filters['document_group'] === 'property' ? 'selected' : '' }}>Expediente propiedad</option>
                            <option value="tenant" {{ $filters['document_group'] === 'tenant' ? 'selected' : '' }}>Expediente inquilino</option>
                            <option value="owner" {{ $filters['document_group'] === 'owner' ? 'selected' : '' }}>Expediente propietario</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('documents.deleted-files-log') }}" class="btn btn-light">Limpiar</a>
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body py-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle mb-0">
                        <thead>
                            <tr class="text-muted text-uppercase fs-8">
                                <th>Fecha</th>
                                <th>Entidad</th>
                                <th>Documento</th>
                                <th>Archivo</th>
                                <th>Eliminado por</th>
                                <th>Estado archivo</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($deletedFiles as $row)
                                <tr>
                                    <td>{{ $row->deleted_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $row->entity_name ?: '-' }}</div>
                                        <div class="text-muted fs-8">{{ strtoupper($row->entity_type) }} #{{ $row->entity_id }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $row->document_label ?: $row->document_type }}</div>
                                        <div class="text-muted fs-8">Tipo: {{ $row->document_group }} | v{{ $row->version_number ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $row->original_name ?: '-' }}</div>
                                        <div class="text-muted fs-8">{{ $row->file_path ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $row->deletedBy?->name ?: 'Sistema' }}</div>
                                        <div class="text-muted fs-8">{{ $row->deletedBy?->email ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $row->file_deleted ? 'badge-light-success text-success' : 'badge-light-warning text-warning' }}">
                                            {{ $row->file_deleted ? 'Borrado en servidor' : 'No encontrado al borrar' }}
                                        </span>
                                    </td>
                                    <td>{{ $row->delete_reason ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-muted">No hay archivos eliminados registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $deletedFiles->links() }}
            </div>
        </div>
    </div>
@endsection

