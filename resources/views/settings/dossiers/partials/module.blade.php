@php
    $activeEntity = $activeEntity ?? 'property';
@endphp

<div id="dossier-settings-module" class="py-10 dossier-settings">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="mb-1 fw-bold">Configuración de expedientes</h1>
            <div class="text-muted">Define los documentos iniciales que aparecerán en cada expediente.</div>
        </div>
        <a href="{{ route('documents.index') }}" class="btn btn-icon btn-light-primary" title="Documentos">
            <i class="bi bi-folder2-open"></i>
        </a>
    </div>

    <div class="row g-4 mb-6">
        @foreach ($entityLabels as $entityType => $entityLabel)
            @php
                $entityRequirements = $requirementsByEntity[$entityType] ?? collect();
                $activeCount = $entityRequirements->where('is_active', true)->count();
            @endphp
            <div class="col-md-4">
                <div class="settings-stat p-5">
                    <div class="text-muted fs-7 text-uppercase">{{ $entityLabel }}</div>
                    <div class="fs-2 fw-bold">{{ $activeCount }}</div>
                    <div class="text-muted fs-8">documentos activos</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <ul class="nav nav-line-tabs mb-8 fs-6" role="tablist">
                @foreach ($entityLabels as $entityType => $entityLabel)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeEntity === $entityType ? 'active' : '' }}"
                            data-bs-toggle="tab"
                            data-bs-target="#dossier-{{ $entityType }}-tab"
                            type="button"
                            role="tab"
                            data-entity="{{ $entityType }}">
                            <i class="bi {{ $entityType === 'property' ? 'bi-house-door' : ($entityType === 'tenant' ? 'bi-people' : 'bi-person-vcard') }} me-1"></i>
                            {{ $entityLabel }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach ($entityLabels as $entityType => $entityLabel)
                    @php
                        $requirements = $requirementsByEntity[$entityType] ?? collect();
                    @endphp
                    <div class="tab-pane fade {{ $activeEntity === $entityType ? 'show active' : '' }}"
                        id="dossier-{{ $entityType }}-tab"
                        role="tabpanel">
                        <div class="row g-8">
                            <div class="col-xl-5">
                                <div class="border rounded p-6">
                                    <h3 class="fw-bold mb-5">Agregar documento</h3>
                                    <form method="POST" action="{{ route('settings.dossiers.requirements.store', ['entity' => $entityType]) }}" data-dossier-settings-form>
                                        @csrf
                                        <input type="hidden" name="entity_type" value="{{ $entityType }}">

                                        <div class="mb-5">
                                            <label class="form-label required">Nombre del documento</label>
                                            <input type="text" name="label" class="form-control" placeholder="Ej: Contrato">
                                            <div class="invalid-feedback d-block" data-error-for="label"></div>
                                        </div>

                                        <label class="form-check form-switch form-check-custom form-check-solid mb-6">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                            <span class="form-check-label fw-semibold">Activo en nuevos expedientes</span>
                                        </label>

                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-plus-lg me-1"></i> Agregar
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="col-xl-7">
                                <div class="d-flex flex-column gap-4">
                                    @forelse ($requirements as $requirement)
                                        <div class="document-row {{ $requirement->is_active ? '' : 'is-inactive' }} p-5">
                                            <form method="POST" action="{{ route('settings.dossiers.requirements.update', [$requirement, 'entity' => $entityType]) }}" data-dossier-settings-form>
                                                @csrf
                                                @method('PUT')
                                                <div class="row g-4 align-items-end">
                                                    <div class="col-lg-6">
                                                        <label class="form-label">Documento</label>
                                                        <input type="text" name="label" class="form-control" value="{{ $requirement->label }}">
                                                        <div class="invalid-feedback d-block" data-error-for="label"></div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <label class="form-label">Orden</label>
                                                        <input type="number" name="sort_order" class="form-control" min="0" max="9999" value="{{ $requirement->sort_order }}">
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <label class="form-check form-switch form-check-custom form-check-solid mb-3">
                                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $requirement->is_active ? 'checked' : '' }}>
                                                            <span class="form-check-label fw-semibold">Activo</span>
                                                        </label>
                                                    </div>
                                                    <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-3">
                                                        <div>
                                                            <span class="badge badge-light-secondary text-secondary">{{ $requirement->document_type }}</span>
                                                            @if (!$requirement->is_active)
                                                                <span class="badge badge-light-warning text-warning ms-2">Inactivo</span>
                                                            @endif
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-check2 me-1"></i> Guardar
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <form method="POST" action="{{ route('settings.dossiers.requirements.destroy', [$requirement, 'entity' => $entityType]) }}" class="text-end mt-3" data-dossier-settings-form>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger" data-dossier-delete>
                                                    <i class="bi bi-trash me-1"></i> Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    @empty
                                        <div class="settings-empty text-center text-muted py-10">
                                            No hay documentos configurados para {{ mb_strtolower($entityLabel) }}.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
