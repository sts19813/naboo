@extends('layouts.app')

@section('title', 'Pendientes | SuWork')

@php
    $isAdministrative = $isAdministrative ?? false;
    $taskRouteName = $isAdministrative ? 'admin.tasks.index' : 'advisor.tasks.index';
    $selectedUserParameter = $isAdministrative && $selectedTaskUser ? ['user_id' => $selectedTaskUser->id] : [];
    $filters = [
        'all' => ['label' => 'Todos', 'icon' => 'bi-list-check'],
        'urgent' => ['label' => 'Urgentes', 'icon' => 'bi-exclamation-octagon'],
        'today' => ['label' => 'Hoy', 'icon' => 'bi-calendar-day'],
        'charges' => ['label' => 'Cobranza', 'icon' => 'bi-wallet2'],
        'maintenance' => ['label' => 'Tickets', 'icon' => 'bi-tools'],
        'documents' => ['label' => 'Docs', 'icon' => 'bi-folder2-open'],
        'contracts' => ['label' => 'Contratos', 'icon' => 'bi-file-earmark-text'],
    ];

    $dateRanges = [
        'today' => ['label' => 'Hoy', 'icon' => 'bi-calendar-day'],
        'current_week' => ['label' => 'Esta semana', 'icon' => 'bi-calendar-week'],
        'current_month' => ['label' => 'Este mes', 'icon' => 'bi-calendar3'],
    ];

@endphp

