@extends('layouts.app')

@section('title', 'Ticket mantenimiento | SuWork')

@section('content')
    <div class="maintenance-module py-8">
        @php
            $tenantName = $ticket->property?->tenant?->full_name
                ?: ($ticket->property?->current_tenant_name
                    ?: ($ticket->reported_by_role === 'inquilino' ? ($ticket->reported_by_name ?: '-') : '-'));
            $tenantPhone = $ticket->property?->tenant?->phone_primary ?: '-';
            $propertyPhoto = $ticket->property?->facade_photo_path
                ? asset('storage/' . ltrim((string) $ticket->property->facade_photo_path, '/'))
                : null;
            $mapsLink = $ticket->property?->map_url;
            if (!$mapsLink && filled($ticket->property?->full_address)) {
                $mapsLink = 'https://www.google.com/maps/search/?api=1&query=' . urlencode((string) $ticket->property?->full_address);
            }
            $allFiles = $ticket->files
                ->map(function ($file) {
                    $file->preview_url = $file->url;
                    return $file;
                })
                ->values();
            $incidentFiles = $allFiles
                ->filter(fn ($file) => filled($file->preview_url) && (string) $file->kind !== 'trabajo_finalizado')
                ->values();
            $finishedWorkFiles = $allFiles
                ->filter(fn ($file) => filled($file->preview_url) && (string) $file->kind === 'trabajo_finalizado')
                ->values();
            $statusTone = match ($ticket->status) {
                'completado' => 'green',
                'cancelado' => 'red',
                'en_proceso' => 'purple',
                'programado', 'asignado' => 'blue',
                default => 'amber',
            };
            $priorityTone = match ($ticket->priority) {
                'baja' => 'green',
                'media' => 'blue',
                'alta' => 'amber',
                'urgente' => 'red',
                default => 'neutral',
            };
            $maintenanceCosts = $ticket->costs->sortByDesc('created_at')->values();
            $visibleMessages = $ticket->messages
                ->filter(function ($message) use ($role) {
                    if ($role === 'inquilino') {
                        return $message->channel === 'inquilino_admin';
                    }
                    if ($role === 'tecnico') {
                        return in_array($message->channel, ['inquilino_admin', 'admin_tecnico'], true);
                    }
                    return true;
                })
                ->sortBy('created_at')
                ->values();
            $messageFormChannels = match ($role) {
                'administrador' => [
                    'inquilino_admin' => $messageChannels['inquilino_admin'] ?? 'Inquilino - Administración',
                    'admin_tecnico' => $messageChannels['admin_tecnico'] ?? 'Administración - Técnico',
                    'interno' => $messageChannels['interno'] ?? 'Interno',
                ],
                'tecnico' => [
                    'admin_tecnico' => $messageChannels['admin_tecnico'] ?? 'Administración - Técnico',
                ],
                'inquilino' => [
                    'inquilino_admin' => $messageChannels['inquilino_admin'] ?? 'Inquilino - Administración',
                ],
                default => [],
            };
        @endphp

        <div class="ticket-page">
            @if (session('success'))
                <div class="alert alert-success mb-0">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger mb-0">{{ session('error') }}</div>
            @endif
            <div id="ticketAjaxNotice"></div>

            <div class="ticket-hero">
                <div class="ticket-hero-main">
                    @if ($propertyPhoto)
                        <img src="{{ $propertyPhoto }}" alt="Foto propiedad" class="ticket-hero-photo">
                    @else
                        <div class="ticket-photo-placeholder"><i class="bi bi-house-door fs-1"></i></div>
                    @endif
                    <div class="min-w-0">
                        <div class="ticket-kicker">Folio #{{ $ticket->display_reference }}</div>
                        <h1 class="ticket-title">{{ $ticket->title }}</h1>
                        <div class="ticket-subtitle">
                            {{ $ticket->property?->internal_name ?? '-' }}
                            @if ($ticket->property?->internal_reference)
                                · {{ $ticket->property->internal_reference }}
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <span class="maintenance-chip maintenance-chip-{{ $statusTone }}">
                                {{ \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                            </span>
                            <span class="maintenance-chip maintenance-chip-{{ $priorityTone }}">
                                {{ $priorityOptions[$ticket->priority] ?? $ticket->priority }}
                            </span>
                            <span class="maintenance-chip maintenance-chip-neutral">
                                {{ $categoryOptions[$ticket->category] ?? $ticket->category }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="maintenance-actions">
                    <a class="maintenance-plain-btn" href="{{ route('maintenance.index') }}">
                        <i class="bi bi-arrow-left"></i> Regresar
                    </a>
                    @if ($canQuickScheduleVisit)
                        <button class="maintenance-primary-btn" type="button" data-bs-toggle="collapse"
                            data-bs-target="#quickScheduleCollapse" aria-expanded="false"
                            aria-controls="quickScheduleCollapse">
                            <i class="bi bi-calendar2-plus"></i> Programar
                        </button>
                    @endif
                </div>
            </div>

            @if ($canQuickScheduleVisit)
                <div class="collapse" id="quickScheduleCollapse">
                    <div class="ticket-panel">
                        <form method="POST" action="{{ route('maintenance.schedule-visit', $ticket) }}" id="quickScheduleForm" class="row g-3 align-items-end">
                            @csrf
                            @method('PATCH')
                            <div class="col-md-5">
                                <label class="form-label">Fecha programada</label>
                                <input class="form-control" type="datetime-local" name="scheduled_visit_at" id="quickScheduleDate" value="{{ $ticket->scheduled_visit_at?->format('Y-m-d\\TH:i') }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Nota</label>
                                <input class="form-control" type="text" name="notes" maxlength="3000" placeholder="Opcional">
                            </div>
                            <div class="col-md-2 d-grid">
                                <button class="maintenance-primary-btn">Guardar</button>
                            </div>
                            <input type="hidden" name="force_conflict" value="0">
                        </form>
                    </div>
                </div>
            @endif

            <div class="ticket-grid">
                <main class="ticket-stack">
                    <section class="ticket-panel">
                        <div class="ticket-panel-header">
                            <h2 class="ticket-panel-title">Trabajo</h2>
                            @if ($canEditTicket)
                                <button class="maintenance-soft-btn py-2" data-bs-toggle="modal" data-bs-target="#editTicketModal">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                            @endif
                        </div>
                        <div class="ticket-info-grid mb-4">
                            <div class="ticket-info-item">
                                <div class="ticket-info-label">Ubicación exacta</div>
                                <div class="ticket-info-value">{{ $ticket->exact_location ?: '-' }}</div>
                            </div>
                            <div class="ticket-info-item">
                                <div class="ticket-info-label">Reporte</div>
                                <div class="ticket-info-value">{{ $ticket->reported_at?->format('d/m/Y H:i') ?: '-' }}</div>
                            </div>
                            <div class="ticket-info-item">
                                <div class="ticket-info-label">Visita</div>
                                <div class="ticket-info-value">{{ $ticket->scheduled_visit_at?->format('d/m/Y H:i') ?: 'Sin programar' }}</div>
                            </div>
                            <div class="ticket-info-item">
                                <div class="ticket-info-label">Quién paga</div>
                                <div class="ticket-info-value">{{ \App\Models\MaintenanceTicket::PAYER_LABELS[$ticket->payer] ?? 'Sin definir' }}</div>
                            </div>
                            <div class="ticket-info-item">
                                <div class="ticket-info-label">Regla</div>
                                <div class="ticket-info-value">{{ $paymentRuleOptions[$ticket->payment_rule] ?? 'Sin definir' }}</div>
                            </div>
                            <div class="ticket-info-item">
                                <div class="ticket-info-label">Reportó</div>
                                <div class="ticket-info-value">{{ $ticket->reporter?->name ?: ($ticket->reported_by_name ?: '-') }}</div>
                            </div>
                        </div>
                        <div class="ticket-description">
                            <strong>Descripción:</strong> {{ $ticket->description ?: '-' }}
                        </div>
                        @if ($ticket->additional_notes)
                            <div class="ticket-description mt-3">
                                <div><strong>Notas adicionales:</strong> {{ $ticket->additional_notes }}</div>
                            </div>
                        @endif
                    </section>

                    <section class="ticket-panel">
                        <div class="ticket-panel-header">
                            <h2 class="ticket-panel-title">Evidencias y archivos del incidente</h2>
                            <span class="maintenance-chip maintenance-chip-neutral">{{ $incidentFiles->count() }} archivos</span>
                        </div>
                        <div class="ticket-file-grid mb-4">
                            @forelse ($incidentFiles as $file)
                                <div class="ticket-file-tile">
                                    <button type="button" class="ticket-file-thumb js-ticket-file-preview"
                                        data-file-url="{{ $file->preview_url }}"
                                        data-file-name="{{ $file->original_name }}"
                                        data-file-mime="{{ $file->mime_type }}"
                                        data-file-download="{{ $file->preview_url }}"
                                        data-file-delete-url="{{ route('maintenance.files.destroy', [$ticket, $file]) }}">
                                        @if (str_starts_with((string) $file->mime_type, 'video/'))
                                            <video src="{{ $file->preview_url }}" muted preload="metadata"></video>
                                        @elseif ((string) $file->mime_type === 'application/pdf')
                                            <span class="ticket-file-pdf-thumb">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                                <span>{{ $file->original_name }}</span>
                                            </span>
                                        @elseif (str_starts_with((string) $file->mime_type, 'image/'))
                                            <img src="{{ $file->preview_url }}" alt="{{ $file->original_name }}">
                                        @else
                                            <span class="ticket-file-pdf-thumb">
                                                <i class="bi bi-file-earmark"></i>
                                                <span>{{ $file->original_name }}</span>
                                            </span>
                                        @endif
                                    </button>
                                    <div class="ticket-file-caption">
                                        {{ \App\Models\MaintenanceTicketFile::KIND_LABELS[$file->kind] ?? $file->kind }} · {{ $file->created_at?->format('d/m/Y H:i') ?: '-' }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted">No hay archivos cargados.</div>
                            @endforelse
                        </div>

                        <form method="POST" action="{{ route('maintenance.files', $ticket) }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                            @csrf
                            <input type="hidden" name="kind" value="reporte">
                            <div class="col-md-9">
                                <label class="form-label">Archivos</label>
                                <input class="form-control" type="file" name="files[]" multiple required>
                            </div>
                            <div class="col-md-3 d-grid">
                                <button class="maintenance-primary-btn">
                                    <i class="bi bi-upload"></i> Subir
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="ticket-panel d-none" id="ticket-chat-section" hidden>
                        <div class="ticket-panel-header">
                            <h2 class="ticket-panel-title">Chat</h2>
                            <span class="maintenance-chip maintenance-chip-blue">{{ $visibleMessages->count() }} mensajes</span>
                        </div>
                        <div class="ticket-chat-log mb-4">
                            @forelse ($visibleMessages as $message)
                                <div class="ticket-message {{ (int) $message->sender_user_id === (int) auth()->id() ? 'is-own' : '' }}">
                                    <div class="ticket-message-meta">
                                        <span>{{ $message->sender?->name ?: 'Sistema' }}</span>
                                        <span>{{ $message->created_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="maintenance-chip maintenance-chip-neutral">
                                            {{ $messageChannels[$message->channel] ?? $message->channel }}
                                        </span>
                                    </div>
                                    <div>{{ $message->message }}</div>
                                </div>
                            @empty
                                <div class="text-muted">Sin mensajes todavía.</div>
                            @endforelse
                        </div>

                        @if ($messageFormChannels !== [])
                            <form method="POST" action="{{ route('maintenance.messages', $ticket) }}" class="row g-3">
                                @csrf
                                @if (count($messageFormChannels) === 1)
                                    <input type="hidden" name="channel" value="{{ array_key_first($messageFormChannels) }}">
                                @else
                                    <div class="col-md-6">
                                        <label class="form-label">Canal</label>
                                        <select class="form-select" name="channel" required>
                                            @foreach ($messageFormChannels as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                @if (in_array($role, ['administrador', 'tecnico'], true))
                                    <div class="col-md-6">
                                        <label class="form-label">Destinatario</label>
                                        <select class="form-select" name="recipient_user_id">
                                            <option value="">Sin destinatario específico</option>
                                            @foreach ($users as $userRow)
                                                <option value="{{ $userRow->id }}">{{ $userRow->name }} · {{ $userRow->email }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-12">
                                    <label class="form-label">Mensaje</label>
                                    <textarea class="form-control" name="message" rows="3" maxlength="5000" required></textarea>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="maintenance-primary-btn">
                                        <i class="bi bi-send"></i> Enviar mensaje
                                    </button>
                                </div>
                            </form>
                        @endif
                    </section>

                    @if ($canManageCosts)
                        <section class="ticket-panel">
                            <div class="ticket-panel-header">
                                <div>
                                    <h2 class="ticket-panel-title">Costos y gastos del incidente</h2>
                                    <div class="text-muted mt-1">Cada registro se agrega automáticamente a los gastos de la propiedad.</div>
                                </div>
                                <button type="button" class="maintenance-primary-btn" data-bs-toggle="modal" data-bs-target="#createMaintenanceCostModal">
                                    <i class="bi bi-plus-lg"></i> Agregar costo
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted">
                                            <th>Fecha</th>
                                            <th>Mano de obra</th>
                                            <th>Materiales</th>
                                            <th>Gasto Total</th>
                                            <th>Quién paga</th>
                                            <th>Regla</th>
                                            <th>Estado</th>
                                            <th>Facturas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($maintenanceCosts as $costRow)
                                            <tr>
                                                <td>{{ $costRow->created_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                                <td>${{ number_format((float) $costRow->labor_cost, 2) }}</td>
                                                <td>${{ number_format((float) $costRow->material_cost, 2) }}</td>
                                                <td class="fw-bold">${{ number_format((float) $costRow->final_cost, 2) }}</td>
                                                <td>
                                                    {{ $costPayerOptions[$costRow->payer] ?? 'Sin definir' }}
                                                    @if ($costRow->payer === 'inquilino')
                                                        <span class="maintenance-chip maintenance-chip-neutral ms-1">No contabiliza</span>
                                                    @endif
                                                </td>
                                                <td>{{ $paymentRuleOptions[$costRow->payment_rule] ?? 'Sin definir' }}</td>
                                                <td>
                                                    @if ($costRow->expense)
                                                        @include('expenses.partials.status-badge', ['expense' => $costRow->expense])
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($costRow->expense?->files?->isNotEmpty())
                                                        @include('expenses.partials.attachments', [
                                                            'files' => $costRow->expense->files,
                                                            'previewInModal' => true,
                                                        ])
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if ($costRow->notes)
                                                <tr><td colspan="8" class="text-muted pt-0"><strong>Notas:</strong> {{ $costRow->notes }}</td></tr>
                                            @endif
                                        @empty
                                            <tr><td colspan="8" class="text-center text-muted py-5">No hay costos registrados.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endif

                    <section class="ticket-panel">
                        <div class="ticket-panel-header">
                            <h2 class="ticket-panel-title">Evidencias y archivos del trabajo finalizado</h2>
                            <span class="maintenance-chip maintenance-chip-neutral">{{ $finishedWorkFiles->count() }} archivos</span>
                        </div>
                        <div class="ticket-file-grid mb-4">
                            @forelse ($finishedWorkFiles as $file)
                                <div class="ticket-file-tile">
                                    <button type="button" class="ticket-file-thumb js-ticket-file-preview"
                                        data-file-url="{{ $file->preview_url }}"
                                        data-file-name="{{ $file->original_name }}"
                                        data-file-mime="{{ $file->mime_type }}"
                                        data-file-download="{{ $file->preview_url }}"
                                        data-file-delete-url="{{ route('maintenance.files.destroy', [$ticket, $file]) }}">
                                        @if (str_starts_with((string) $file->mime_type, 'video/'))
                                            <video src="{{ $file->preview_url }}" muted preload="metadata"></video>
                                        @elseif ((string) $file->mime_type === 'application/pdf')
                                            <span class="ticket-file-pdf-thumb">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                                <span>{{ $file->original_name }}</span>
                                            </span>
                                        @elseif (str_starts_with((string) $file->mime_type, 'image/'))
                                            <img src="{{ $file->preview_url }}" alt="{{ $file->original_name }}">
                                        @else
                                            <span class="ticket-file-pdf-thumb">
                                                <i class="bi bi-file-earmark"></i>
                                                <span>{{ $file->original_name }}</span>
                                            </span>
                                        @endif
                                    </button>
                                    <div class="ticket-file-caption">
                                        {{ \App\Models\MaintenanceTicketFile::KIND_LABELS[$file->kind] ?? $file->kind }} · {{ $file->created_at?->format('d/m/Y H:i') ?: '-' }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted">No hay archivos cargados.</div>
                            @endforelse
                        </div>

                        <form method="POST" action="{{ route('maintenance.files', $ticket) }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                            @csrf
                            <input type="hidden" name="kind" value="trabajo_finalizado">
                            <div class="col-md-9">
                                <label class="form-label">Archivos</label>
                                <input class="form-control" type="file" name="files[]" multiple required>
                            </div>
                            <div class="col-md-3 d-grid">
                                <button class="maintenance-primary-btn">
                                    <i class="bi bi-upload"></i> Subir
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="ticket-panel" id="ticket-history-section">
                        <div class="ticket-panel-header">
                            <h2 class="ticket-panel-title">Historial</h2>
                        </div>
                        <ul class="nav nav-tabs nav-line-tabs mb-4">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ticket-history-changes" type="button">Cambios</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ticket-history-notifications" type="button">Notificaciones</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="ticket-history-changes">
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr class="text-muted">
                                                <th>Fecha</th>
                                                <th>De</th>
                                                <th>A</th>
                                                <th>Usuario</th>
                                                <th>Nota</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($ticket->statusHistory as $row)
                                                <tr>
                                                    <td>{{ $row->changed_at?->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $row->from_status ? (\App\Models\MaintenanceTicket::STATUS_LABELS[$row->from_status] ?? $row->from_status) : '-' }}</td>
                                                    <td>{{ \App\Models\MaintenanceTicket::STATUS_LABELS[$row->to_status] ?? $row->to_status }}</td>
                                                    <td>{{ $row->changedBy?->name ?? '-' }}</td>
                                                    <td>{{ $row->notes ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-8">Sin historial.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="ticket-history-notifications">
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr class="text-muted">
                                                <th>Fecha</th>
                                                <th>Evento</th>
                                                <th>Canal</th>
                                                <th>Destino</th>
                                                <th>Envío</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($ticket->notifications as $notification)
                                                <tr>
                                                    <td>{{ $notification->created_at?->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $notification->event }}</td>
                                                    <td>{{ $notification->channel }}</td>
                                                    <td>{{ $notification->recipient ?: '-' }}</td>
                                                    <td>{{ $notification->was_sent ? 'Enviado' : 'Falló' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-8">Sin notificaciones.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <aside class="ticket-stack">
                    <section class="ticket-panel">
                        <div class="ticket-panel-header">
                            <h2 class="ticket-panel-title">Estado</h2>
                            <span class="maintenance-chip maintenance-chip-{{ $statusTone }}">
                                {{ \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                            </span>
                        </div>
                        @if ($canChangeStatus)
                            <form method="POST" action="{{ route('maintenance.status', $ticket) }}" class="row g-3 mb-4 js-ticket-status-form">
                                @csrf
                                @method('PATCH')
                                <div class="col-12">
                                    <label class="form-label">Cambiar estado</label>
                                    <select class="form-select" name="status">
                                        @foreach ($statusOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Nota</label>
                                    <textarea class="form-control" name="notes" rows="3" maxlength="3000" placeholder="Opcional"></textarea>
                                </div>
                                <div class="col-12 d-grid">
                                    <button class="maintenance-primary-btn js-ticket-status-submit" type="submit">
                                        <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                                        <span class="js-ticket-status-submit-text">Actualizar estado</span>
                                    </button>
                                </div>
                            </form>
                        @endif
                        <div class="ticket-timeline">
                            @foreach ([
                                ['label' => 'Creado', 'value' => $ticket->created_at?->format('d/m/Y H:i') ?: '-'],
                                ['label' => 'Asignado', 'value' => $ticket->assigned_at?->format('d/m/Y H:i') ?: '-'],
                                ['label' => 'Iniciado', 'value' => $ticket->started_at?->format('d/m/Y H:i') ?: '-'],
                                ['label' => 'Terminado', 'value' => $ticket->completed_at?->format('d/m/Y H:i') ?: '-'],
                                ['label' => 'Cancelado', 'value' => $ticket->canceled_at?->format('d/m/Y H:i') ?: '-'],
                            ] as $row)
                                <div class="ticket-timeline-item">
                                    <span class="ticket-timeline-dot"><i class="bi bi-clock"></i></span>
                                    <span>
                                        <span class="maintenance-cell-title">{{ $row['label'] }}</span>
                                        <span class="maintenance-cell-subtitle">{{ $row['value'] }}</span>
                                    </span>
                                </div>
                            @endforeach
                            @if ($ticket->cancel_reason)
                                <div class="ticket-description"><strong>Motivo cancelación:</strong> {{ $ticket->cancel_reason }}</div>
                            @endif
                        </div>
                    </section>

                    <section class="ticket-panel">
                        <div class="ticket-panel-header">
                            <h2 class="ticket-panel-title">Asignación</h2>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label">Categoría</label>
                                @if (in_array($role, ['administrador', 'tecnico'], true))
                                    <select class="form-select js-ticket-meta" data-field="category">
                                        @foreach ($categoryOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->category === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="ticket-info-value">{{ $categoryOptions[$ticket->category] ?? $ticket->category }}</div>
                                @endif
                            </div>
                            <div>
                                <label class="form-label">Prioridad</label>
                                @if (in_array($role, ['administrador', 'tecnico'], true))
                                    <select class="form-select js-ticket-meta" data-field="priority">
                                        @foreach ($priorityOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->priority === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="ticket-info-value">{{ $priorityOptions[$ticket->priority] ?? $ticket->priority }}</div>
                                @endif
                            </div>
                            <div>
                                <label class="form-label">Técnico asignado</label>
                                @if (in_array($role, ['administrador', 'tecnico'], true))
                                    <select class="form-select js-ticket-meta" data-field="provider_id" data-ticket-uuid="{{ $ticket->uuid }}" data-scheduled-visit-at="{{ $ticket->scheduled_visit_at?->format('Y-m-d\\TH:i:s') }}">
                                        <option value="">Sin asignar</option>
                                        @foreach ($providers as $provider)
                                            <option value="{{ $provider->id }}" {{ $ticket->current_provider_id === $provider->id ? 'selected' : '' }}>
                                                {{ $provider->name }} · {{ \App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="ticket-info-value">{{ $ticket->currentProvider?->name ?? 'Sin asignar' }}</div>
                                    <div class="maintenance-cell-subtitle">{{ $ticket->currentProvider?->phone ?: ($ticket->currentProvider?->email ?: '-') }}</div>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section class="ticket-panel">
                        <div class="ticket-panel-header">
                            <h2 class="ticket-panel-title">Propiedad e inquilino</h2>
                        </div>
                        <div class="ticket-info-grid" style="grid-template-columns: 1fr;">
                            <div class="ticket-info-item">
                                <div class="ticket-info-label">Propiedad</div>
                                <div class="ticket-info-value">{{ $ticket->property?->internal_name ?? '-' }}</div>
                                <div class="maintenance-cell-subtitle">{{ $ticket->property?->full_address ?: '-' }}</div>
                            </div>
                            <div class="ticket-info-item">
                                <div class="ticket-info-label">Inquilino</div>
                                <div class="ticket-info-value">{{ $tenantName ?: '-' }}</div>
                                <div class="maintenance-cell-subtitle">{{ $tenantPhone }}</div>
                            </div>
                        </div>
                        <div class="d-grid gap-2 mt-3">
                            @if ($mapsLink)
                                <a class="maintenance-plain-btn" href="{{ $mapsLink }}" target="_blank">
                                    <i class="bi bi-geo-alt"></i> Abrir ubicación
                                </a>
                            @endif
                            @if ($tenantPhone && $tenantPhone !== '-')
                                <a class="maintenance-soft-btn" href="tel:{{ preg_replace('/\D+/', '', $tenantPhone) }}">
                                    <i class="bi bi-telephone"></i> Llamar inquilino
                                </a>
                            @endif
                        </div>
                    </section>
                </aside>
            </div>

            @if ($canChangeStatus && !in_array($ticket->status, ['completado', 'cancelado'], true))
                <div class="ticket-mobile-actionbar">
                    <form method="POST" action="{{ route('maintenance.status', $ticket) }}" class="flex-grow-1">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="completado">
                        <button class="maintenance-primary-btn w-100">
                            <i class="bi bi-check-circle"></i> Marcar terminado
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    @if ($canManageCosts)
        <div class="modal fade" id="createMaintenanceCostModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('maintenance.costs', $ticket) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <div>
                                <h3 class="modal-title">Agregar costo de mantenimiento</h3>
                                <div class="text-muted fs-7 mt-1">Se creará un nuevo registro sin reemplazar los anteriores.</div>
                            </div>
                            <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label required">Mano de obra</label>
                                    <input class="form-control js-maintenance-cost-component" type="number" step="0.01" min="0" name="labor_cost" value="{{ old('labor_cost', 0) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">Materiales</label>
                                    <input class="form-control js-maintenance-cost-component" type="number" step="0.01" min="0" name="material_cost" value="{{ old('material_cost', 0) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">Gasto Total</label>
                                    <input class="form-control js-maintenance-cost-total" type="number" step="0.01" min="0" name="final_cost" value="{{ number_format((float) old('labor_cost', 0) + (float) old('material_cost', 0), 2, '.', '') }}" readonly aria-readonly="true">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Quién paga</label>
                                    <select class="form-select" name="payer" required>
                                        @foreach ($costPayerOptions as $key => $label)
                                            <option value="{{ $key }}" {{ old('payer', 'administracion') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Los costos del inquilino aparecen en Gastos, pero no se suman en resúmenes ni dashboards.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Regla de pago</label>
                                    <select class="form-select" name="payment_rule">
                                        <option value="">Sin definir</option>
                                        @foreach ($paymentRuleOptions as $key => $label)
                                            <option value="{{ $key }}" {{ old('payment_rule') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="is_paid" value="1" id="maintenanceCostIsPaid" {{ old('is_paid') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="maintenanceCostIsPaid">Marcar como ya pagado</label>
                                    </div>
                                    <div class="form-text">El gasto se registrará automáticamente con estado Pagado.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Facturas</label>
                                    <input class="form-control" type="file" name="invoice_files[]" multiple>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notas de costo</label>
                                    <textarea class="form-control" name="notes" rows="3" maxlength="5000">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="maintenance-primary-btn">Guardar costo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canEditTicket)
        <div class="modal fade" id="editTicketModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('maintenance.update', $ticket) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h3 class="modal-title">Editar ticket</h3>
                            <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label class="form-label required">Propiedad</label>
                                    <select class="form-select" name="property_id" required>
                                        @foreach ($properties as $property)
                                            <option value="{{ $property->id }}" {{ $ticket->property_id === $property->id ? 'selected' : '' }}>
                                                {{ $property->internal_name }}{{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Categoría</label>
                                    <select class="form-select" name="category" required>
                                        @foreach ($categoryOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->category === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Prioridad</label>
                                    <select class="form-select" name="priority" required>
                                        @foreach ($priorityOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->priority === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label required">Título</label>
                                    <input class="form-control" type="text" name="title" value="{{ $ticket->title }}" maxlength="190" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Folio</label>
                                    <input class="form-control" type="text" value="{{ $ticket->display_reference }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Ubicación exacta</label>
                                    <input class="form-control" type="text" name="exact_location" value="{{ $ticket->exact_location }}" maxlength="255" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label required">Fecha reporte</label>
                                    <input class="form-control" type="datetime-local" name="reported_at" value="{{ $ticket->reported_at?->format('Y-m-d\\TH:i') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Visita programada</label>
                                    <input class="form-control" type="datetime-local" name="scheduled_visit_at" value="{{ $ticket->scheduled_visit_at?->format('Y-m-d\\TH:i') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Quién paga</label>
                                    <select class="form-select" name="payer">
                                        <option value="">Sin definir</option>
                                        @foreach ($payerOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->payer === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Regla de pago</label>
                                    <select class="form-select" name="payment_rule">
                                        <option value="">Sin definir</option>
                                        @foreach ($paymentRuleOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->payment_rule === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label required">Descripción</label>
                                    <textarea class="form-control" rows="4" name="description" maxlength="10000" required>{{ $ticket->description }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notas adicionales</label>
                                    <textarea class="form-control" rows="3" name="additional_notes" maxlength="10000">{{ $ticket->additional_notes }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Agregar archivos</label>
                                    <input class="form-control" type="file" name="files[]" multiple>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button class="btn btn-primary" type="submit">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="ticketFilePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable ticket-file-modal-dialog">
            <div class="modal-content ticket-file-modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="ticketFilePreviewTitle">Archivo</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                </div>
                <div class="modal-body ticket-file-modal-body">
                    <img src="" alt="" class="ticket-file-modal-media d-none" id="ticketFilePreviewImage">
                    <video src="" class="ticket-file-modal-media d-none" id="ticketFilePreviewVideo" controls></video>
                    <iframe src="" class="ticket-file-modal-frame d-none" id="ticketFilePreviewPdf" title="Vista previa PDF"></iframe>
                    <div class="ticket-file-modal-fallback d-none" id="ticketFilePreviewFallback">
                        <i class="bi bi-file-earmark"></i>
                        <div>Este archivo no tiene vista previa dentro del navegador.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="#" class="m-0 js-delete-ticket-file" id="ticketFilePreviewDeleteForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-light-danger" id="ticketFilePreviewDelete">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </form>
                    <a href="#" class="btn btn-light-primary" id="ticketFilePreviewDownload" download>
                        <i class="bi bi-download"></i> Descargar
                    </a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (() => {
            const costForm = document.querySelector('#createMaintenanceCostModal form');
            const costComponents = costForm?.querySelectorAll('.js-maintenance-cost-component') ?? [];
            const costTotal = costForm?.querySelector('.js-maintenance-cost-total');
            const updateCostTotal = () => {
                if (!costTotal) return;

                const total = Array.from(costComponents).reduce((sum, input) => {
                    const value = Number.parseFloat(input.value);
                    return sum + (Number.isFinite(value) ? value : 0);
                }, 0);

                costTotal.value = total.toFixed(2);
            };
            costComponents.forEach((input) => input.addEventListener('input', updateCostTotal));
            updateCostTotal();

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const conflictUrl = @json(route('maintenance.technician-conflicts'));
            const ticketUuid = @json($ticket->uuid);
            const providerSelect = document.querySelector('.js-ticket-meta[data-field="provider_id"]');
            const providerScheduledValue = () => providerSelect?.dataset.scheduledVisitAt || @json($ticket->scheduled_visit_at?->format('Y-m-d\\TH:i:s'));
            const fileModalEl = document.getElementById('ticketFilePreviewModal');
            const fileModal = fileModalEl && window.bootstrap?.Modal ? new window.bootstrap.Modal(fileModalEl) : null;
            const fileModalTitle = document.getElementById('ticketFilePreviewTitle');
            const fileModalImage = document.getElementById('ticketFilePreviewImage');
            const fileModalVideo = document.getElementById('ticketFilePreviewVideo');
            const fileModalPdf = document.getElementById('ticketFilePreviewPdf');
            const fileModalFallback = document.getElementById('ticketFilePreviewFallback');
            const fileModalDownload = document.getElementById('ticketFilePreviewDownload');
            const fileModalDeleteForm = document.getElementById('ticketFilePreviewDeleteForm');
            const hideFilePreviewElements = () => {
                [fileModalImage, fileModalVideo, fileModalPdf, fileModalFallback].forEach((element) => {
                    element?.classList.add('d-none');
                });
                if (fileModalVideo) {
                    fileModalVideo.pause();
                    fileModalVideo.removeAttribute('src');
                    fileModalVideo.load();
                }
                if (fileModalImage) fileModalImage.removeAttribute('src');
                if (fileModalPdf) fileModalPdf.removeAttribute('src');
            };
            const openFilePreview = (button) => {
                const url = button.dataset.fileUrl || '';
                const name = button.dataset.fileName || 'Archivo';
                const mime = button.dataset.fileMime || '';
                if (!url || !fileModal) return;

                hideFilePreviewElements();
                if (fileModalTitle) fileModalTitle.textContent = name;
                if (fileModalDownload) {
                    fileModalDownload.href = button.dataset.fileDownload || url;
                    fileModalDownload.setAttribute('download', name);
                }
                if (fileModalDeleteForm) {
                    const deleteUrl = button.dataset.fileDeleteUrl || '';
                    fileModalDeleteForm.classList.toggle('d-none', deleteUrl === '');
                    if (deleteUrl !== '') fileModalDeleteForm.action = deleteUrl;
                }

                if (mime.startsWith('image/') && fileModalImage) {
                    fileModalImage.src = url;
                    fileModalImage.alt = name;
                    fileModalImage.classList.remove('d-none');
                } else if (mime.startsWith('video/') && fileModalVideo) {
                    fileModalVideo.src = url;
                    fileModalVideo.classList.remove('d-none');
                    fileModalVideo.load();
                } else if (mime === 'application/pdf' && fileModalPdf) {
                    fileModalPdf.src = url;
                    fileModalPdf.classList.remove('d-none');
                } else {
                    fileModalFallback?.classList.remove('d-none');
                }

                fileModal.show();
            };

            document.querySelectorAll('.js-ticket-file-preview').forEach((button) => {
                button.addEventListener('click', () => openFilePreview(button));
            });
            fileModalEl?.addEventListener('hidden.bs.modal', hideFilePreviewElements);
            document.querySelectorAll('.js-delete-ticket-file').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    let confirmed = false;
                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            icon: 'warning',
                            title: 'Eliminar archivo',
                            text: 'Esta acción eliminará el archivo del ticket.',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#d92d20',
                        });
                        confirmed = result.isConfirmed === true;
                    } else {
                        confirmed = window.confirm('¿Eliminar este archivo del ticket?');
                    }
                    if (confirmed) form.submit();
                });
            });
            document.querySelectorAll('.js-ticket-status-form').forEach((form) => {
                form.addEventListener('submit', () => {
                    const button = form.querySelector('.js-ticket-status-submit');
                    const spinner = button?.querySelector('.spinner-border');
                    const text = button?.querySelector('.js-ticket-status-submit-text');
                    if (!button) return;

                    const loadingMessages = [
                        'Guardando cambios...',
                        'Actualizando historial...',
                        'Notificando al técnico...',
                        'Procesando solicitud...',
                    ];
                    let messageIndex = 0;
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                    spinner?.classList.remove('d-none');
                    if (text) {
                        text.textContent = loadingMessages[messageIndex];
                        window.setInterval(() => {
                            messageIndex = (messageIndex + 1) % loadingMessages.length;
                            text.textContent = loadingMessages[messageIndex];
                        }, 2800);
                    }
                });
            });
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
            const checkConflicts = async (providerId, scheduledVisitAt) => {
                if (!providerId || !scheduledVisitAt) return null;
                const response = await fetch(conflictUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        provider_id: providerId,
                        scheduled_visit_at: scheduledVisitAt,
                        exclude_ticket_uuid: ticketUuid,
                    }),
                }).catch(() => null);
                if (!response?.ok) return null;
                const payload = await response.json().catch(() => null);
                return payload?.has_conflicts ? payload : null;
            };

            const selects = document.querySelectorAll('.js-ticket-meta');
            if (!selects.length) {
                const quickScheduleForm = document.getElementById('quickScheduleForm');
                if (!quickScheduleForm) return;
            }

            const metaUrl = @json(route('maintenance.meta', $ticket));

            const renderNotice = (type, message) => {
                window.SuWorkToast?.fire(type, message);
            };

            selects.forEach((select) => {
                select.dataset.prevValue = select.value;
                select.addEventListener('focus', () => {
                    select.dataset.prevValue = select.value;
                });

                select.addEventListener('change', async () => {
                    const field = select.dataset.field;
                    const nextValue = select.value;
                    const prevValue = select.dataset.prevValue ?? '';
                    if (!field || nextValue === prevValue) {
                        return;
                    }

                    select.disabled = true;
                    try {
                        const payloadData = {
                            [field]: field === 'provider_id' && nextValue === '' ? null : nextValue
                        };
                        if (field === 'provider_id') {
                            const scheduledVisitAt = providerScheduledValue();
                            const conflicts = await checkConflicts(nextValue, scheduledVisitAt);
                            if (conflicts) {
                                const approved = await askConfirmation(conflicts.message || 'El técnico ya tiene otra asignación este día.');
                                if (!approved) {
                                    select.value = prevValue;
                                    return;
                                }
                                payloadData.force_conflict = 1;
                            }
                        }
                        const response = await fetch(metaUrl, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(payloadData),
                        });

                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok || payload.success === false) {
                            throw new Error(payload.message || 'No fue posible guardar el cambio.');
                        }

                        select.dataset.prevValue = nextValue;
                        if (field === 'provider_id') {
                            select.dataset.scheduledVisitAt = payload.data?.scheduled_visit_at || select.dataset.scheduledVisitAt || '';
                        }
                        renderNotice('success', payload.message || 'Guardado correctamente.');
                    } catch (error) {
                        select.value = prevValue;
                        renderNotice('danger', error.message || 'Error al guardar.');
                    } finally {
                        select.disabled = false;
                    }
                });
            });

            const quickScheduleForm = document.getElementById('quickScheduleForm');
            const quickScheduleDate = document.getElementById('quickScheduleDate');
            const quickForce = quickScheduleForm?.querySelector('[name="force_conflict"]');
            if (!quickScheduleForm || !providerSelect || !quickScheduleDate) return;

            quickScheduleForm.addEventListener('submit', async (event) => {
                if (!providerSelect.value || !quickScheduleDate.value || quickForce?.value === '1') {
                    return;
                }
                event.preventDefault();
                const conflicts = await checkConflicts(providerSelect.value, quickScheduleDate.value);
                if (!conflicts) {
                    quickScheduleForm.submit();
                    return;
                }
                const approved = await askConfirmation(conflicts.message || 'El técnico ya tiene otra asignación este día.');
                if (!approved) return;
                if (quickForce) quickForce.value = '1';
                quickScheduleForm.submit();
            });
        })();
    </script>
@endpush
