@php
    $versions = $document->relationLoaded('versions') ? $document->versions : collect();
    $latestVersion = $versions->first();
    $fileName = $latestVersion?->original_name;
    $mimeType = $latestVersion?->mime_type;
    [$icon, $iconColor, $iconBg, $extensionLabel] = $fileIcon($fileName ?: $document->file_path);
    $isExpired = $document->expires_at && $document->expires_at->lt(today());
    $hasFile = filled($document->file_path);
    $stateTone = $isExpired ? 'warning' : ($hasFile ? 'success' : 'danger');
    $stateLabel = $isExpired ? 'Vencido' : ($hasFile ? 'Cargado' : 'Falta cargar');
    $stateHelp = $isExpired
        ? 'El archivo sigue disponible, pero su vigencia ya vencio.'
        : ($hasFile ? 'Documento disponible para abrir o descargar.' : 'Sube un archivo para completar este requisito.');
    $uploadedAt = $latestVersion?->uploaded_at ?: $document->uploaded_at;
    $currentFileAction = $hasFile ? $buildFileAction($document->file_path, $fileName, $mimeType) : null;
@endphp

<div class="document-tile document-tile--{{ $stateTone }} p-5 p-lg-7">
    <div class="d-flex flex-column gap-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-4">
            <div class="d-flex align-items-start gap-4 min-w-0 flex-grow-1">
                <div class="document-file-icon rounded {{ $iconBg }} d-flex align-items-center justify-content-center">
                    <i class="ki-outline {{ $icon }} fs-2 {{ $iconColor }}"></i>
                </div>
                <div class="min-w-0 flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h4 class="fw-bold text-gray-900 mb-0">{{ $document->label }}</h4>
                        <span class="document-state-pill document-state-pill--{{ $stateTone }}">{{ $stateLabel }}</span>
                        <span class="badge badge-light">{{ $extensionLabel }}</span>
                        @if ($versions->count() > 1)
                            <span class="badge badge-light-info text-info">v{{ $versions->count() }}</span>
                        @endif
                        @if ($document->exists && filled($document->status))
                            <span class="badge {{ $document->status_badge_class }}">{{ $document->status_label }}</span>
                        @endif
                    </div>

                    <div class="mb-1">
                        @if ($currentFileAction)
                            <a href="{{ $currentFileAction['url'] }}"
                                class="fw-semibold fs-6 text-gray-800 text-hover-primary text-break"
                                @if ($currentFileAction['target']) target="{{ $currentFileAction['target'] }}" rel="noopener" @endif
                                @if (!$currentFileAction['is_previewable']) download="{{ $currentFileAction['download_name'] }}" @endif>
                                {{ $fileName ?: 'Abrir archivo actual' }}
                            </a>
                        @else
                            <span class="fw-semibold fs-6 text-muted">Sin archivo cargado</span>
                        @endif
                    </div>

                    <div class="text-muted fs-7">
                        {{ $stateHelp }}
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-4 mt-3 text-muted fs-8">
                        @if ($uploadedAt)
                            <span><i class="ki-outline ki-calendar-8 fs-7 me-1"></i>Actualizado: {{ $uploadedAt->format('d/m/Y H:i') }}</span>
                        @endif
                        @if ($document->expires_at)
                            <span><i class="ki-outline ki-time fs-7 me-1"></i>Vence: {{ $document->expires_at->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                @if ($currentFileAction)
                    <a href="{{ $currentFileAction['url'] }}"
                        class="btn btn-sm btn-light-primary"
                        title="{{ $currentFileAction['is_previewable'] ? 'Ver archivo' : 'Descargar archivo' }}"
                        @if ($currentFileAction['target']) target="{{ $currentFileAction['target'] }}" rel="noopener" @endif
                        @if (!$currentFileAction['is_previewable']) download="{{ $currentFileAction['download_name'] }}" @endif>
                        <i class="ki-outline {{ $currentFileAction['is_previewable'] ? 'ki-eye' : 'ki-file-down' }} fs-4 me-1"></i>
                        {{ $currentFileAction['is_previewable'] ? 'Ver archivo' : 'Descargar' }}
                    </a>

                    @if ($currentFileAction['is_previewable'])
                        <a href="{{ $currentFileAction['url'] }}"
                            download="{{ $currentFileAction['download_name'] }}"
                            class="btn btn-sm btn-light">
                            <i class="ki-outline ki-file-down fs-4 me-1"></i>Descargar
                        </a>
                    @endif
                @endif

                @if ($canDeleteDossierFiles && $document->exists && $document->file_path)
                    <form method="POST" action="{{ $destroyRouteResolver($document) }}" class="d-inline"
                        onsubmit="return confirm('Eliminar este documento y su historial de versiones?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-icon btn-light btn-active-light-danger btn-sm" type="submit" title="Eliminar">
                            <i class="ki-outline ki-trash fs-2"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if ($document->exists)
            <form method="POST" action="{{ $uploadRouteResolver($document) }}" enctype="multipart/form-data"
                data-dossier-upload-form
                data-max-upload-size="{{ $dossierUploadLimit['effective_bytes'] }}"
                data-max-upload-label="{{ $dossierUploadLimit['effective_label'] }}">
                @csrf
                <div class="row g-4 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Vencimiento opcional</label>
                        <input type="date" name="expires_at" class="form-control form-control-solid"
                            value="{{ $document->expires_at?->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-8">
                        <input id="upload-{{ $entityType }}-{{ $document->document_type }}" type="file" name="file"
                            class="d-none" accept=".pdf,.jpg,.jpeg,.png,.zip" data-dossier-file-input>
                        <label for="upload-{{ $entityType }}-{{ $document->document_type }}"
                            class="document-dropzone d-flex align-items-center justify-content-center text-center p-5"
                            data-document-dropzone>
                            <span>
                                <i class="ki-outline {{ $hasFile ? 'ki-arrows-circle' : 'ki-file-up' }} fs-2x text-gray-500 d-block mb-2"></i>
                                <span class="fw-bold text-gray-900 d-block">
                                    {{ $hasFile ? 'Reemplazar archivo actual' : 'Cargar archivo' }}
                                </span>
                                <span class="text-muted fs-8 d-block mt-1">
                                    PDF, imagenes o ZIP hasta {{ $dossierUploadLimit['effective_label'] }}
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
