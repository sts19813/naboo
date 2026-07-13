@php
    $requiredDocuments = collect($documents ?? []);
    $customDocuments = collect($customDocuments ?? []);
    $allDocuments = $requiredDocuments->concat($customDocuments)->values();
    $historicalVersions = $allDocuments
        ->flatMap(function ($document) {
            $versions = $document->relationLoaded('versions') ? $document->versions : collect();

            return $versions->skip(1)->map(fn ($version) => ['document' => $document, 'version' => $version]);
        })
        ->values();
    $expiredDocuments = $allDocuments->filter(fn ($document) => $document->expires_at && $document->expires_at->lt(today()))->values();
    $loadedDocuments = $allDocuments->filter(fn ($document) => filled($document->file_path) && !($document->expires_at && $document->expires_at->lt(today())))->values();
    $pendingDocuments = $allDocuments->filter(fn ($document) => blank($document->file_path))->values();
    $filledCount = $allDocuments->filter(fn ($document) => filled($document->file_path))->count();
    $loadedCount = $loadedDocuments->count();
    $expiredCount = $expiredDocuments->count();
    $pendingCount = $pendingDocuments->count();
    $totalCount = $allDocuments->count();
    $completionPercentage = $totalCount > 0 ? ($filledCount / $totalCount) * 100 : 0;
    $storagePercentage = min(100, $dossierStorage['percentage'] ?? 0);
    $storageTone = $storagePercentage >= 90 ? 'danger' : ($storagePercentage >= 70 ? 'warning' : 'primary');
    $documentLayout = ($documentLayout ?? request()->query('layout')) === 'cards' ? 'cards' : 'table';

    $fileIcon = function (?string $name): array {
        $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

        return match ($extension) {
            'zip' => ['ki-archive', 'text-warning', 'bg-light-warning', 'ZIP'],
            'jpg', 'jpeg', 'png', 'webp', 'gif' => ['ki-picture', 'text-success', 'bg-light-success', strtoupper($extension ?: 'IMG')],
            default => ['ki-document', 'text-danger', 'bg-light-danger', strtoupper($extension ?: 'PDF')],
        };
    };

    $buildFileAction = function (?string $path, ?string $name = null, ?string $mimeType = null): ?array {
        if (blank($path)) {
            return null;
        }

        $extension = strtolower(pathinfo((string) ($name ?: $path), PATHINFO_EXTENSION));
        $previewableExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif'];
        $previewableMimeTypes = ['application/pdf'];
        $isPreviewable = ($mimeType && (str_starts_with($mimeType, 'image/') || in_array($mimeType, $previewableMimeTypes, true)))
            || in_array($extension, $previewableExtensions, true);
        $downloadName = $name ?: basename($path);

        return [
            'url' => \Illuminate\Support\Facades\Storage::url($path),
            'is_previewable' => $isPreviewable,
            'target' => $isPreviewable ? '_blank' : null,
            'download_name' => $downloadName,
        ];
    };

    $documentState = function ($document): array {
        $isExpired = $document->expires_at && $document->expires_at->lt(today());
        $hasFile = filled($document->file_path);

        if ($isExpired) {
            return ['warning', 'Vencido'];
        }

        if ($hasFile) {
            return ['success', 'Cargado'];
        }

        return ['danger', 'Falta cargar'];
    };

    $tableLayoutUrl = request()->fullUrlWithQuery(['layout' => 'table']) . '#required-documents-pane';
    $cardLayoutUrl = request()->fullUrlWithQuery(['layout' => 'cards']) . '#required-documents-pane';
@endphp

