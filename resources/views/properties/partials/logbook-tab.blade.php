<div class="property-logbook">
    <div class="card property-block-card mb-6">
        <div class="card-header border-0 pt-6">
            <div>
                <h3 class="card-title fw-bold mb-1">Nueva nota</h3>
                <div class="text-muted fs-7">Comparte información y seguimiento con el equipo.</div>
            </div>
        </div>
        <div class="card-body pt-0">
            <form action="{{ route('properties.logbook.store', $property) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <textarea name="note" rows="4" class="form-control form-control-solid @error('note') is-invalid @enderror"
                    placeholder="Escribe una nota para la bitácora...">{{ old('note') }}</textarea>
                @error('note')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <div class="mt-4">
                    <label for="logbook-attachments" class="form-label fw-semibold">Imágenes</label>
                    <input id="logbook-attachments" name="attachments[]" type="file" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/avif"
                        class="form-control form-control-solid @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror">
                    <div class="form-text">Opcionales. Puedes publicar solo imágenes, solo texto o ambos. Máximo 10 imágenes de 10 MB cada una.</div>
                    @error('attachments')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('attachments.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end mt-5">
                    <button type="submit" class="btn btn-primary">
                        <i class="ki-outline ki-message-add fs-5 me-1"></i> Publicar nota
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-5">
        <h3 class="fw-bold mb-0">Actividad</h3>
        <span class="badge badge-light-primary">{{ $property->logbookEntries->count() }} {{ $property->logbookEntries->count() === 1 ? 'nota' : 'notas' }}</span>
    </div>

    @forelse ($property->logbookEntries as $entry)
        <article class="card property-block-card logbook-entry mb-5">
            <div class="card-body p-6">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                    <img class="symbol symbol-42px symbol-circle" src="{{ $entry->user?->profilePhotoUrl() ?? asset('metronic/assets/media/svg/avatars/blank.svg') }}" alt="{{ $entry->user?->name ?? 'Usuario eliminado' }}">
                    <div class="min-w-0">
                        <div class="fw-bold text-gray-900">{{ $entry->user?->name ?? 'Usuario eliminado' }}</div>
                        <div class="text-muted fs-8">{{ $entry->created_at->translatedFormat('d M Y, H:i') }}</div>
                    </div>
                    </div>
                    <form action="{{ route('properties.logbook.destroy', [$property, $entry]) }}" method="POST" class="js-delete-logbook-entry" onsubmit="return confirm('¿Eliminar esta nota y sus imágenes?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-icon btn-sm btn-light-danger" title="Eliminar nota" aria-label="Eliminar nota">
                            <i class="ki-outline ki-trash fs-5"></i>
                        </button>
                    </form>
                </div>

                @if ($entry->note)
                    <div class="logbook-entry__note text-gray-800">{{ $entry->note }}</div>
                @endif

                @if ($entry->attachments->isNotEmpty())
                    <div class="logbook-entry__images {{ $entry->note ? 'mt-4' : '' }}">
                        @foreach ($entry->attachments as $attachment)
                            @if ($attachment->is_image)
                                <a class="logbook-entry__image" href="{{ route('properties.logbook.attachments.preview', [$property, $attachment]) }}" target="_blank" rel="noopener"
                                    title="Abrir {{ $attachment->original_name }}">
                                    <img src="{{ route('properties.logbook.attachments.preview', [$property, $attachment]) }}" alt="Imagen adjunta a la nota">
                                </a>
                            @else
                                <a class="btn btn-sm btn-light-primary" href="{{ route('properties.logbook.attachments.download', [$property, $attachment]) }}">
                                    <i class="ki-outline ki-file-down fs-5 me-1"></i>{{ $attachment->original_name }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </article>
    @empty
        <div class="card property-block-card">
            <div class="card-body py-12 text-center">
                <div class="symbol symbol-60px symbol-circle bg-light-primary mb-4">
                    <span class="symbol-label text-primary"><i class="ki-outline ki-notepad-edit fs-2x"></i></span>
                </div>
                <div class="fw-bold text-gray-800 mb-1">Aún no hay notas en la bitácora</div>
                <div class="text-muted fs-7">Agrega la primera para mantener al equipo al tanto.</div>
            </div>
        </div>
    @endforelse
</div>
