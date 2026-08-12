@extends('layouts.app')

@section('title', 'Cortes de mantenimiento | Naboo')

@section('content')
    <div class="maintenance-module py-8">
        <div class="maintenance-page">
            @if (session('success'))
                <div class="alert alert-success mb-0">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger mb-0">{{ $errors->first() }}</div>
            @endif

            <div class="maintenance-hero">
                <div>
                    <div class="maintenance-kicker">Liquidaciones internas</div>
                    <h1 class="maintenance-title">Cortes de mantenimiento</h1>
                    <div class="maintenance-subtitle">
                        Selecciona tickets con liquidación pendiente, revisa sus costos y confirma el pago a proveedores.
                    </div>
                </div>
                <div class="maintenance-actions">
                    <a class="maintenance-plain-btn" href="{{ route('maintenance.index') }}">
                        <i class="bi bi-arrow-left"></i> Tickets
                    </a>
                </div>
            </div>

            <div class="maintenance-kpi-strip">
                <div class="maintenance-kpi">
                    <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#b45309"></span>Pendientes</div>
                    <div class="maintenance-kpi-value">{{ number_format($pendingTickets->count()) }}</div>
                    <div class="maintenance-kpi-sub">Tickets elegibles</div>
                </div>
                <div class="maintenance-kpi">
                    <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#1d4ed8"></span>Mano de obra</div>
                    <div class="maintenance-kpi-value">${{ number_format((float) $pendingTotals['labor'], 2) }}</div>
                    <div class="maintenance-kpi-sub">Pendiente de corte</div>
                </div>
                <div class="maintenance-kpi">
                    <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#0f766e"></span>Materiales</div>
                    <div class="maintenance-kpi-value">${{ number_format((float) $pendingTotals['material'], 2) }}</div>
                    <div class="maintenance-kpi-sub">Pendiente de corte</div>
                </div>
                <div class="maintenance-kpi">
                    <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#111827"></span>Total</div>
                    <div class="maintenance-kpi-value">${{ number_format((float) $pendingTotals['final'], 2) }}</div>
                    <div class="maintenance-kpi-sub">Disponible para liquidar</div>
                </div>
            </div>

            <form method="POST" action="{{ route('maintenance.settlements.store') }}" data-maintenance-settlement-form>
                @csrf
                <div class="maintenance-panel mb-6">
                    <div class="maintenance-list-toolbar">
                        <div>
                            <div class="maintenance-list-title">Generar corte</div>
                            <div class="maintenance-list-count">Elige únicamente los tickets que vas a pagar en este corte.</div>
                        </div>
                        <button class="maintenance-primary-btn" type="submit" {{ $pendingTickets->isEmpty() ? 'disabled' : '' }}>
                            <i class="bi bi-check2-circle"></i> Confirmar y liquidar
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr class="text-muted">
                                    <th style="width:42px">
                                        <input class="form-check-input" type="checkbox" data-settlement-check-all aria-label="Seleccionar todos">
                                    </th>
                                    <th>Ticket</th>
                                    <th>Propiedad</th>
                                    <th>Técnico</th>
                                    <th class="text-end">Mano de obra</th>
                                    <th class="text-end">Materiales</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pendingTickets as $ticket)
                                    <tr>
                                        <td>
                                            <input class="form-check-input" type="checkbox" name="ticket_ids[]" value="{{ $ticket->id }}"
                                                data-settlement-ticket
                                                data-labor="{{ (float) ($ticket->labor_total ?? 0) }}"
                                                data-material="{{ (float) ($ticket->material_total ?? 0) }}"
                                                data-final="{{ (float) ($ticket->final_total ?? 0) }}"
                                                aria-label="Seleccionar ticket {{ $ticket->display_reference }}">
                                        </td>
                                        <td>
                                            <a class="fw-bold text-dark" href="{{ route('maintenance.show', $ticket) }}">#{{ $ticket->display_reference }}</a>
                                            <div class="text-muted fs-7">{{ $ticket->title }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $ticket->property?->internal_name ?: '-' }}</div>
                                            <div class="text-muted fs-7">{{ $ticket->property?->internal_reference ?: 'Sin referencia' }}</div>
                                        </td>
                                        <td>{{ $ticket->currentProvider?->name ?: 'Sin asignar' }}</td>
                                        <td class="text-end">${{ number_format((float) ($ticket->labor_total ?? 0), 2) }}</td>
                                        <td class="text-end">${{ number_format((float) ($ticket->material_total ?? 0), 2) }}</td>
                                        <td class="text-end fw-bold">${{ number_format((float) ($ticket->final_total ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-8">
                                            No hay tickets pendientes por liquidar.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-4 mt-1">
                        <div class="col-lg-7">
                            <label class="form-label">Notas del corte</label>
                            <textarea class="form-control" name="notes" rows="3" maxlength="3000" placeholder="Referencia de pago, banco, observaciones internas...">{{ old('notes') }}</textarea>
                        </div>
                        <div class="col-lg-5">
                            <div class="maintenance-kpi h-100">
                                <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#111827"></span>Resumen seleccionado</div>
                                <div class="d-flex justify-content-between mt-3"><span>Tickets</span><strong data-settlement-count>0</strong></div>
                                <div class="d-flex justify-content-between mt-2"><span>Mano de obra</span><strong data-settlement-labor>$0.00</strong></div>
                                <div class="d-flex justify-content-between mt-2"><span>Materiales</span><strong data-settlement-material>$0.00</strong></div>
                                <div class="d-flex justify-content-between mt-3 fs-5"><span>Total a liquidar</span><strong data-settlement-final>$0.00</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="maintenance-panel">
                <div class="maintenance-list-toolbar">
                    <div>
                        <div class="maintenance-list-title">Cortes generados</div>
                        <div class="maintenance-list-count">Historial de liquidaciones confirmadas.</div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Generado por</th>
                                <th class="text-end">Tickets</th>
                                <th class="text-end">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($settlements as $settlement)
                                <tr>
                                    <td class="fw-bold">{{ $settlement->reference }}</td>
                                    <td>{{ $settlement->settled_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td>{{ $settlement->creator?->name ?: '-' }}</td>
                                    <td class="text-end">{{ number_format((int) $settlement->total_tickets) }}</td>
                                    <td class="text-end fw-bold">${{ number_format((float) $settlement->total_amount, 2) }}</td>
                                    <td class="text-end">
                                        <a class="maintenance-soft-btn" href="{{ route('maintenance.settlements.show', $settlement) }}">Ver corte</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-8">Aún no hay cortes generados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="maintenance-pagination">{{ $settlements->links() }}</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-maintenance-settlement-form]');
            if (!form) return;

            const checks = Array.from(form.querySelectorAll('[data-settlement-ticket]'));
            const checkAll = form.querySelector('[data-settlement-check-all]');
            const money = (value) => `$${Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            const render = () => {
                const selected = checks.filter((check) => check.checked);
                const totals = selected.reduce((carry, check) => ({
                    labor: carry.labor + Number(check.dataset.labor || 0),
                    material: carry.material + Number(check.dataset.material || 0),
                    final: carry.final + Number(check.dataset.final || 0),
                }), { labor: 0, material: 0, final: 0 });

                form.querySelector('[data-settlement-count]').textContent = selected.length;
                form.querySelector('[data-settlement-labor]').textContent = money(totals.labor);
                form.querySelector('[data-settlement-material]').textContent = money(totals.material);
                form.querySelector('[data-settlement-final]').textContent = money(totals.final);
            };

            checkAll?.addEventListener('change', () => {
                checks.forEach((check) => check.checked = checkAll.checked);
                render();
            });
            checks.forEach((check) => check.addEventListener('change', render));
            render();
        });
    </script>
@endsection