@push('styles')
    <style>
        .dossier-drive .dossier-hero-card {
            position: relative;
            overflow: hidden;
            border: 0;
            background:
                radial-gradient(circle at top right, rgba(80, 205, 137, 0.18), transparent 36%),
                linear-gradient(135deg, rgba(0, 158, 247, 0.1), rgba(255, 255, 255, 0.98));
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        }

        .dossier-drive .dossier-hero-card::after {
            content: '';
            position: absolute;
            inset: auto -60px -80px auto;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: rgba(0, 158, 247, 0.08);
            filter: blur(4px);
        }

        .dossier-drive .dossier-hero-card .card-body,
        .dossier-drive .dossier-summary-card,
        .dossier-drive .dossier-sidebar-card,
        .dossier-drive .document-tile,
        .dossier-drive .upload-progress-item {
            position: relative;
        }

        .dossier-drive .dossier-summary-card,
        .dossier-drive .dossier-sidebar-card,
        .dossier-drive .dossier-table-card {
            border: 1px solid var(--bs-gray-200);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .dossier-drive .dossier-summary-card--success {
            border-color: rgba(80, 205, 137, 0.35);
        }

        .dossier-drive .dossier-summary-card--warning {
            border-color: rgba(255, 199, 0, 0.45);
        }

        .dossier-drive .dossier-summary-card--danger {
            border-color: rgba(241, 65, 108, 0.35);
        }

        .dossier-drive .drive-nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            border-radius: 0.85rem;
            color: var(--bs-gray-700);
            transition: background-color .2s ease, color .2s ease, transform .2s ease;
        }

        .dossier-drive .drive-nav-link.active,
        .dossier-drive .drive-nav-link:hover {
            background-color: var(--bs-primary-light);
            color: var(--bs-primary);
            transform: translateX(2px);
        }

        .dossier-drive .drive-nav-link.active .badge,
        .dossier-drive .drive-nav-link:hover .badge {
            background: rgba(0, 158, 247, 0.16);
        }

        .dossier-drive .document-dropzone {
            border: 2px dashed var(--bs-gray-300);
            border-radius: 1rem;
            min-height: 126px;
            cursor: pointer;
            transition: border-color .2s ease, background-color .2s ease, transform .2s ease;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 1));
        }

        .dossier-drive .document-dropzone:hover,
        .dossier-drive .document-dropzone.is-dragging {
            border-color: var(--bs-primary);
            background-color: var(--bs-primary-light);
            transform: translateY(-1px);
        }

        .dossier-drive .document-file-icon {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
        }

        .dossier-drive .document-tile {
            border: 1px solid var(--bs-gray-200);
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .dossier-drive .document-tile--success {
            border-color: rgba(80, 205, 137, 0.4);
            box-shadow: 0 14px 30px rgba(80, 205, 137, 0.08);
        }

        .dossier-drive .document-tile--warning {
            border-color: rgba(255, 199, 0, 0.5);
            box-shadow: 0 14px 30px rgba(255, 199, 0, 0.08);
        }

        .dossier-drive .document-tile--danger {
            border-color: rgba(241, 65, 108, 0.35);
            box-shadow: 0 14px 30px rgba(241, 65, 108, 0.07);
        }

        .dossier-drive .document-state-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1;
        }

        .dossier-drive .document-state-pill--success {
            color: var(--bs-success);
            background: rgba(80, 205, 137, 0.15);
        }

        .dossier-drive .document-state-pill--warning {
            color: #9a6700;
            background: rgba(255, 199, 0, 0.22);
        }

        .dossier-drive .document-state-pill--danger {
            color: var(--bs-danger);
            background: rgba(241, 65, 108, 0.12);
        }

        .dossier-drive .upload-progress-item {
            border: 1px solid var(--bs-gray-200);
            border-radius: 1rem;
            padding: 1rem;
            background: var(--bs-body-bg);
        }

        .dossier-drive .storage-mini-meter {
            height: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: #edf2f7;
        }

        .dossier-drive .storage-mini-meter-bar {
            height: 100%;
            border-radius: inherit;
        }

        .dossier-drive .dossier-status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.65rem 0.9rem;
            border-radius: 0.95rem;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .dossier-drive .dossier-status-chip__dot {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 999px;
        }

        .dossier-drive .dossier-status-chip__dot--success {
            background: var(--bs-success);
        }

        .dossier-drive .dossier-status-chip__dot--warning {
            background: var(--bs-warning);
        }

        .dossier-drive .dossier-status-chip__dot--danger {
            background: var(--bs-danger);
        }

        .dossier-drive .dossier-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .dossier-drive .dossier-empty-state {
            border: 1px dashed var(--bs-gray-300);
            border-radius: 1rem;
            padding: 2rem 1.5rem;
            text-align: center;
            color: var(--bs-gray-600);
            background: #fbfcfe;
        }

        .dossier-drive .dossier-file-meta {
            min-width: 0;
        }

        .dossier-drive .dossier-upload-panel {
            border-top: 1px dashed var(--bs-gray-300);
            margin-top: 1.5rem;
            padding-top: 1.5rem;
        }

        @media (max-width: 767.98px) {
            .dossier-drive.py-10 {
                padding-top: 1.25rem !important;
                padding-bottom: 1.25rem !important;
            }

            .dossier-drive .dossier-overview-card {
                border: 0;
                border-radius: 8px;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
            }

            .dossier-drive .dossier-overview-card .card-body {
                display: block !important;
                gap: 18px !important;
                padding: 20px !important;
            }

            .dossier-drive .dossier-overview-card h1 {
                font-size: 1.45rem;
                line-height: 1.2;
                margin-bottom: 12px !important;
                overflow-wrap: anywhere;
            }

            .dossier-drive .dossier-overview-card .fs-4 {
                font-size: 1.05rem !important;
                line-height: 1.25;
                overflow-wrap: anywhere;
            }

            .dossier-drive .dossier-overview-card .min-w-250px {
                min-width: 0 !important;
                width: 100%;
            }

            .dossier-drive .dossier-overview-card .d-flex.justify-content-between {
                gap: 12px;
            }

            .dossier-drive .dossier-overview-card .text-muted,
            .dossier-drive .dossier-overview-card .fw-bold {
                font-size: 0.86rem;
                line-height: 1.25;
            }

            .dossier-drive .dossier-overview-card .progress,
            .dossier-drive .dossier-overview-card .storage-mini-meter {
                width: 100%;
            }

            .dossier-drive .dossier-table-card {
                border: 0;
                border-radius: 0;
                box-shadow: none;
                background: transparent;
            }

            .dossier-drive .dossier-table-card > .card-header,
            .dossier-drive .dossier-history-card > .card-header {
                padding: 18px 0 10px !important;
            }

            .dossier-drive .dossier-table-card > .card-body,
            .dossier-drive .dossier-history-card > .card-body {
                padding: 0 !important;
            }

            .dossier-drive .dossier-table-card .card-toolbar {
                width: 100%;
                margin-top: 10px;
            }

            .dossier-drive .dossier-table-card .card-toolbar .btn {
                width: 100%;
                border-radius: 8px;
            }

            .dossier-drive .dossier-table-card .table-responsive,
            .dossier-drive .dossier-history-card .table-responsive {
                overflow: visible;
            }

            .dossier-drive .dossier-mobile-table,
            .dossier-drive .dossier-mobile-table tbody {
                display: block;
                width: 100% !important;
            }

            .dossier-drive .dossier-mobile-table {
                border: 0 !important;
                border-collapse: separate !important;
                border-spacing: 0 !important;
            }

            .dossier-drive .dossier-mobile-table thead {
                display: none;
            }

            .dossier-drive .dossier-mobile-table tbody {
                display: grid;
                gap: 14px;
            }

            .dossier-drive .dossier-mobile-table tbody tr {
                display: block;
                padding: 16px !important;
                border: 1px solid #e8eef7;
                border-radius: 8px;
                background: #fff !important;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
                overflow: hidden;
            }

            .dossier-drive .dossier-mobile-table tbody td {
                display: grid;
                grid-template-columns: minmax(82px, max-content) minmax(0, 1fr);
                align-items: center;
                gap: 12px;
                min-width: 0;
                padding: 10px 0 !important;
                border-top: 1px solid #f0f3f8 !important;
                border-right: 0 !important;
                border-left: 0 !important;
                background: transparent !important;
                text-align: right !important;
            }

            .dossier-drive .dossier-mobile-table tbody td::before {
                color: #8b96b2;
                font-size: 0.66rem;
                font-weight: 800;
                letter-spacing: 0.04em;
                line-height: 1.25;
                text-align: left;
                text-transform: uppercase;
                justify-self: start;
            }

            .dossier-drive .dossier-mobile-table tbody td > :not(style):not(script) {
                justify-self: end;
            }

            .dossier-drive .dossier-mobile-table tbody td:first-child {
                display: block;
                padding-top: 0 !important;
                padding-bottom: 12px !important;
                border-top: 0 !important;
                text-align: left !important;
            }

            .dossier-drive .dossier-mobile-table tbody td:first-child::before,
            .dossier-drive .dossier-mobile-table tbody td:last-child::before {
                content: none;
            }

            .dossier-drive .dossier-documents-table tbody td:nth-child(2)::before {
                content: 'Archivo';
            }

            .dossier-drive .dossier-documents-table tbody td:nth-child(3)::before {
                content: 'Vence';
            }

            .dossier-drive .dossier-documents-table tbody td:nth-child(4)::before,
            .dossier-drive .dossier-history-table tbody td:nth-child(4)::before {
                content: 'Fecha';
            }

            .dossier-drive .dossier-history-table tbody td:nth-child(2)::before {
                content: 'Version';
            }

            .dossier-drive .dossier-history-table tbody td:nth-child(3)::before {
                content: 'Archivo';
            }

            .dossier-drive .dossier-mobile-table tbody td:last-child {
                margin-top: 6px;
                padding-top: 12px !important;
                border-top: 1px solid #e8eef7 !important;
            }

            .dossier-drive .dossier-mobile-table tbody td[colspan] {
                display: block;
                padding: 0 !important;
                border-top: 0 !important;
                text-align: center !important;
            }

            .dossier-drive .dossier-mobile-table tbody td[colspan]::before {
                content: none;
            }

            .dossier-drive .dossier-mobile-table tbody td:nth-child(2) > .d-flex,
            .dossier-drive .dossier-history-table tbody td:nth-child(3) > .d-flex {
                max-width: 100%;
                min-width: 0;
                justify-content: flex-end;
            }

            .dossier-drive .dossier-mobile-table .document-file-icon {
                width: 38px;
                height: 38px;
                flex-basis: 38px;
            }

            .dossier-drive .dossier-mobile-table .dossier-file-meta,
            .dossier-drive .dossier-mobile-table .min-w-0 {
                min-width: 0;
            }

            .dossier-drive .dossier-mobile-table .dossier-file-meta a,
            .dossier-drive .dossier-mobile-table .min-w-0 a {
                display: block;
                max-width: 100%;
                text-align: right;
                overflow-wrap: anywhere;
            }

            .dossier-drive .dossier-mobile-table .fw-bold,
            .dossier-drive .dossier-mobile-table td {
                overflow-wrap: anywhere;
            }

            .dossier-drive .dossier-mobile-table .text-muted,
            .dossier-drive .dossier-mobile-table .fs-8 {
                font-size: 0.76rem !important;
                line-height: 1.25;
            }

            .dossier-drive .dossier-mobile-table td:last-ch ild > .d-inline-flex,
            .dossier-drive .dossier-history-table td:last-child {
                display: inline-flex !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                width: 100%;
            }

            .dossier-drive .dossier-mobile-table td:last-child form {
                display: contents !important;
            }

            .dossier-drive .dossier-mobile-table td:last-child .btn {
                width: 100%;
                min-width: 0;
                height: 46px;
                border-radius: 8px;
                padding: 8px;
            }

            .dossier-drive .dossier-mobile-table td:last-child .btn:nth-last-child(1):nth-child(odd) {
                grid-column: 1 / -1;
            }
        }
    </style>
