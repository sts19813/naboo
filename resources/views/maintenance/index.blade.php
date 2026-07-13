@extends('layouts.app')

@section('title', 'Mantenimiento | SuWork')

@section('content')
    <div class="maintenance-module py-8">
        @php
            $calendarEvents = $calendarItems->map(function ($item) {
                return [
                    'title' => ($item->currentProvider?->name ?? 'Sin asignar') . ' · ' . $item->display_reference,
                    'start' => $item->scheduled_visit_at?->toIso8601String(),
                    'url' => route('maintenance.show', $item),
                    'extendedProps' => [
                        'property' => $item->property?->internal_name ?? '-',
                        'ticket' => $item->title,
                        'priority' => $item->priority,
                    ],
                ];
            })->values()->all();

            $statusTone = fn ($value) => match ($value) {
                'completado' => 'green',
                'cancelado' => 'red',
                'en_proceso' => 'purple',
                'programado', 'asignado' => 'blue',
                'pendiente', 'revisado', 'esperando_material', 'reabierto' => 'amber',
                default => 'neutral',
            };
            $priorityTone = fn ($value) => match ($value) {
                'baja' => 'green',
                'media' => 'blue',
                'alta' => 'amber',
                'urgente' => 'red',
                default => 'neutral',
            };

            $roleTitle = match ($role) {
                'inquilino' => 'Mis reportes de mantenimiento',
                'tecnico' => 'Mis tickets asignados',
                default => 'Mantenimiento',
            };
            $roleSubtitle = match ($role) {
                'inquilino' => 'Consulta tus tickets y levanta nuevos reportes para tus propiedades asignadas.',
                'tecnico' => 'Agenda de campo, evidencias, estados y comunicación del ticket.',
                default => 'Operación diaria de tickets, técnicos, propiedades y visitas programadas.',
            };

            $ticketCollection = $tickets->getCollection();
            $ticketBuckets = [
                'attention' => collect(),
                'work' => collect(),
                'scheduled' => collect(),
                'done' => collect(),
                'other' => collect(),
            ];
            foreach ($ticketCollection as $ticketRow) {
                $bucket = 'other';
                if (in_array($ticketRow->status, ['completado', 'cancelado'], true)) {
                    $bucket = 'done';
                } elseif ($ticketRow->priority === 'urgente' || !$ticketRow->currentProvider || in_array($ticketRow->status, ['pendiente', 'reabierto'], true)) {
                    $bucket = 'attention';
                } elseif (in_array($ticketRow->status, ['en_proceso', 'esperando_material'], true)) {
                    $bucket = 'work';
                } elseif (in_array($ticketRow->status, ['asignado', 'programado', 'revisado'], true)) {
                    $bucket = 'scheduled';
                }
                $ticketBuckets[$bucket]->push($ticketRow);
            }
            $bucketMeta = [
                'attention' => ['title' => 'Requiere atención', 'hint' => 'Urgentes, pendientes o sin técnico', 'icon' => 'bi-exclamation-triangle', 'tone' => 'red'],
                'work' => ['title' => 'En trabajo activo', 'hint' => 'Atención en proceso o esperando material', 'icon' => 'bi-tools', 'tone' => 'purple'],
                'scheduled' => ['title' => 'Agendados y revisados', 'hint' => 'Con técnico, revisión o visita programada', 'icon' => 'bi-calendar2-check', 'tone' => 'blue'],
                'done' => ['title' => 'Cerrados', 'hint' => 'Tickets completados o cancelados', 'icon' => 'bi-check2-circle', 'tone' => 'green'],
                'other' => ['title' => 'Otros tickets', 'hint' => 'Sin agrupación operativa', 'icon' => 'bi-list-task', 'tone' => 'neutral'],
            ];
            $kpis = [
                ['label' => 'Total', 'value' => number_format((int) ($metrics['total'] ?? 0)), 'sub' => 'Incidencias visibles', 'tone' => '#334155'],
                ['label' => 'Pendientes', 'value' => number_format((int) ($metrics['pending'] ?? 0)), 'sub' => 'Por atender', 'tone' => '#b45309'],
                ['label' => 'Urgentes', 'value' => number_format((int) ($metrics['urgent'] ?? 0)), 'sub' => 'Prioridad urgente', 'tone' => '#b42318'],
                ['label' => 'En proceso', 'value' => number_format((int) ($metrics['in_progress'] ?? 0)), 'sub' => 'Trabajo activo', 'tone' => '#6d28d9'],
                ['label' => 'Completados', 'value' => number_format((int) ($metrics['completed'] ?? 0)), 'sub' => 'Histórico filtrado', 'tone' => '#15803d'],
                [
                    'label' => 'Resolución',
                    'value' => $metrics['avg_resolution_hours'] !== null ? number_format((float) $metrics['avg_resolution_hours'], 2) . 'h' : '-',
                    'sub' => 'Promedio',
                    'tone' => '#1d4ed8',
                ],
            ];
        @endphp

        <div class="maintenance-page">
            @if (session('success'))
                <div class="alert alert-success mb-0">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger mb-0">{{ session('error') }}</div>
            @endif

            <div class="maintenance-hero">
                <div>
                    <div class="maintenance-kicker">Operaciones</div>
                    <h1 class="maintenance-title">{{ $roleTitle }}</h1>
                    <div class="maintenance-subtitle">
                        {{ $roleSubtitle }}
                        @if ($selectedProperty)
                            <span class="maintenance-chip maintenance-chip-blue ms-2">{{ $selectedProperty->internal_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="maintenance-actions">
                    @if (!$isTenant)
                        <button class="maintenance-plain-btn" type="button" data-bs-toggle="collapse"
                            data-bs-target="#maintenanceFiltersCollapse" aria-expanded="false"
                            aria-controls="maintenanceFiltersCollapse">
                            <i class="bi bi-sliders"></i> Filtros
                        </button>
                        @if ($canManageProviders)
                            <a class="maintenance-soft-btn" href="{{ route('maintenance.technicians.index') }}">
                                <i class="bi bi-person-gear"></i> Administración de técnicos
                            </a>
                        @endif
                    @endif
                    @if ($canCreateTicket)
                        <button class="maintenance-primary-btn" data-bs-toggle="modal" data-bs-target="#createMaintenanceTicketModal">
                            <i class="bi bi-plus-lg"></i> Nuevo ticket
                        </button>
                    @endif
                </div>
            </div>

            @if (!$isTenant)
                <div class="collapse" id="maintenanceFiltersCollapse">
                    <div class="maintenance-filter-panel">
                        <form class="row g-4 align-items-end" method="GET" action="{{ route('maintenance.index') }}">
                            <input type="hidden" name="tab" value="{{ $activeTab }}">
                            <div class="col-xl-3 col-md-6">
                                <label class="form-label">Buscar</label>
                                <input type="text" class="form-control" name="q" value="{{ $search }}"
                                    placeholder="Título, folio, propiedad">
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <label class="form-label">Propiedad</label>
                                <select class="form-select" name="property">
                                    <option value="">Todas</option>
                                    @foreach ($properties as $property)
                                        <option value="{{ $property->uuid }}" {{ $selectedProperty?->uuid === $property->uuid ? 'selected' : '' }}>
                                            {{ $property->internal_name }}{{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-4">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="status">
                                    @foreach ($statusOptions as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}" {{ $status === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-4">
                                <label class="form-label">Prioridad</label>
                                <select class="form-select" name="priority">
                                    @foreach ($priorityOptions as $priorityKey => $priorityLabel)
                                        <option value="{{ $priorityKey }}" {{ $priority === $priorityKey ? 'selected' : '' }}>{{ $priorityLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-4">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" name="category">
                                    @foreach ($categoryOptions as $categoryKey => $categoryLabel)
                                        <option value="{{ $categoryKey }}" {{ $category === $categoryKey ? 'selected' : '' }}>{{ $categoryLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Desde</label>
                                <input type="date" class="form-control" name="from" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hasta</label>
                                <input type="date" class="form-control" name="to" value="{{ $dateTo }}">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button class="maintenance-primary-btn">Aplicar filtros</button>
                            </div>
                            <div class="col-md-3 d-grid">
                                <a class="maintenance-plain-btn" href="{{ route('maintenance.index', ['tab' => $activeTab]) }}">Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="maintenance-kpi-strip">
                    @foreach ($kpis as $kpi)
                        <div class="maintenance-kpi">
                            <div class="maintenance-kpi-label">
                                <span class="maintenance-kpi-dot" style="background: {{ $kpi['tone'] }}"></span>
                                {{ $kpi['label'] }}
                            </div>
                            <div class="maintenance-kpi-value">{{ $kpi['value'] }}</div>
                            <div class="maintenance-kpi-sub">{{ $kpi['sub'] }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="maintenance-tenant-summary">
                    <div class="maintenance-kpi">
                        <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#334155"></span>Mis tickets</div>
                        <div class="maintenance-kpi-value">{{ number_format((int) ($metrics['total'] ?? 0)) }}</div>
                        <div class="maintenance-kpi-sub">De tus propiedades</div>
                    </div>
                    <div class="maintenance-kpi">
                        <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#b45309"></span>Pendientes</div>
                        <div class="maintenance-kpi-value">{{ number_format((int) ($metrics['pending'] ?? 0)) }}</div>
                        <div class="maintenance-kpi-sub">Por revisar</div>
                    </div>
                    <div class="maintenance-kpi">
                        <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#15803d"></span>Cerrados</div>
                        <div class="maintenance-kpi-value">{{ number_format((int) ($metrics['completed'] ?? 0)) }}</div>
                        <div class="maintenance-kpi-sub">Completados</div>
                    </div>
                </div>
            @endif

            <div class="maintenance-tabs">
                <a class="maintenance-tab {{ $activeTab === 'activos' ? 'active' : '' }}"
                    href="{{ route('maintenance.index', array_merge(request()->except(['page', 'tab']), ['tab' => 'activos'])) }}">
                    Activos
                </a>
                <a class="maintenance-tab {{ $activeTab === 'completados' ? 'active' : '' }}"
                    href="{{ route('maintenance.index', array_merge(request()->except(['page', 'tab']), ['tab' => 'completados'])) }}">
                    Completados
                </a>
                <a class="maintenance-tab {{ $activeTab === 'cancelados' ? 'active' : '' }}"
                    href="{{ route('maintenance.index', array_merge(request()->except(['page', 'tab']), ['tab' => 'cancelados'])) }}">
                    Cancelados
                </a>
            </div>

            <div class="maintenance-layout {{ $isTenant ? 'maintenance-layout-single' : '' }}">
                <div class="maintenance-worklist">
                    <div class="maintenance-panel">
                        <div class="maintenance-list-toolbar">
                            <div>
                                <div class="maintenance-list-title">{{ $isTenant ? 'Tus tickets' : 'Lista operativa' }}</div>
                                <div class="maintenance-list-count">
                                    Mostrando {{ $tickets->count() }} de {{ $tickets->total() }} tickets
                                </div>
                            </div>
                            @if (!$isTenant && ($search || $status || $priority || $category || $dateFrom || $dateTo || $selectedProperty))
                                <span class="maintenance-chip maintenance-chip-blue">Filtros activos</span>
                            @endif
                        </div>
                    </div>

                    @forelse ($ticketBuckets as $bucketKey => $bucketTickets)
                        @continue($bucketTickets->isEmpty())
                        @php $meta = $bucketMeta[$bucketKey]; @endphp
                        <div class="maintenance-group">
                            <div class="maintenance-group-header">
                                <span class="maintenance-group-icon maintenance-chip-{{ $meta['tone'] }}">
                                    <i class="bi {{ $meta['icon'] }}"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="maintenance-group-title">{{ $meta['title'] }}</div>
                                    <div class="maintenance-group-hint">{{ $meta['hint'] }}</div>
                                </div>
                                <span class="maintenance-chip maintenance-chip-neutral ms-auto">{{ $bucketTickets->count() }}</span>
                            </div>
                            <div class="maintenance-list-header">
                                <span></span>
                                <span>Folio</span>
                                <span>Ticket</span>
                                <span>Propiedad</span>
                                <span>Técnico</span>
                                <span>Estado</span>
                            </div>
                            @foreach ($bucketTickets as $ticket)
                                @php
                                    $providerName = $ticket->currentProvider?->name;
                                    $providerInitials = collect(explode(' ', trim((string) $providerName)))
                                        ->filter()
                                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                        ->take(2)
                                        ->implode('');
                                @endphp
                                <div class="maintenance-ticket-row" role="link" tabindex="0" data-maintenance-row-url="{{ route('maintenance.show', $ticket) }}">
                                    <span class="maintenance-priority-bar maintenance-priority-{{ $ticket->priority }}"></span>
                                    <span class="maintenance-priority-cell">
                                        <span class="maintenance-reference">#{{ $ticket->display_reference }}</span>
                                        @if ($canUpdateTicketMeta)
                                            <span class="dropdown maintenance-inline-dropdown mt-2" data-maintenance-row-action>
                                                <button class="maintenance-chip maintenance-chip-button maintenance-chip-{{ $priorityTone($ticket->priority) }} dropdown-toggle"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                                    aria-label="Cambiar urgencia de {{ $ticket->display_reference }}">
                                                    {{ \App\Models\MaintenanceTicket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority }}
                                                </button>
                                                <div class="dropdown-menu maintenance-inline-menu">
                                                    @foreach (\App\Models\MaintenanceTicket::PRIORITY_LABELS as $priorityKey => $priorityLabel)
                                                        <form method="POST" action="{{ route('maintenance.meta', $ticket) }}" class="js-maintenance-inline-meta">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="priority" value="{{ $priorityKey }}">
                                                            <button class="dropdown-item maintenance-inline-option {{ $ticket->priority === $priorityKey ? 'active' : '' }}"
                                                                type="submit" {{ $ticket->priority === $priorityKey ? 'disabled' : '' }}>
                                                                <span class="maintenance-chip maintenance-chip-{{ $priorityTone($priorityKey) }}">{{ $priorityLabel }}</span>
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            </span>
                                        @else
                                            <span class="maintenance-chip maintenance-chip-{{ $priorityTone($ticket->priority) }} mt-2">
                                                {{ \App\Models\MaintenanceTicket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority }}
                                            </span>
                                        @endif
                                    </span>
                                    <span class="maintenance-ticket-main">
                                        <span class="maintenance-ticket-name">{{ $ticket->title }}</span>
                                        <span class="maintenance-ticket-meta">
                                            <span><i class="bi bi-tools me-1"></i>{{ \App\Models\MaintenanceTicket::CATEGORY_LABELS[$ticket->category] ?? $ticket->category }}</span>
                                            <span><i class="bi bi-clock me-1"></i>{{ $ticket->reported_at?->format('d/m/Y H:i') ?: '-' }}</span>
                                            @if ((int) $ticket->messages_count > 0)
                                                <span><i class="bi bi-chat-dots me-1"></i>{{ (int) $ticket->messages_count }}</span>
                                            @endif
                                            @if ((int) $ticket->files_count > 0)
                                                <span><i class="bi bi-paperclip me-1"></i>{{ (int) $ticket->files_count }}</span>
                                            @endif
                                        </span>
                                    </span>
                                    <span class="maintenance-property-cell">
                                        <span class="maintenance-cell-icon"><i class="bi bi-house-door"></i></span>
                                        <span class="min-w-0">
                                            <span class="maintenance-cell-title">{{ $ticket->property?->internal_name ?? '-' }}</span>
                                            <span class="maintenance-cell-subtitle">{{ $ticket->property?->internal_reference ?: 'Sin referencia' }}</span>
                                        </span>
                                    </span>
                                    <span class="maintenance-provider-cell">
                                        @if ($canUpdateTicketMeta)
                                            <span class="dropdown maintenance-inline-dropdown maintenance-provider-dropdown" data-maintenance-row-action>
                                                <button class="maintenance-provider-trigger dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                    aria-label="Cambiar técnico de {{ $ticket->display_reference }}">
                                                    @if ($ticket->currentProvider)
                                                        <span class="maintenance-avatar">{{ $providerInitials ?: 'T' }}</span>
                                                        <span class="min-w-0">
                                                            <span class="maintenance-cell-title">{{ $ticket->currentProvider->name }}</span>
                                                            <span class="maintenance-cell-subtitle">{{ \App\Models\MaintenanceProvider::TYPE_LABELS[$ticket->currentProvider->type] ?? $ticket->currentProvider->type }}</span>
                                                        </span>
                                                    @else
                                                        <span class="maintenance-cell-icon"><i class="bi bi-person-plus"></i></span>
                                                        <span class="maintenance-cell-title text-warning">Sin asignar</span>
                                                    @endif
                                                </button>
                                                <div class="dropdown-menu maintenance-inline-menu maintenance-provider-menu">
                                                    <form method="POST" action="{{ route('maintenance.meta', $ticket) }}" class="js-maintenance-inline-meta">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="provider_id" value="">
                                                        <button class="dropdown-item maintenance-provider-option {{ !$ticket->currentProvider ? 'active' : '' }}"
                                                            type="submit" {{ !$ticket->currentProvider ? 'disabled' : '' }}>
                                                            <span class="maintenance-cell-icon"><i class="bi bi-person-dash"></i></span>
                                                            <span class="min-w-0">
                                                                <span class="maintenance-cell-title">Sin asignar</span>
                                                                <span class="maintenance-cell-subtitle">Quitar técnico actual</span>
                                                            </span>
                                                        </button>
                                                    </form>
                                                    @foreach ($providers as $provider)
                                                        @php
                                                            $optionInitials = collect(explode(' ', trim((string) $provider->name)))
                                                                ->filter()
                                                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                                                ->take(2)
                                                                ->implode('');
                                                        @endphp
                                                        <form method="POST" action="{{ route('maintenance.meta', $ticket) }}" class="js-maintenance-inline-meta">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                                                            <input type="hidden" name="notes" value="Asignación rápida desde panel">
                                                            <button class="dropdown-item maintenance-provider-option {{ (int) $ticket->current_provider_id === (int) $provider->id ? 'active' : '' }}"
                                                                type="submit" {{ (int) $ticket->current_provider_id === (int) $provider->id ? 'disabled' : '' }}>
                                                                <span class="maintenance-avatar">{{ $optionInitials ?: 'T' }}</span>
                                                                <span class="min-w-0">
                                                                    <span class="maintenance-cell-title">{{ $provider->name }}</span>
                                                                    <span class="maintenance-cell-subtitle">
                                                                        {{ \App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type }}{{ $provider->is_active ? '' : ' · Inactivo' }}
                                                                    </span>
                                                                </span>
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            </span>
                                        @elseif ($ticket->currentProvider)
                                            <span class="maintenance-avatar">{{ $providerInitials ?: 'T' }}</span>
                                            <span class="min-w-0">
                                                <span class="maintenance-cell-title">{{ $ticket->currentProvider->name }}</span>
                                                <span class="maintenance-cell-subtitle">{{ \App\Models\MaintenanceProvider::TYPE_LABELS[$ticket->currentProvider->type] ?? $ticket->currentProvider->type }}</span>
                                            </span>
                                        @else
                                            <span class="maintenance-cell-icon"><i class="bi bi-person-plus"></i></span>
                                            <span class="maintenance-cell-title text-warning">Sin asignar</span>
                                        @endif
                                    </span>
                                    <span>
                                        <span class="maintenance-chip maintenance-chip-{{ $statusTone($ticket->status) }}">
                                            {{ \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                                        </span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="maintenance-panel maintenance-empty">
                            No hay tickets de mantenimiento.
                        </div>
                    @endforelse

                    @if ($ticketCollection->isEmpty())
                        <div class="maintenance-panel maintenance-empty">
                            {{ $isTenant ? 'Aún no tienes tickets registrados.' : 'No hay tickets de mantenimiento para los filtros seleccionados.' }}
                        </div>
                    @endif

                    <div class="maintenance-pagination">
                        {{ $tickets->links() }}
                    </div>
                </div>

                @if (!$isTenant)
                    <aside class="maintenance-side">
                        <div class="maintenance-side-panel">
                            <h3 class="maintenance-panel-title">Agenda del equipo</h3>
                            <div id="maintenance-team-calendar" style="min-height: 470px;"></div>
                        </div>

                        <div class="maintenance-side-panel">
                            <h3 class="maintenance-panel-title">Próximas visitas</h3>
                            @forelse ($calendarItems->take(5) as $item)
                                <div class="maintenance-mini-row">
                                    <div class="min-w-0">
                                        <div class="maintenance-cell-title">{{ $item->title }}</div>
                                        <div class="maintenance-cell-subtitle">
                                            {{ $item->scheduled_visit_at?->format('d/m/Y H:i') }} · {{ $item->property?->internal_name ?? '-' }}
                                        </div>
                                    </div>
                                    <a class="maintenance-icon-btn" href="{{ route('maintenance.show', $item) }}" aria-label="Abrir ticket">
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            @empty
                                <div class="text-muted fs-7">No hay visitas programadas.</div>
                            @endforelse
                        </div>

                        @if ($canManageProviders)
                            <div class="maintenance-side-panel">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                    <h3 class="maintenance-panel-title mb-0">Equipo</h3>
                                    <a class="maintenance-soft-btn py-2" href="{{ route('maintenance.technicians.index') }}">Administrar</a>
                                </div>
                                @forelse ($providers->take(5) as $provider)
                                    <div class="maintenance-mini-row">
                                        <div class="min-w-0">
                                            <div class="maintenance-cell-title">{{ $provider->name }}</div>
                                            <div class="maintenance-cell-subtitle">
                                                {{ $provider->specialty ?: (\App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type) }}
                                            </div>
                                        </div>
                                        <span class="maintenance-chip maintenance-chip-{{ $provider->is_active ? 'green' : 'neutral' }}">
                                            {{ $provider->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="text-muted fs-7">No hay técnicos/proveedores.</div>
                                @endforelse
                            </div>
                        @endif

                        <div class="maintenance-side-panel">
                            <h3 class="maintenance-panel-title">Propiedades con más incidencias</h3>
                            @forelse ($metrics['top_properties'] as $item)
                                <div class="maintenance-mini-row">
                                    <div class="min-w-0">
                                        <div class="maintenance-cell-title">{{ $item->property?->internal_name ?? 'Sin propiedad' }}</div>
                                        <div class="maintenance-cell-subtitle">{{ $item->property?->internal_reference ?: '-' }}</div>
                                    </div>
                                    <span class="maintenance-chip maintenance-chip-amber">{{ $item->total }}</span>
                                </div>
                            @empty
                                <div class="text-muted fs-7">Sin datos</div>
                            @endforelse
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </div>

    @if ($canCreateTicket)
        <div class="modal fade" id="createMaintenanceTicketModal" tabindex="-1" aria-hidden="true">

            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content" style="height: 90vh; max-height: 90vh; overflow: hidden;">

                    <form method="POST" action="{{ route('maintenance.store') }}" enctype="multipart/form-data"
                        id="createMaintenanceTicketForm" class="d-flex flex-column h-100">

                        @csrf

                        {{-- HEADER --}}
                        <div class="modal-header flex-shrink-0">
                            <h3 class="modal-title">
                                Nuevo ticket de mantenimiento
                            </h3>

                            <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">
                                ×
                            </button>
                        </div>

                        {{-- BODY CON SCROLL INTERNO --}}
                        <div class="modal-body overflow-auto">
                            <div class="row g-4">

                                @if ($isTenant)

                                    @if ($properties->count() === 1)
                                        <div class="col-12">
                                            <label class="form-label required">
                                                Propiedad
                                            </label>

                                            <input class="form-control" type="text"
                                                value="{{ $properties->first()->internal_name }}{{ $properties->first()->internal_reference ? ' - ' . $properties->first()->internal_reference : '' }}"
                                                disabled>

                                            <input type="hidden" name="property_id" value="{{ $properties->first()->id }}">
                                        </div>
                                    @else
                                        <div class="col-12">
                                            <label class="form-label required">
                                                Propiedad
                                            </label>

                                            <select class="form-select" name="property_id" required>

                                                <option value="">
                                                    Seleccionar...
                                                </option>

                                                @foreach ($properties as $property)
                                                    <option value="{{ $property->id }}" {{ old('property_id', $selectedProperty?->id) == $property->id ? 'selected' : '' }}>

                                                        {{ $property->internal_name }}
                                                        {{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                                    </option>
                                                @endforeach

                                            </select>
                                        </div>
                                    @endif

                                    <div class="col-12">
                                        <label class="form-label required">
                                            Título
                                        </label>

                                        <input class="form-control" type="text" name="title" value="{{ old('title') }}"
                                            maxlength="190" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Descripción
                                        </label>

                                        <textarea class="form-control" rows="4" name="description"
                                            maxlength="10000">{{ old('description') }}</textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label required">
                                            Evidencia
                                        </label>

                                        <input class="form-control" type="file" name="files[]" multiple required>
                                    </div>

                                @else

                                    <div class="col-md-6">
                                        <label class="form-label required">
                                            Propiedad
                                        </label>

                                        <select class="form-select" name="property_id" required>

                                            <option value="">
                                                Seleccionar...
                                            </option>

                                            @foreach ($properties as $property)
                                                <option value="{{ $property->id }}" {{ old('property_id', $selectedProperty?->id) == $property->id ? 'selected' : '' }}>

                                                    {{ $property->internal_name }}
                                                    {{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label required">
                                            Categoría
                                        </label>

                                        <select class="form-select" name="category" required>

                                            @foreach (\App\Models\MaintenanceTicket::CATEGORY_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ old('category', 'sin_categoria') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label required">
                                            Prioridad
                                        </label>

                                        <select class="form-select" name="priority" required>

                                            @foreach (\App\Models\MaintenanceTicket::PRIORITY_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ old('priority', 'sin_asignar') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Técnico asignado
                                        </label>

                                        <select class="form-select" name="provider_id" id="createTicketProvider">
                                            <option value="">Sin asignar</option>
                                            @foreach ($providers as $provider)
                                                <option value="{{ $provider->id }}" {{ old('provider_id') == $provider->id ? 'selected' : '' }}>
                                                    {{ $provider->name }} ·
                                                    {{ \App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">
                                            Nombre del ticket
                                        </label>

                                        <input class="form-control" type="text" name="title" value="{{ old('title') }}"
                                            maxlength="190" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">
                                            Ubicación exacta
                                        </label>

                                        <input class="form-control" type="text" name="exact_location"
                                            value="{{ old('exact_location') }}" maxlength="255" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label required">
                                            Fecha del reporte
                                        </label>

                                        <input class="form-control" type="datetime-local" name="reported_at"
                                            value="{{ old('reported_at', now()->format('Y-m-d\\TH:i')) }}" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">
                                            Visita programada
                                        </label>

                                        <input class="form-control" type="datetime-local" name="scheduled_visit_at"
                                            id="createTicketScheduledVisit" value="{{ old('scheduled_visit_at') }}">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Quién paga
                                        </label>

                                        <select class="form-select" name="payer">

                                            <option value="">
                                                Sin definir
                                            </option>

                                            @foreach (\App\Models\MaintenanceTicket::COST_PAYER_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ old('payer') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Regla
                                        </label>

                                        <select class="form-select" name="payment_rule">

                                            <option value="">
                                                Sin definir
                                            </option>

                                            @foreach (\App\Models\MaintenanceTicket::PAYMENT_RULE_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ old('payment_rule') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Estado inicial
                                        </label>

                                        <select class="form-select" name="status">

                                            @foreach (\App\Models\MaintenanceTicket::STATUS_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ old('status', 'pendiente') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label required">
                                            Descripción
                                        </label>

                                        <textarea class="form-control" rows="4" name="description" maxlength="10000"
                                            required>{{ old('description') }}</textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Notas adicionales
                                        </label>

                                        <textarea class="form-control" rows="3" name="additional_notes"
                                            maxlength="10000">{{ old('additional_notes') }}</textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Archivos (múltiples)
                                        </label>

                                        <input class="form-control" type="file" name="files[]" multiple>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Notas sobre regla de pago
                                        </label>

                                        <textarea class="form-control" rows="2" name="payment_rule_notes"
                                            maxlength="3000">{{ old('payment_rule_notes') }}</textarea>
                                    </div>

                                @endif

                            </div>
                        </div>

                        {{-- FOOTER FIJO --}}
                        <div class="modal-footer flex-shrink-0">
                            <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                                Cancelar
                            </button>

                            <button class="btn btn-primary" type="submit">
                                Crear ticket
                            </button>
                            <input type="hidden" name="force_conflict" value="0">
                        </div>

                    </form>
                </div>
            </div>
        </div>
    @endif
    @if ($canManageProviders)
        <div class="modal fade" id="createProviderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('maintenance.providers.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h3 class="modal-title">Nuevo técnico/proveedor</h3>
                            <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label required">Tipo</label>
                                    <select class="form-select" name="type" required>
                                        @foreach (\App\Models\MaintenanceProvider::TYPE_LABELS as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label required">Nombre</label>
                                    <input class="form-control" type="text" name="name" maxlength="190" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Correo</label>
                                    <input class="form-control" type="email" name="email" maxlength="190">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input class="form-control" type="text" name="phone" maxlength="40">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Especialidad</label>
                                    <input class="form-control" type="text" name="specialty" maxlength="190">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Costo promedio</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="average_cost">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Calificación</label>
                                    <input class="form-control" type="number" step="0.01" min="0" max="5" name="rating">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Disponibilidad</label>
                                    <input class="form-control" type="text" name="availability" maxlength="255">
                                </div>
                                <div class="col-12">
                                    <hr class="my-1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vincular usuario existente</label>
                                    <select class="form-select" name="user_id">
                                        <option value="">Sin vincular</option>
                                        @foreach ($users as $userRow)
                                            <option value="{{ $userRow->id }}">{{ $userRow->name }} · {{ $userRow->email }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" id="create_user_account_new"
                                            name="create_user_account">
                                        <label class="form-check-label" for="create_user_account_new">Crear cuenta nueva para
                                            técnico</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre cuenta</label>
                                    <input class="form-control" type="text" name="account_name" maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Correo cuenta</label>
                                    <input class="form-control" type="email" name="account_email" maxlength="190">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contraseña cuenta</label>
                                    <input class="form-control" type="text" name="account_password" maxlength="120">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1"
                                            id="send_credentials_email_new" name="send_credentials_email">
                                        <label class="form-check-label" for="send_credentials_email_new">Enviar acceso por
                                            correo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach ($providers as $provider)
            <div class="modal fade" id="editProviderModal-{{ $provider->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('maintenance.providers.update', $provider) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h3 class="modal-title">Editar técnico/proveedor</h3>
                                <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label required">Tipo</label>
                                        <select class="form-select" name="type" required>
                                            @foreach (\App\Models\MaintenanceProvider::TYPE_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ $provider->type === $key ? 'selected' : '' }}>{{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label required">Nombre</label>
                                        <input class="form-control" type="text" name="name" maxlength="190"
                                            value="{{ $provider->name }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Correo</label>
                                        <input class="form-control" type="email" name="email" maxlength="190"
                                            value="{{ $provider->email }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Teléfono</label>
                                        <input class="form-control" type="text" name="phone" maxlength="40"
                                            value="{{ $provider->phone }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Especialidad</label>
                                        <input class="form-control" type="text" name="specialty" maxlength="190"
                                            value="{{ $provider->specialty }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Costo promedio</label>
                                        <input class="form-control" type="number" step="0.01" min="0" name="average_cost"
                                            value="{{ $provider->average_cost }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Calificación</label>
                                        <input class="form-control" type="number" step="0.01" min="0" max="5" name="rating"
                                            value="{{ $provider->rating }}">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Disponibilidad</label>
                                        <input class="form-control" type="text" name="availability" maxlength="255"
                                            value="{{ $provider->availability }}">
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" name="is_active"
                                                id="provider_active_{{ $provider->id }}" {{ $provider->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label" for="provider_active_{{ $provider->id }}">Activo</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <hr class="my-1">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Vincular usuario existente</label>
                                        <select class="form-select" name="user_id">
                                            <option value="">Sin vincular</option>
                                            @foreach ($users as $userRow)
                                                <option value="{{ $userRow->id }}" {{ (int) $provider->user_id === (int) $userRow->id ? 'selected' : '' }}>
                                                    {{ $userRow->name }} · {{ $userRow->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="create_user_account_{{ $provider->id }}" name="create_user_account">
                                            <label class="form-check-label" for="create_user_account_{{ $provider->id }}">Crear
                                                cuenta nueva</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre cuenta</label>
                                        <input class="form-control" type="text" name="account_name" maxlength="255"
                                            value="{{ $provider->name }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Correo cuenta</label>
                                        <input class="form-control" type="email" name="account_email" maxlength="190">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contraseña cuenta</label>
                                        <input class="form-control" type="text" name="account_password" maxlength="120">
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="send_credentials_email_{{ $provider->id }}" name="send_credentials_email">
                                            <label class="form-check-label" for="send_credentials_email_{{ $provider->id }}">Enviar
                                                acceso por correo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    <style>
        #maintenance-team-calendar .fc-event {
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        #maintenance-team-calendar .fc-daygrid-event {
            white-space: normal !important;
            margin-top: 4px;
        }

        #maintenance-team-calendar .fc-ticket-event {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            width: 100%;
            padding: 6px 7px;
            border-radius: 8px;
            background: color-mix(in srgb, var(--ticket-color) 14%, white);
            border-left: 4px solid var(--ticket-color);
            color: #181C32;
            font-size: 12px;
            line-height: 1.25;
            overflow: hidden;
        }

        #maintenance-team-calendar .fc-ticket-dot {
            width: 8px;
            height: 8px;
            min-width: 8px;
            margin-top: 4px;
            border-radius: 50%;
            background: var(--ticket-color);
        }

        #maintenance-team-calendar .fc-ticket-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        #maintenance-team-calendar .fc-ticket-time {
            font-size: 10px;
            font-weight: 700;
            color: #5E6278;
        }

        #maintenance-team-calendar .fc-ticket-title {
            font-weight: 600;
            color: #181C32;
            word-break: break-word;
        }

        #maintenance-team-calendar .fc-more-link {
            font-size: 12px;
            font-weight: 600;
            color: #3E97FF;
        }
    </style>
@endsection

@push('scripts')
    <script src="{{ asset('/metronic/assets/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>
    @if ($errors->createMaintenanceTicket->any())
        <script>
            (() => {
                const modalEl = document.getElementById('createMaintenanceTicketModal');
                if (!modalEl) return;
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif
    <script>
        (() => {
            const calendarEl = document.getElementById('maintenance-team-calendar');
            if (calendarEl && window.FullCalendar?.Calendar) {
                const events = @json($calendarEvents);
                const priorityColorMap = {
                    'baja': '#54B81C',
                    'media': '#3699FF',
                    'alta': '#FFC700',
                    'urgente': '#F1416C'
                };
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek',
                    },
                    events: events.filter((event) => !!event.start),
                    eventClick: (info) => {
                        if (!info.event.url) return;
                        info.jsEvent.preventDefault();
                        window.location.href = info.event.url;
                    },
                    eventContent: (arg) => {
                        const priority = arg.event.extendedProps?.priority ?? 'media';
                        const color = priorityColorMap[priority] || '#3699FF';

                        const title = arg.event.title || 'Sin título';
                        const time = arg.timeText ? `<span class="fc-ticket-time">${arg.timeText}</span>` : '';

                        return {
                            html: `
                                    <div class="fc-ticket-event" style="--ticket-color: ${color};">
                                        <span class="fc-ticket-dot"></span>
                                        <div class="fc-ticket-text">
                                            ${time}
                                            <span class="fc-ticket-title">${title}</span>
                                        </div>
                                    </div>
                                `
                        };
                    },
                });
                calendar.render();
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const askConfirmation = async (message) => {
                if (window.Swal?.fire) {
                    const result = await window.Swal.fire({
                        icon: 'warning',
                        title: 'Conflicto de agenda',
                        html: String(message || '').replace(/\n/g, '<br>'),
                        showCancelButton: true,
                        confirmButtonText: 'Sí, continuar',
                        cancelButtonText: 'Cancelar',
                    });
                    return result.isConfirmed === true;
                }
                return window.confirm(message);
            };

            const rowIgnoreSelector = 'a, button, input, select, textarea, label, form, .dropdown-menu, [data-maintenance-row-action]';
            document.querySelectorAll('[data-maintenance-row-url]').forEach((row) => {
                const openTicket = () => {
                    const url = row.dataset.maintenanceRowUrl;
                    if (url) window.location.href = url;
                };

                row.addEventListener('click', (event) => {
                    if (event.target.closest(rowIgnoreSelector)) return;
                    openTicket();
                });

                row.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter') return;
                    if (event.target.closest(rowIgnoreSelector)) return;
                    event.preventDefault();
                    openTicket();
                });
            });

            document.querySelectorAll('.maintenance-ticket-row .dropdown').forEach((dropdown) => {
                const row = dropdown.closest('.maintenance-ticket-row');
                dropdown.addEventListener('shown.bs.dropdown', () => {
                    row?.classList.add('is-dropdown-open');
                });
                dropdown.addEventListener('hidden.bs.dropdown', () => {
                    row?.classList.remove('is-dropdown-open');
                });
            });

            const renderNotice = (type, message) => {
                if (window.SuWorkToast?.fire) {
                    window.SuWorkToast.fire(type, message);
                    return;
                }
                console[type === 'danger' || type === 'error' ? 'error' : 'log'](message);
            };

            const submitInlineMeta = async (inlineForm, forceConflict = false) => {
                const data = new FormData(inlineForm);
                if (forceConflict) {
                    data.set('force_conflict', '1');
                }

                const response = await fetch(inlineForm.action, {
                    method: (inlineForm.method || 'POST').toUpperCase(),
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: data,
                });
                const payload = await response.json().catch(() => ({}));

                if (response.status === 422 && payload.requires_confirmation) {
                    const approved = await askConfirmation(payload.message || 'El técnico ya tiene otra asignación este día.');
                    if (!approved) return false;
                    return submitInlineMeta(inlineForm, true);
                }

                if (!response.ok || payload.success === false) {
                    const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                    throw new Error(errors[0] || payload.message || 'No fue posible guardar el cambio.');
                }

                renderNotice('success', payload.message || 'Guardado correctamente.');
                window.location.reload();
                return true;
            };

            document.querySelectorAll('.js-maintenance-inline-meta').forEach((inlineForm) => {
                inlineForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const submitButton = inlineForm.querySelector('[type="submit"]');
                    if (submitButton?.disabled) return;
                    if (submitButton) submitButton.disabled = true;

                    try {
                        const saved = await submitInlineMeta(inlineForm);
                        if (!saved && submitButton) submitButton.disabled = false;
                    } catch (error) {
                        renderNotice('danger', error.message || 'No fue posible guardar el cambio.');
                        if (submitButton) submitButton.disabled = false;
                    }
                });
            });

            const form = document.getElementById('createMaintenanceTicketForm');
            if (!form) return;
            const providerInput = form.querySelector('[name="provider_id"]');
            const scheduledInput = document.getElementById('createTicketScheduledVisit');
            const forceInput = form.querySelector('[name="force_conflict"]');
            const conflictUrl = @json(route('maintenance.technician-conflicts'));
            form.addEventListener('submit', async (event) => {
                if (!providerInput?.value || !scheduledInput?.value || forceInput?.value === '1') {
                    return;
                }
                event.preventDefault();
                const response = await fetch(conflictUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        provider_id: providerInput.value,
                        scheduled_visit_at: scheduledInput.value,
                    }),
                }).catch(() => null);
                if (!response?.ok) {
                    form.submit();
                    return;
                }
                const payload = await response.json().catch(() => null);
                if (!payload?.has_conflicts) {
                    form.submit();
                    return;
                }
                const approved = await askConfirmation(payload.message || 'El técnico ya tiene otra asignación este día.');
                if (!approved) return;
                forceInput.value = '1';
                form.submit();
            });
        })();
    </script>
@endpush