@push('styles')
    <style>
        .advisor-tasks {
            max-width: 1180px;
            margin: 0 auto;
        }

        .advisor-tasks-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .advisor-tasks-title {
            font-size: 2rem;
            line-height: 1.15;
        }

        .advisor-filter-strip {
            display: flex;
            gap: 0.65rem;
            overflow-x: auto;
            padding: 0.25rem 0 0.75rem;
            margin-bottom: 0.5rem;
            scrollbar-width: thin;
        }

        .advisor-user-panel {
            border: 1px solid #e9edf4;
            border-radius: 10px;
            background: #fff;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .advisor-user-list {
            display: flex;
            gap: 0.65rem;
            overflow-x: auto;
            padding-top: 0.75rem;
            scrollbar-width: thin;
        }

        .advisor-user-option {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            min-width: 210px;
            border: 1px solid #dfe5ee;
            border-radius: 8px;
            color: #4b5675;
            padding: 0.7rem 0.8rem;
            text-decoration: none;
        }

        .advisor-user-option.is-active {
            border-color: #1b84ff;
            background: #eef6ff;
            color: #1b84ff;
        }

        .advisor-user-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 50%;
            background: #f1f3f7;
            font-weight: 800;
        }

        .advisor-range-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            border: 1px solid #e9edf4;
            border-radius: 8px;
            background: #fff;
            padding: 0.85rem;
            margin-bottom: 1rem;
        }

        .advisor-range-tabs {
            display: inline-flex;
            gap: 0.35rem;
            border-radius: 8px;
            background: #f5f8fa;
            padding: 0.25rem;
        }

        .advisor-range-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 6px;
            color: #4b5675;
            font-weight: 700;
            padding: 0.55rem 0.75rem;
            text-decoration: none;
            white-space: nowrap;
        }

        .advisor-range-tab.is-active {
            background: #fff;
            color: #1b84ff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }

        .advisor-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            flex: 0 0 auto;
            border: 1px solid #dfe5ee;
            border-radius: 999px;
            background: #fff;
            color: #4b5675;
            font-weight: 700;
            padding: 0.65rem 0.95rem;
            text-decoration: none;
        }

        .advisor-filter-chip.is-active {
            border-color: #1b84ff;
            background: #eef6ff;
            color: #1b84ff;
        }

        .advisor-task-list {
            background: #fff;
        }

        .advisor-task-table {
            overflow: hidden;
            border: 1px solid #e9edf4;
            border-radius: 10px;
            background: #fff;
        }

        .advisor-task-table-header,
        .advisor-task-item {
            display: grid;
            grid-template-columns: minmax(180px, .9fr) minmax(240px, 1.5fr) 110px 115px 90px;
            gap: 1rem;
            align-items: center;
        }

        .advisor-task-table-header {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            color: #99a1b7;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .advisor-task-item {
            border-top: 1px solid #eef1f6;
            padding: 0.85rem 1rem;
            color: inherit;
            transition: background-color .15s ease;
        }

        .advisor-task-item:hover {
            background: #f8fbff;
        }

        .advisor-task-property {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        .advisor-task-photo {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            border-radius: 8px;
            object-fit: cover;
            background: #f5f8fa;
        }

        .advisor-task-subject {
            min-width: 0;
        }

        .advisor-task-category {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.2rem;
            color: #99a1b7;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .advisor-task-category-dot {
            width: 7px;
            height: 7px;
            flex: 0 0 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .advisor-task-time,
        .advisor-task-date {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .advisor-task-action {
            text-align: right;
        }

        .advisor-task-date {
            color: #78829d;
            text-transform: capitalize;
        }

        .advisor-task-mobile-label {
            display: none;
        }

        .advisor-empty {
            border: 1px dashed #badbcc;
            border-radius: 8px;
            background: #f3fbf7;
            padding: 2rem;
            color: #0f5132;
        }

        @media (max-width: 991.98px) {
            .advisor-tasks {
                padding-bottom: 5rem;
            }

            .advisor-tasks-hero {
                display: block;
            }

            .advisor-tasks-title {
                font-size: 1.65rem;
            }

            .advisor-range-bar {
                align-items: stretch;
            }

            .advisor-range-tabs {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                width: 100%;
            }

            .advisor-range-tab {
                justify-content: center;
                padding: 0.65rem 0.45rem;
            }

            .advisor-task-table-header {
                display: none;
            }

            .advisor-task-table {
                overflow: visible;
                border: 0;
                background: transparent;
            }

            .advisor-task-list {
                display: grid;
                gap: 0.85rem;
                background: transparent;
            }

            .advisor-task-item {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 0.75rem 1rem;
                align-items: start;
                border: 1px solid #c8ced9;
                border-radius: 10px;
                background: #fff;
                padding: 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }

            .advisor-task-property,
            .advisor-task-subject {
                grid-column: 1 / -1;
            }

            .advisor-task-subject {
                padding-left: 60px;
            }

            .advisor-task-time,
            .advisor-task-date {
                display: grid;
                gap: 0.15rem;
                padding-top: 0.75rem;
                border-top: 1px solid #eef1f6;
            }

            .advisor-task-action {
                grid-column: 1 / -1;
            }

            .advisor-task-action .btn {
                width: 100%;
            }

            .advisor-task-date {
                text-align: right;
            }

            .advisor-task-mobile-label {
                display: block;
                color: #99a1b7;
                font-size: 0.65rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }
        }

    </style>
@endpush

@section('content')
    <div class="py-8 advisor-tasks">
        <div class="advisor-tasks-hero">
            <div>
                <div class="text-muted fs-7 fw-bold text-uppercase mb-2">Centro de trabajo</div>
                <h1 class="advisor-tasks-title fw-bold text-dark mb-2">
                    {{ $isAdministrative ? 'Pendientes administrativos' : 'Mis pendientes' }}
                </h1>
                <div class="text-muted fs-6">
                    @if ($isAdministrative)
                        Consulta las tareas de asesores y técnicos por usuario para {{ strtolower($periodLabel) }}.
                    @else
                        Tareas y urgencias de tus propiedades asignadas para {{ strtolower($periodLabel) }}.
                    @endif
                </div>
            </div>

            <div class="badge badge-light-primary text-primary fs-7 fw-bold mt-2">
                {{ $allTasksCount }} pendiente{{ $allTasksCount === 1 ? '' : 's' }}
            </div>
        </div>

        @if ($isAdministrative)
            <div class="advisor-user-panel">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <div class="text-muted fs-8 fw-bold text-uppercase">Pendientes por usuario</div>
                        <div class="fw-bold text-dark">Asesores y técnicos</div>
                    </div>
                    <span class="badge badge-light-secondary text-gray-700">
                        {{ $taskUsers->count() }} usuario{{ $taskUsers->count() === 1 ? '' : 's' }}
                    </span>
                </div>

                <div class="advisor-user-list" aria-label="Seleccionar asesor o técnico">
                    @forelse ($taskUsers as $taskUser)
                        @php
                            $isTechnicianUser = $taskUser->hasAnyRole(['tecnico', 'technician']);
                            $userInitials = collect(preg_split('/\s+/', trim($taskUser->name), -1, PREG_SPLIT_NO_EMPTY))
                                ->take(2)
                                ->map(fn ($word) => mb_substr($word, 0, 1))
                                ->join('');
                        @endphp
                        <a href="{{ route($taskRouteName, ['user_id' => $taskUser->id, 'range' => $activeRange, 'filter' => $activeFilter]) }}"
                            class="advisor-user-option {{ $selectedTaskUser?->is($taskUser) ? 'is-active' : '' }}">
                            <span class="advisor-user-avatar">{{ $userInitials }}</span>
                            <span class="min-w-0">
                                <span class="d-block fw-bold text-truncate">{{ $taskUser->name }}</span>
                                <span class="d-block fs-8 text-muted">
                                    {{ $isTechnicianUser ? 'Técnico' : 'Asesor' }}{{ $taskUser->is_active ? '' : ' · Inactivo' }}
                                </span>
                            </span>
                        </a>
                    @empty
                        <div class="text-muted py-2">No hay asesores o técnicos registrados.</div>
                    @endforelse
                </div>
            </div>
        @endif

        <div class="advisor-range-bar">
            <div>
                <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Rango de fecha</div>
                <div class="fw-bold text-dark">
                    {{ $periodStart->translatedFormat('d M Y') }}
                    @unless ($periodStart->isSameDay($periodEnd))
                        - {{ $periodEnd->translatedFormat('d M Y') }}
                    @endunless
                </div>
                @if ($periodIncludesOverdue)
                    <div class="text-muted fs-8 mt-1">Incluye todos los pendientes vencidos, sin importar la fecha.</div>
                @endif
            </div>

            <div class="advisor-range-tabs" aria-label="Rango de fecha de pendientes">
                @foreach ($dateRanges as $key => $range)
                    <a href="{{ route($taskRouteName, array_merge($selectedUserParameter, ['range' => $key, 'filter' => $activeFilter])) }}"
                        class="advisor-range-tab {{ $activeRange === $key ? 'is-active' : '' }}">
                        <i class="bi {{ $range['icon'] }}"></i>
                        <span>{{ $range['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="advisor-filter-strip" aria-label="Filtros de pendientes">
            @foreach ($filters as $key => $filter)
                <a href="{{ route($taskRouteName, array_merge($selectedUserParameter, ['range' => $activeRange, 'filter' => $key])) }}"
                    class="advisor-filter-chip {{ $activeFilter === $key ? 'is-active' : '' }}">
                    <i class="bi {{ $filter['icon'] }}"></i>
                    <span>{{ $filter['label'] }}</span>
                    <span class="badge badge-light-secondary text-gray-700">{{ $filterCounts[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <div class="advisor-task-table" role="table" aria-label="Lista de pendientes">
            <div class="advisor-task-table-header" role="row">
                <span role="columnheader">Nombre de la propiedad</span>
                <span role="columnheader">Tipo del asunto</span>
                <span role="columnheader">Tiempo</span>
                <span role="columnheader">Fecha</span>
                <span role="columnheader" class="text-end">Acciones</span>
            </div>

            <div class="advisor-task-list" role="rowgroup">
                @forelse ($tasks as $task)
                    @php
                        $propertyPhotoUrl = $task['property_photo_path']
                            ? \Illuminate\Support\Facades\Storage::url($task['property_photo_path'])
                            : asset('metronic/assets/media/svg/files/blank-image.svg');
                    @endphp
                    <div class="advisor-task-item" role="row">
                        <span class="advisor-task-property" role="cell">
                            <img src="{{ $propertyPhotoUrl }}" class="advisor-task-photo"
                                alt="{{ $task['property_name'] }}" loading="lazy">
                            <span class="fw-bold text-dark text-truncate" title="{{ $task['property_name'] }}">
                                {{ $task['property_name'] }}
                            </span>
                        </span>

                        <span class="advisor-task-subject" role="cell">
                            <span class="advisor-task-category text-{{ $task['tone'] }}">
                                <span class="advisor-task-category-dot"></span>
                                <span>{{ $task['category_label'] }}</span>
                            </span>
                            <span class="d-block text-gray-800 fw-semibold text-truncate" title="{{ $task['subject'] }}">
                                {{ $task['subject'] }}
                            </span>
                        </span>

                        <span class="advisor-task-time text-{{ $task['tone'] }}" role="cell">
                            <span class="advisor-task-mobile-label">Tiempo</span>
                            <span>{{ $task['time_label'] }}</span>
                        </span>

                        <span class="advisor-task-date" role="cell">
                            <span class="advisor-task-mobile-label">Fecha</span>
                            <span>{{ $task['date_label'] }}</span>
                        </span>

                        <span class="advisor-task-action" role="cell">
                            <a href="{{ $task['route'] }}" class="btn btn-sm btn-light-primary">
                                Abrir
                            </a>
                        </span>
                    </div>
                @empty
                    <div class="advisor-empty">
                        <div class="fw-bold mb-1">No hay pendientes en este filtro.</div>
                        <div>Cuando exista cobranza próxima, tickets, contratos o documentos por atender, aparecerán aquí.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