@endpush

<div class="py-10 property-module dossier-drive">
    <div class="mb-8">
        <a href="{{ $backUrl }}" class="text-gray-600 text-hover-primary fw-semibold">
            <i class="ki-outline ki-arrow-left fs-4 me-1"></i> {{ $backLabel }}
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center p-5 mb-8">
            <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
            <div class="fw-semibold">{{ session('success') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-8">
            <div class="fw-bold mb-2">Hay errores en el formulario:</div>
            <ul class="mb-0 ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

   
    <div class="card mb-8 dossier-overview-card">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-6 p-8">
            <div>
                <h1 class="mb-2 fw-bold">{{ $title }}</h1>
                <div class="fs-4 fw-bold text-gray-900">{{ $entityName }}</div>
                <div class="text-muted">{{ $entityMeta }}</div>
            </div>
            <div class="min-w-250px">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Documentos cargados</span>
                    <span class="fw-bold">{{ $filledCount }}/{{ $totalCount }}</span>
                </div>
                <div class="progress h-8px mb-4">
                    <div class="progress-bar bg-primary" style="width: {{ $totalCount > 0 ? ($filledCount / $totalCount) * 100 : 0 }}%"></div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Espacio usado</span>
                    <span class="fw-bold">{{ $dossierStorage['used_label'] ?? '0 B' }}</span>
                </div>
                <div class="storage-mini-meter">
                    <div class="storage-mini-meter-bar" style="width: {{ $storagePercentage }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-8">
        <div class="col-xl-3">
            <div class="card dossier-sidebar-card">
                <div class="card-body p-5">
                    <div class="d-flex flex-column gap-2" role="tablist">
                        <button class="drive-nav-link active border-0 text-start bg-transparent px-4 py-3 fw-semibold"
                            data-bs-toggle="tab" data-bs-target="#required-documents-pane" type="button">
                            <span><i class="ki-outline ki-folder fs-2 me-2"></i>Documentos</span>
                            <span class="badge badge-light">{{ $allDocuments->count() }}</span>
                        </button>
                        <button class="drive-nav-link border-0 text-start bg-transparent px-4 py-3 fw-semibold"
                            data-bs-toggle="tab" data-bs-target="#historical-documents-pane" type="button">
                            <span><i class="ki-outline ki-time fs-2 me-2"></i>Historico</span>
                            <span class="badge badge-light">{{ $historicalVersions->count() }}</span>
                        </button>
                        <button class="drive-nav-link border-0 text-start bg-transparent px-4 py-3 fw-semibold"
                            data-bs-toggle="tab" data-bs-target="#expired-documents-pane" type="button">
                            <span><i class="ki-outline ki-calendar-tick fs-2 me-2"></i>Vencidos</span>
                            <span class="badge badge-light-warning text-warning">{{ $expiredDocuments->count() }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-9">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="required-documents-pane">
                    @if ($documentLayout === 'table')
                        <div class="card dossier-table-card">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title flex-column align-items-start">
                                    <h3 class="fw-bold mb-1">Documentos actuales</h3>
                                    <div class="text-muted fs-7">Vista tipo historial para revisar todo el expediente actual de un vistazo.</div>
                                </div>
                                <div class="card-toolbar">
                                    <a href="{{ $cardLayoutUrl }}" class="btn btn-light-primary btn-sm">
                                        <i class="ki-outline ki-element-11 fs-4 me-1"></i>Ver vista actual
                                    </a>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-dashed align-middle dossier-mobile-table dossier-documents-table">
                                        <thead>
                                            <tr class="text-muted text-uppercase fs-8">
                                                <th>Documento</th>
                                                <th>Archivo</th>
                                                <th>Vence</th>
                                                <th>Fecha</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($allDocuments as $index => $document)
                                                @php
                                                    $versions = $document->relationLoaded('versions') ? $document->versions : collect();
                                                    $latestVersion = $versions->first();
                                                    $fileName = $latestVersion?->original_name;
                                                    $mimeType = $latestVersion?->mime_type;
                                                    $uploadedAt = $latestVersion?->uploaded_at ?: $document->uploaded_at;
                                                    [$stateTone, $stateLabel] = $documentState($document);
                                                    [$icon, $iconColor, $iconBg] = $fileIcon($fileName ?: $document->file_path);
                                                    $fileAction = filled($document->file_path) ? $buildFileAction($document->file_path, $fileName, $mimeType) : null;
                                                    $uploadInputId = 'table-upload-' . $entityType . '-' . $index;
                                                    $editModalId = 'edit-document-modal-' . $entityType . '-' . $index;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="d-flex flex-column gap-2">
                                                            <div class="fw-bold text-gray-900">{{ $document->label }}</div>
                                                            
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="document-file-icon rounded {{ $iconBg }} d-flex align-items-center justify-content-center">
                                                                <i class="ki-outline {{ $icon }} fs-3 {{ $iconColor }}"></i>
                                                            </div>
                                                            <div class="dossier-file-meta">
                                                                @if ($fileAction)
                                                                    <a href="{{ $fileAction['url'] }}"
                                                                        class="fw-semibold text-gray-800 text-hover-primary text-break"
                                                                        @if ($fileAction['target']) target="{{ $fileAction['target'] }}" rel="noopener" @endif
                                                                        @if (!$fileAction['is_previewable']) download="{{ $fileAction['download_name'] }}" @endif>
                                                                        {{ $fileName ?: 'Abrir archivo actual' }}
                                                                    </a>
                                                                    <div class="text-muted fs-8">
                                                                        {{ $fileAction['is_previewable'] ? 'Abrir en una nueva pestaña' : 'Descargar archivo' }}
                                                                    </div>
                                                                @else
                                                                    <span class="text-muted">Sin archivo vigente</span>
                                                                    <div class="text-muted fs-8">Sube un archivo desde acciones o cambia a vista detallada.</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>{{ $document->expires_at?->format('d/m/Y') ?: '-' }}</td>
                                                    <td>{{ $uploadedAt?->format('d/m/Y H:i') ?: '-' }}</td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex align-items-center gap-2">
                                                            @if ($fileAction)
                                                                <a href="{{ $fileAction['url'] }}"
                                                                    class="btn btn-icon btn-light btn-active-light-primary btn-sm"
                                                                    title="{{ $fileAction['is_previewable'] ? 'Ver' : 'Descargar' }}"
                                                                    @if ($fileAction['target']) target="{{ $fileAction['target'] }}" rel="noopener" @endif
                                                                    @if (!$fileAction['is_previewable']) download="{{ $fileAction['download_name'] }}" @endif>
                                                                    <i class="ki-outline {{ $fileAction['is_previewable'] ? 'ki-eye' : 'ki-file-down' }} fs-2"></i>
                                                                </a>

                                                                @if ($fileAction['is_previewable'])
                                                                    <a href="{{ $fileAction['url'] }}"
                                                                        download="{{ $fileAction['download_name'] }}"
                                                                        class="btn btn-icon btn-light btn-active-light-primary btn-sm"
                                                                        title="Descargar">
                                                                        <i class="ki-outline ki-file-down fs-2"></i>
                                                                    </a>
                                                                @endif
                                                            @endif

                                                            @if ($document->exists)
                                                                <form method="POST" action="{{ $uploadRouteResolver($document) }}"
                                                                    enctype="multipart/form-data"
                                                                    class="d-inline"
                                                                    data-dossier-upload-form
                                                                    data-max-upload-size="{{ $dossierUploadLimit['effective_bytes'] }}"
                                                                    data-max-upload-label="{{ $dossierUploadLimit['effective_label'] }}">
                                                                    @csrf
                                                                    <input type="hidden" name="expires_at" value="{{ $document->expires_at?->format('Y-m-d') }}">
                                                                    <input id="{{ $uploadInputId }}" type="file" name="file" class="d-none"
                                                                        accept=".pdf,.jpg,.jpeg,.png,.zip" data-dossier-file-input>
                                                                    <button type="button"
                                                                        class="btn btn-icon btn-light btn-active-light-primary btn-sm"
                                                                        title="{{ filled($document->file_path) ? 'Reemplazar archivo' : 'Cargar archivo' }}"
                                                                        data-inline-upload-trigger
                                                                        data-input-id="{{ $uploadInputId }}">
                                                                        <i class="ki-outline {{ filled($document->file_path) ? 'ki-arrows-circle' : 'ki-file-up' }} fs-2"></i>
                                                                    </button>
                                                                </form>
                                                            @endif

                                                            @if ($fileAction)
                                                                <button type="button"
                                                                    class="btn btn-icon btn-light btn-active-light-warning btn-sm"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#{{ $editModalId }}"
                                                                    title="Editar metadatos">
                                                                    <i class="ki-outline ki-pencil fs-2"></i>
                                                                </button>
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
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="py-10">
                                                        <div class="dossier-empty-state">
                                                            No hay documentos configurados para este expediente.
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="dossier-upload-panel">
                                    <div class="row g-4 align-items-center mb-4">
                                        <div class="col-lg-7">
                                            <h4 class="fw-bold text-gray-900 mb-1">Seguir cargando archivos al expediente</h4>
                                            <div class="text-muted fs-7">Usa esta zona para agregar documentos adicionales. Si necesitas un dropzone por documento, cambia a la vista detallada.</div>
                                        </div>
                                        <div class="col-lg-5">
                                            <div class="row g-3">
                                                <div class="col-md-7">
                                                    <label class="form-label fw-semibold">Nombre del documento</label>
                                                    <input type="text" name="label" form="custom-dossier-upload-{{ $entityType }}"
                                                        class="form-control form-control-solid"
                                                        placeholder="Opcional. Si lo dejas vacio se usa el nombre original del archivo"
                                                        data-custom-label-input>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label fw-semibold">Vencimiento</label>
                                                    <input type="date" name="expires_at" form="custom-dossier-upload-{{ $entityType }}"
                                                        class="form-control form-control-solid">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form id="custom-dossier-upload-{{ $entityType }}" method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data"
                                        data-dossier-upload-form
                                        data-custom-document-form
                                        data-max-upload-size="{{ $dossierUploadLimit['effective_bytes'] }}"
                                        data-max-upload-label="{{ $dossierUploadLimit['effective_label'] }}">
                                        @csrf
                                        <input id="custom-upload-{{ $entityType }}" type="file" name="file" class="d-none"
                                            accept=".pdf,.jpg,.jpeg,.png,.zip" data-dossier-file-input>
                                        <label for="custom-upload-{{ $entityType }}"
                                            class="document-dropzone d-flex align-items-center justify-content-center text-center p-5"
                                            data-document-dropzone>
                                            <span>
                                                <i class="ki-outline ki-file-up fs-2x text-gray-500 d-block mb-3"></i>
                                                <span class="fw-bold text-gray-900 d-block">Arrastra o selecciona archivos para seguir cargando el expediente</span>
                                                <span class="text-muted fs-8 d-block mt-2">
                                                    PDF, imagenes o ZIP hasta {{ $dossierUploadLimit['effective_label'] }}
                                                </span>
                                            </span>
                                        </label>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="card">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title flex-column align-items-start">
                                    <h3 class="fw-bold mb-1">Documentos principales</h3>
                                    <div class="text-muted fs-7">Vista detallada con un dropzone independiente por documento.</div>
                                </div>
                                <div class="card-toolbar">
                                    <a href="{{ $tableLayoutUrl }}" class="btn btn-light-primary btn-sm">
                                        <i class="ki-outline ki-row-horizontal fs-4 me-1"></i>Volver a tabla
                                    </a>
                                </div>
                            </div>
                            <div class="card-body pt-0 d-flex flex-column gap-4">
                                @forelse ($requiredDocuments as $document)
                                    @include('documents.partials.document-tile', ['document' => $document])
                                @empty
                                    <div class="dossier-empty-state">
                                        No hay documentos obligatorios configurados para este expediente.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="card mt-6">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title flex-column align-items-start">
                                    <h3 class="fw-bold mb-1">Documentos adicionales</h3>
                                    <div class="text-muted fs-7">Archivos extra que ayudan a complementar el expediente.</div>
                                </div>
                            </div>
                            <div class="card-body pt-0 d-flex flex-column gap-4">
                                @forelse ($customDocuments as $document)
                                    @include('documents.partials.document-tile', ['document' => $document])
                                @empty
                                    <div class="dossier-empty-state">
                                        Aun no hay documentos adicionales.
                                    </div>
                                @endforelse

                                <div class="dossier-upload-panel">
                                    <div class="row g-4 align-items-center mb-4">
                                        <div class="col-lg-7">
                                            <h4 class="fw-bold text-gray-900 mb-1">Agregar documento personalizado</h4>
                                            <div class="text-muted fs-7">Sube anexos, garantias u otros respaldos adicionales.</div>
                                        </div>
                                        <div class="col-lg-5">
                                            <div class="row g-3">
                                                <div class="col-md-7">
                                                    <label class="form-label fw-semibold">Nombre del documento</label>
                                                    <input type="text" name="label" form="custom-dossier-card-upload-{{ $entityType }}"
                                                        class="form-control form-control-solid"
                                                        placeholder="Opcional. Si lo dejas vacio se usa el nombre original del archivo"
                                                        data-custom-label-input>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label fw-semibold">Vencimiento</label>
                                                    <input type="date" name="expires_at" form="custom-dossier-card-upload-{{ $entityType }}"
                                                        class="form-control form-control-solid">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form id="custom-dossier-card-upload-{{ $entityType }}" method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data"
                                        data-dossier-upload-form
                                        data-custom-document-form
                                        data-max-upload-size="{{ $dossierUploadLimit['effective_bytes'] }}"
                                        data-max-upload-label="{{ $dossierUploadLimit['effective_label'] }}">
                                        @csrf
                                        <input id="custom-card-upload-{{ $entityType }}" type="file" name="file" class="d-none"
                                            accept=".pdf,.jpg,.jpeg,.png,.zip" data-dossier-file-input>
                                        <label for="custom-card-upload-{{ $entityType }}"
                                            class="document-dropzone d-flex align-items-center justify-content-center text-center p-5"
                                            data-document-dropzone>
                                            <span>
                                                <i class="ki-outline ki-file-up fs-2x text-gray-500 d-block mb-3"></i>
                                                <span class="fw-bold text-gray-900 d-block">Arrastra o selecciona archivos para el expediente</span>
                                                <span class="text-muted fs-8 d-block mt-2">
                                                    PDF, imagenes o ZIP hasta {{ $dossierUploadLimit['effective_label'] }}
                                                </span>
                                            </span>
                                        </label>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="expired-documents-pane">
                    <div class="card dossier-table-card">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column align-items-start">
                                <h3 class="fw-bold mb-1">Documentos vencidos</h3>
                                <div class="text-muted fs-7">Vista exclusiva de este expediente con los archivos que requieren renovacion.</div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle dossier-mobile-table dossier-documents-table">
                                    <thead>
                                        <tr class="text-muted text-uppercase fs-8">
                                            <th>Documento</th>
                                            <th>Archivo</th>
                                            <th>Vence</th>
                                            <th>Fecha</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($expiredDocuments as $index => $document)
                                            @php
                                                $versions = $document->relationLoaded('versions') ? $document->versions : collect();
                                                $latestVersion = $versions->first();
                                                $fileName = $latestVersion?->original_name;
                                                $mimeType = $latestVersion?->mime_type;
                                                $uploadedAt = $latestVersion?->uploaded_at ?: $document->uploaded_at;
                                                [$stateTone, $stateLabel] = $documentState($document);
                                                [$icon, $iconColor, $iconBg] = $fileIcon($fileName ?: $document->file_path);
                                                $fileAction = filled($document->file_path) ? $buildFileAction($document->file_path, $fileName, $mimeType) : null;
                                                $uploadInputId = 'expired-upload-' . $entityType . '-' . $index;
                                                $editModalId = 'edit-document-modal-' . $entityType . '-' . $allDocuments->search($document);
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column gap-2">
                                                        <div class="fw-bold text-gray-900">{{ $document->label }}</div>
                                                        <span class="document-state-pill document-state-pill--{{ $stateTone }}">{{ $stateLabel }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="document-file-icon rounded {{ $iconBg }} d-flex align-items-center justify-content-center">
                                                            <i class="ki-outline {{ $icon }} fs-3 {{ $iconColor }}"></i>
                                                        </div>
                                                        <div class="dossier-file-meta">
                                                            @if ($fileAction)
                                                                <a href="{{ $fileAction['url'] }}"
                                                                    class="fw-semibold text-gray-800 text-hover-primary text-break"
                                                                    @if ($fileAction['target']) target="{{ $fileAction['target'] }}" rel="noopener" @endif
                                                                    @if (!$fileAction['is_previewable']) download="{{ $fileAction['download_name'] }}" @endif>
                                                                    {{ $fileName ?: 'Abrir archivo actual' }}
                                                                </a>
                                                                <div class="text-muted fs-8">
                                                                    {{ $fileAction['is_previewable'] ? 'Abrir en una nueva pestaña' : 'Descargar archivo' }}
                                                                </div>
                                                            @else
                                                                <span class="text-muted">Sin archivo vigente</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $document->expires_at?->format('d/m/Y') ?: '-' }}</td>
                                                <td>{{ $uploadedAt?->format('d/m/Y H:i') ?: '-' }}</td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex align-items-center gap-2">
                                                        @if ($fileAction)
                                                            <a href="{{ $fileAction['url'] }}"
                                                                class="btn btn-icon btn-light btn-active-light-primary btn-sm"
                                                                title="{{ $fileAction['is_previewable'] ? 'Ver' : 'Descargar' }}"
                                                                @if ($fileAction['target']) target="{{ $fileAction['target'] }}" rel="noopener" @endif
                                                                @if (!$fileAction['is_previewable']) download="{{ $fileAction['download_name'] }}" @endif>
                                                                <i class="ki-outline {{ $fileAction['is_previewable'] ? 'ki-eye' : 'ki-file-down' }} fs-2"></i>
                                                            </a>

                                                            @if ($fileAction['is_previewable'])
                                                                <a href="{{ $fileAction['url'] }}"
                                                                    download="{{ $fileAction['download_name'] }}"
                                                                    class="btn btn-icon btn-light btn-active-light-primary btn-sm"
                                                                    title="Descargar">
                                                                    <i class="ki-outline ki-file-down fs-2"></i>
                                                                </a>
                                                            @endif
                                                        @endif

                                                        @if ($document->exists)
                                                            <form method="POST" action="{{ $uploadRouteResolver($document) }}"
                                                                enctype="multipart/form-data"
                                                                class="d-inline"
                                                                data-dossier-upload-form
                                                                data-max-upload-size="{{ $dossierUploadLimit['effective_bytes'] }}"
                                                                data-max-upload-label="{{ $dossierUploadLimit['effective_label'] }}">
                                                                @csrf
                                                                <input type="hidden" name="expires_at" value="{{ $document->expires_at?->format('Y-m-d') }}">
                                                                <input id="{{ $uploadInputId }}" type="file" name="file" class="d-none"
                                                                    accept=".pdf,.jpg,.jpeg,.png,.zip" data-dossier-file-input>
                                                                <button type="button"
                                                                    class="btn btn-icon btn-light btn-active-light-primary btn-sm"
                                                                    title="Reemplazar archivo"
                                                                    data-inline-upload-trigger
                                                                    data-input-id="{{ $uploadInputId }}">
                                                                    <i class="ki-outline ki-arrows-circle fs-2"></i>
                                                                </button>
                                                            </form>
                                                        @endif

                                                        @if ($fileAction)
                                                            <button type="button"
                                                                class="btn btn-icon btn-light btn-active-light-warning btn-sm"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#{{ $editModalId }}"
                                                                title="Editar metadatos">
                                                                <i class="ki-outline ki-pencil fs-2"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="py-10">
                                                    <div class="dossier-empty-state">
                                                        Este expediente no tiene documentos vencidos.
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="historical-documents-pane">
                    <div class="card dossier-history-card">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column align-items-start">
                                <h3 class="fw-bold mb-1">Versiones anteriores</h3>
                                <div class="text-muted fs-7">Consulta el historial de archivos reemplazados y descarga cualquier version anterior.</div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle dossier-mobile-table dossier-history-table">
                                    <thead>
                                        <tr class="text-muted text-uppercase fs-8">
                                            <th>Documento</th>
                                            <th>Version</th>
                                            <th>Archivo</th>
                                            <th>Fecha</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($historicalVersions as $item)
                                            @php
                                                $document = $item['document'];
                                                $version = $item['version'];
                                                $fileAction = $buildFileAction($version->file_path, $version->original_name, $version->mime_type);
                                                [$versionIcon, $versionIconColor, $versionIconBg] = $fileIcon($version->original_name);
                                            @endphp
                                            <tr>
                                                <td class="fw-bold text-gray-900">{{ $document->label }}</td>
                                                <td>v{{ $version->version_number }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="document-file-icon rounded {{ $versionIconBg }} d-flex align-items-center justify-content-center">
                                                            <i class="ki-outline {{ $versionIcon }} fs-3 {{ $versionIconColor }}"></i>
                                                        </div>
                                                        <div class="min-w-0">
                                                            @if ($fileAction)
                                                                <a href="{{ $fileAction['url'] }}"
                                                                    class="fw-semibold text-gray-800 text-hover-primary text-break"
                                                                    @if ($fileAction['target']) target="{{ $fileAction['target'] }}" rel="noopener" @endif
                                                                    @if (!$fileAction['is_previewable']) download="{{ $fileAction['download_name'] }}" @endif>
                                                                    {{ $version->original_name }}
                                                                </a>
                                                                <div class="text-muted fs-8">
                                                                    {{ $fileAction['is_previewable'] ? 'Abrir en una nueva pestaña' : 'Descargar archivo' }}
                                                                </div>
                                                            @else
                                                                <span class="text-muted">{{ $version->original_name }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $version->uploaded_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                                <td class="text-end">
                                                    @if ($fileAction)
                                                        <a href="{{ $fileAction['url'] }}"
                                                            class="btn btn-icon btn-light btn-active-light-primary btn-sm"
                                                            title="{{ $fileAction['is_previewable'] ? 'Ver' : 'Descargar' }}"
                                                            @if ($fileAction['target']) target="{{ $fileAction['target'] }}" rel="noopener" @endif
                                                            @if (!$fileAction['is_previewable']) download="{{ $fileAction['download_name'] }}" @endif>
                                                            <i class="ki-outline {{ $fileAction['is_previewable'] ? 'ki-eye' : 'ki-file-down' }} fs-2"></i>
                                                        </a>

                                                        @if ($fileAction['is_previewable'])
                                                            <a href="{{ $fileAction['url'] }}"
                                                                download="{{ $version->original_name }}"
                                                                class="btn btn-icon btn-light btn-active-light-primary btn-sm"
                                                                title="Descargar">
                                                                <i class="ki-outline ki-file-down fs-2"></i>
                                                            </a>
                                                        @endif
                                                    @endif

                                                    @if ($canDeleteDossierFiles)
                                                        <form method="POST" action="{{ $versionDestroyRouteResolver($document, $version) }}" class="d-inline"
                                                            onsubmit="return confirm('Eliminar esta version del expediente?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-icon btn-light btn-active-light-danger btn-sm" title="Eliminar">
                                                                <i class="ki-outline ki-trash fs-2"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="py-10">
                                                    <div class="dossier-empty-state">
                                                        No hay documentos reemplazados.
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <div class="text-muted fw-semibold mb-3" data-document-file-summary>Sin cargas activas.</div>
                <div class="d-flex flex-column gap-3" data-document-upload-progress-list></div>
            </div>
        </div>
    </div>

    @foreach ($allDocuments as $index => $document)
        @php
            $versions = $document->relationLoaded('versions') ? $document->versions : collect();
            $latestVersion = $versions->first();
            $editModalId = 'edit-document-modal-' . $entityType . '-' . $index;
        @endphp
        @if ($latestVersion && $document->exists)
            <div class="modal fade" id="{{ $editModalId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ $metadataUpdateRouteResolver($document) }}">
                            @csrf
                            @method('PATCH')
                            <div class="modal-header">
                                <div>
                                    <h3 class="modal-title">Editar documento</h3>
                                    <div class="text-muted fs-7 mt-1">{{ $document->label }}</div>
                                </div>
                                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                                    <i class="ki-outline ki-cross fs-1"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-5">
                                    <label class="form-label fw-semibold">Nombre del archivo</label>
                                    <input type="text" name="file_name" class="form-control form-control-solid"
                                        value="{{ old('file_name', $latestVersion->original_name) }}" required>
                                    <div class="form-text">Solo cambia el nombre visible del archivo dentro del expediente.</div>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold">Fecha de vencimiento</label>
                                    <input type="date" name="expires_at" class="form-control form-control-solid"
                                        value="{{ old('expires_at', $document->expires_at?->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            var summary = document.querySelector('[data-document-file-summary]');
            var progressList = document.querySelector('[data-document-upload-progress-list]');
            var activeUploads = 0;
            var finishedUploads = 0;
            var failedUploads = 0;

            function showToast(type, message) {
                if (window.SuWorkToast?.fire) {
                    window.SuWorkToast.fire(type, message);
                    return;
                }
                window.alert(message);
            }

            function formatBytes(bytes) {
                if (!bytes) return '0 B';
                var units = ['B', 'KB', 'MB', 'GB'];
                var size = bytes;
                var unitIndex = 0;

                while (size >= 1024 && unitIndex < units.length - 1) {
                    size = size / 1024;
                    unitIndex++;
                }

                return (unitIndex === 0 ? size : size.toFixed(1)) + ' ' + units[unitIndex];
            }

            function createProgressItem(file) {
                var item = document.createElement('div');
                item.className = 'upload-progress-item';
                item.innerHTML =
                    '<div class="d-flex justify-content-between align-items-center mb-2">' +
                        '<div class="min-w-0">' +
                            '<div class="fw-bold text-gray-900 text-truncate"></div>' +
                            '<div class="text-muted fs-7"></div>' +
                        '</div>' +
                        '<span class="badge badge-light-primary" data-upload-status>0%</span>' +
                    '</div>' +
                    '<div class="progress h-6px">' +
                        '<div class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>' +
                    '</div>';

                item.querySelector('.fw-bold').textContent = file.name;
                item.querySelector('.text-muted').textContent = formatBytes(file.size);
                progressList.prepend(item);

                return item;
            }

            function setProgress(item, percent, text, badgeClass) {
                var bar = item.querySelector('.progress-bar');
                var status = item.querySelector('[data-upload-status]');
                bar.style.width = percent + '%';
                bar.setAttribute('aria-valuenow', percent);
                status.textContent = text || percent + '%';
                if (badgeClass) status.className = 'badge ' + badgeClass;
            }

            function firstValidationError(response) {
                if (!response || !response.errors) return response?.message || null;
                var keys = Object.keys(response.errors);
                return keys.length ? response.errors[keys[0]][0] : response.message || null;
            }

            function uploadError(request, maxLabel) {
                if (request.status === 413) return 'El archivo supera el limite permitido. Maximo: ' + maxLabel + '.';
                if (request.status === 401 || request.status === 419) return 'Tu sesion expiro. Actualiza la pagina.';
                if (request.responseText) {
                    try {
                        return firstValidationError(JSON.parse(request.responseText)) || 'No se pudo cargar el archivo.';
                    } catch (error) {
                        return 'No se pudo cargar el archivo.';
                    }
                }
                return 'No se pudo cargar el archivo.';
            }

            function finishUpload(item, ok, message) {
                finishedUploads++;
                failedUploads += ok ? 0 : 1;
                setProgress(item, 100, ok ? 'Cargado' : 'Error', ok ? 'badge-light-success' : 'badge-light-danger');

                if (!ok && message) {
                    var errorText = document.createElement('div');
                    errorText.className = 'text-danger fs-7 mt-2';
                    errorText.textContent = message;
                    item.appendChild(errorText);
                }

                summary.textContent = finishedUploads + ' de ' + activeUploads + ' archivo(s) procesado(s).';

                if (finishedUploads === activeUploads && failedUploads === 0) {
                    showToast('success', 'Documento cargado correctamente.');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 650);
                } else if (finishedUploads === activeUploads) {
                    showToast('danger', message || 'No se pudieron cargar algunos archivos.');
                }
            }

            function labelFromFile(file) {
                return (file.name || 'Documento').replace(/\.[^/.]+$/, '').replace(/[_-]+/g, ' ').trim() || 'Documento';
            }

            function uploadFile(form, file) {
                var maxSize = parseInt(form.dataset.maxUploadSize || '0', 10);
                var maxLabel = form.dataset.maxUploadLabel || 'limite configurado';
                var item = createProgressItem(file);

                if (maxSize > 0 && file.size > maxSize) {
                    finishUpload(item, false, 'El archivo pesa ' + formatBytes(file.size) + '. El maximo permitido es ' + maxLabel + '.');
                    return;
                }

                if (form.matches('[data-custom-document-form]')) {
                    var labelInput = form.querySelector('[data-custom-label-input]');
                    if (!labelInput && form.id) {
                        labelInput = document.querySelector('[data-custom-label-input][form="' + form.id + '"]');
                    }
                    if (labelInput && !labelInput.value.trim()) {
                        labelInput.value = labelFromFile(file);
                    }
                }

                var request = new XMLHttpRequest();
                var formData = new FormData(form);
                formData.set('file', file);

                request.open('POST', form.action, true);
                request.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                request.setRequestHeader('Accept', 'application/json');

                request.upload.addEventListener('progress', function (event) {
                    if (!event.lengthComputable) return;
                    setProgress(item, Math.min(99, Math.round((event.loaded / event.total) * 100)));
                });

                request.addEventListener('load', function () {
                    var ok = request.status >= 200 && request.status < 300;
                    finishUpload(item, ok, ok ? '' : uploadError(request, maxLabel));
                });

                request.addEventListener('error', function () {
                    finishUpload(item, false, 'Error de conexion al cargar el archivo.');
                });

                request.send(formData);
            }

            function uploadFiles(form, files) {
                files = Array.prototype.slice.call(files || []);
                if (!files.length) return;

                if (finishedUploads === activeUploads) {
                    activeUploads = 0;
                    finishedUploads = 0;
                    failedUploads = 0;
                    progressList.innerHTML = '';
                }

                activeUploads += files.length;
                summary.textContent = 'Cargando ' + activeUploads + ' archivo(s)...';
                files.forEach(function (file) {
                    uploadFile(form, file);
                });
            }

            document.querySelectorAll('[data-dossier-upload-form]').forEach(function (form) {
                var input = form.querySelector('[data-dossier-file-input]');
                var dropzone = form.querySelector('[data-document-dropzone]');

                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                });

                input?.addEventListener('change', function () {
                    uploadFiles(form, input.files);
                    input.value = '';
                });

                if (!dropzone) return;

                ['dragenter', 'dragover'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        dropzone.classList.add('is-dragging');
                    });
                });

                ['dragleave', 'drop'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        dropzone.classList.remove('is-dragging');
                    });
                });

                dropzone.addEventListener('drop', function (event) {
                    uploadFiles(form, event.dataTransfer.files);
                });
            });

            document.querySelectorAll('[data-inline-upload-trigger]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var inputId = button.getAttribute('data-input-id');
                    if (!inputId) return;
                    document.getElementById(inputId)?.click();
                });
            });
        });
    </script>
@endpush
