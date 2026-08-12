@extends('layouts.app')

@section('title', $settlement->reference.' | Corte de mantenimiento')

@section('content')
    <div class="maintenance-module py-8">
        <div class="maintenance-page">
            @if (session('success'))
                <div class="alert alert-success mb-0">{{ session('success') }}</div>
            @endif

            <div class="maintenance-hero">
                <div>
                    <div class="maintenance-kicker">Corte liquidado</div>
                    <h1 class="maintenance-title">{{ $settlement->reference }}</h1>
                    <div class="maintenance-subtitle">
                        Liquidado el {{ $settlement->settled_at?->format('d/m/Y H:i') ?: '-' }}
                        por {{ $settlement->creator?->name ?: 'usuario no disponible' }}.
                    </div>
                </div>
                <div class="maintenance-actions">
                    <a class="maintenance-plain-btn" href="{{ route('maintenance.settlements.index') }}">
                        <i class="bi bi-arrow-left"></i> Cortes
                    </a>
                </div>
            </div>

            <div class="maintenance-kpi-strip">
                <div class="maintenance-kpi">
                    <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#334155"></span>Tickets</div>
                    <div class="maintenance-kpi-value">{{ number_format((int) $settlement->total_tickets) }}</div>
                    <div class="maintenance-kpi-sub">Liquidados</div>
                </div>
                <div class="maintenance-kpi">
                    <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#1d4ed8"></span>Mano de obra</div>
                    <div class="maintenance-kpi-value">${{ number_format((float) $settlement->total_labor_cost, 2) }}</div>
                    <div class="maintenance-kpi-sub">Total del corte</div>
                </div>
                <div class="maintenance-kpi">
                    <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#0f766e"></span>Materiales</div>
                    <div class="maintenance-kpi-value">${{ number_format((float) $settlement->total_material_cost, 2) }}</div>
                    <div class="maintenance-kpi-sub">Total del corte</div>
                </div>
                <div class="maintenance-kpi">
                    <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#111827"></span>Total pagado</div>
                    <div class="maintenance-kpi-value">${{ number_format((float) $settlement->total_amount, 2) }}</div>
                    <div class="maintenance-kpi-sub">{{ $settlement->currency }}</div>
                </div>
            </div>

            @if ($settlement->notes)
                <div class="maintenance-panel">
                    <div class="maintenance-list-title">Notas</div>
                    <div class="text-muted mt-2">{{ $settlement->notes }}</div>
                </div>
            @endif

            <div class="maintenance-panel">
                <div class="maintenance-list-toolbar">
                    <div>
                        <div class="maintenance-list-title">Tickets incluidos</div>
                        <div class="maintenance-list-count">Desglose por incidencia liquidada en este corte.</div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>Ticket</th>
                                <th>Propiedad</th>
                                <th>Técnico</th>
                                <th class="text-end">Mano de obra</th>
                                <th class="text-end">Materiales</th>
                                <th class="text-end">Anticipo</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($settlement->tickets as $ticket)
                                <tr>
                                    <td>
                                        <a class="fw-bold text-dark" href="{{ route('maintenance.show', $ticket) }}">#{{ $ticket->display_reference }}</a>
                                        <div class="text-muted fs-7">{{ $ticket->title }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $ticket->property?->internal_name ?: '-' }}</div>
                                        <div class="text-muted fs-7">{{ $ticket->property?->internal_reference ?: 'Sin referencia' }}</div>
                                    </td>
                                    <td>{{ $ticket->currentProvider?->name ?: 'Sin asignar' }}</td>
                                    <td class="text-end">${{ number_format((float) $ticket->pivot->labor_cost, 2) }}</td>
                                    <td class="text-end">${{ number_format((float) $ticket->pivot->material_cost, 2) }}</td>
                                    <td class="text-end">${{ number_format((float) $ticket->pivot->advance_cost, 2) }}</td>
                                    <td class="text-end fw-bold">${{ number_format((float) $ticket->pivot->final_cost, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="pt-0">
                                        <div class="bg-light rounded p-3">
                                            <div class="fw-bold mb-2">Costos y gastos del incidente</div>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead>
                                                        <tr class="text-muted">
                                                            <th>Fecha</th>
                                                            <th>Notas</th>
                                                            <th>Quién paga</th>
                                                            <th>Gasto</th>
                                                            <th class="text-end">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($ticket->costs as $cost)
                                                            <tr>
                                                                <td>{{ $cost->created_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                                                <td>{{ $cost->notes ?: '-' }}</td>
                                                                <td>{{ \App\Models\MaintenanceTicket::COST_PAYER_LABELS[$cost->payer] ?? 'Sin definir' }}</td>
                                                                <td>
                                                                    @if ($cost->expense)
                                                                        @include('expenses.partials.status-badge', ['expense' => $cost->expense])
                                                                    @else
                                                                        <span class="text-muted">Sin gasto ligado</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-end fw-bold">${{ number_format((float) $cost->final_cost, 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
