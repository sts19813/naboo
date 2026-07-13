@php
    $storagePercentage = min(100, $dossierStorage['percentage'] ?? 0);
    $storageWarningPercent = $dossierStorageSettings['storage_warning_percent'] ?? 80;
    $isNearStorageLimit = $storagePercentage >= $storageWarningPercent || ($dossierStorage['is_over_limit'] ?? false);
@endphp

<div id="dossier-storage-module" class="py-10 dossier-settings">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="mb-1 fw-bold">Configuración de almacenamiento</h1>
            <div class="text-muted">Administra el plan contratado y los límites de carga para expedientes.</div>
        </div>
        <a href="{{ route('settings.dossiers.index') }}" class="btn btn-icon btn-light-primary" title="Configuración de expedientes">
            <i class="bi bi-sliders"></i>
        </a>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="storage-panel p-6 p-lg-8 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 mb-7">
                    <div>
                        <span class="badge badge-light-success mb-3">Expedientes</span>
                        <h2 class="fw-bold text-white mb-2">Almacenamiento documental</h2>
                        <div class="text-muted fw-semibold">
                            {{ $dossierStorage['used_label'] }} ocupados de {{ $dossierStorage['limit_label'] }} contratados.
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fs-1 fw-bold text-white">{{ $dossierStorage['percentage_label'] }}%</div>
                        <div class="text-muted fw-semibold">ocupado</div>
                    </div>
                </div>

                <div class="storage-meter mb-6" role="progressbar"
                    aria-valuenow="{{ round($storagePercentage, 2) }}"
                    aria-valuemin="0"
                    aria-valuemax="100">
                    <div class="storage-meter-bar" style="width: {{ $storagePercentage }}%;"></div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="storage-soft-stat">
                            <div class="text-muted fw-semibold fs-8 mb-1">Ocupado exacto</div>
                            <div class="fw-bold text-white">{{ $dossierStorage['used_exact_label'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="storage-soft-stat">
                            <div class="text-muted fw-semibold fs-8 mb-1">Disponible</div>
                            <div class="fw-bold text-white">{{ $dossierStorage['available_label'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="storage-soft-stat">
                            <div class="text-muted fw-semibold fs-8 mb-1">Max. por archivo</div>
                            <div class="fw-bold text-white">{{ $dossierUploadLimit['effective_label'] }}</div>
                        </div>
                    </div>
                </div>

                @if ($isNearStorageLimit)
                    <div class="alert alert-warning mt-6 mb-0">
                        El almacenamiento de expedientes esta cerca de su limite configurado.
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-6">
                    <h3 class="fw-bold mb-5">Capacidad de almacenamiento</h3>
                    <form method="POST" action="{{ route('settings.dossiers.storage.update') }}" data-dossier-storage-form>
                        @csrf
                        @method('PATCH')

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Capacidad total del sistema</label>
                            <div class="input-group input-group-solid">
                                <input type="number" step="0.5" min="1" max="10240" name="storage_limit_gb"
                                    class="form-control form-control-solid"
                                    value="{{ $dossierStorageSettings['storage_limit_gb'] }}">
                                <span class="input-group-text">GB</span>
                            </div>
                            <div class="invalid-feedback d-block" data-error-for="storage_limit_gb"></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Maximo por archivo</label>
                            <div class="input-group input-group-solid">
                                <input type="number" min="1" max="51200" name="max_file_size_mb"
                                    class="form-control form-control-solid"
                                    value="{{ $dossierStorageSettings['max_file_size_mb'] }}">
                                <span class="input-group-text">MB</span>
                            </div>
                            <div class="invalid-feedback d-block" data-error-for="max_file_size_mb"></div>
                            @if ($dossierUploadLimit['is_server_limited'])
                                <div class="form-text text-warning">
                                    El servidor limita la carga efectiva a {{ $dossierUploadLimit['effective_label'] }}.
                                </div>
                            @endif
                        </div>

                        <div class="mb-6">
                            <label class="form-label fw-semibold">Alerta de uso</label>
                            <div class="input-group input-group-solid">
                                <input type="number" min="50" max="100" name="storage_warning_percent"
                                    class="form-control form-control-solid"
                                    value="{{ $dossierStorageSettings['storage_warning_percent'] }}">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="invalid-feedback d-block" data-error-for="storage_warning_percent"></div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check2 me-1"></i> Guardar almacenamiento
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
