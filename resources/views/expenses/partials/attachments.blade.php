@if ($files->isNotEmpty())
    <div class="d-flex flex-wrap gap-2 mt-2">
        @foreach ($files as $file)
            @if ($file->is_image)
                @if ($previewInModal ?? false)
                    <button type="button" class="d-inline-block border-0 bg-transparent p-0 {{ $previewTriggerClass ?? 'js-ticket-file-preview js-maintenance-invoice-preview' }}"
                        title="{{ $file->original_name ?: 'Adjunto' }}"
                        data-file-url="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}"
                        data-file-download="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}"
                        data-file-name="{{ $file->original_name ?: 'Adjunto' }}"
                        data-file-mime="{{ $file->mime_type }}">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}" alt="Adjunto"
                            style="width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 1px solid #e6e8ec;">
                    </button>
                @else
                    <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}" target="_blank" class="d-inline-block"
                        title="{{ $file->original_name ?: 'Adjunto' }}">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}" alt="Adjunto"
                            style="width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 1px solid #e6e8ec;">
                    </a>
                @endif
            @else
                @if ($previewInModal ?? false)
                    <button type="button" class="badge badge-light-primary text-primary border-0 {{ $previewTriggerClass ?? 'js-ticket-file-preview js-maintenance-invoice-preview' }}"
                        title="{{ $file->original_name ?: 'Adjunto' }}"
                        data-file-url="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}"
                        data-file-download="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}"
                        data-file-name="{{ $file->original_name ?: 'Adjunto' }}"
                        data-file-mime="{{ $file->mime_type }}">
                        {{ strtoupper(pathinfo((string) $file->original_name, PATHINFO_EXTENSION) ?: 'Archivo') }}
                    </button>
                @else
                    <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}" target="_blank" download
                        class="badge badge-light-primary text-primary" title="{{ $file->original_name ?: 'Adjunto' }}">
                        {{ strtoupper(pathinfo((string) $file->original_name, PATHINFO_EXTENSION) ?: 'Archivo') }}
                    </a>
                @endif
            @endif
        @endforeach
    </div>
@endif
