@extends('layouts.app')

@section('title', 'Corte de mantenimiento | Naboo')

@section('content')
    <div class="maintenance-module maintenance-cuts py-8">
        @php
            $showHistory = !$errors->any() && (session('success') || request('tab') === 'historial');
        @endphp
        <div class="cut-heading">
            <div>
                <div class="cut-eyebrow">Mantenimiento</div>
                <h1 class="cut-title">Corte de mantenimiento</h1>
                <p class="cut-subtitle">Selecciona tickets completados, revisa sus costos acumulados y registra el pago en un solo paso.</p>
            </div>
            <a href="{{ route('maintenance.index', ['tab' => 'completados']) }}" class="maintenance-plain-btn">
                <i class="bi bi-check2-circle"></i> Ver tickets completados
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <strong>No se pudo registrar el pago.</strong>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <div class="cut-metrics">
            <article class="cut-metric">
                <span class="cut-metric-icon is-blue"><i class="bi bi-ticket-perforated"></i></span>
                <span><small>Por pagar</small><strong>{{ $tickets->count() }} tickets</strong></span>
            </article>
            <article class="cut-metric">
                <span class="cut-metric-icon is-purple"><i class="bi bi-hammer"></i></span>
                <span><small>Mano de obra pendiente</small><strong>${{ number_format($pendingTotals['labor'], 2) }}</strong></span>
            </article>
            <article class="cut-metric">
                <span class="cut-metric-icon is-amber"><i class="bi bi-box-seam"></i></span>
                <span><small>Materiales pendientes</small><strong>${{ number_format($pendingTotals['materials'], 2) }}</strong></span>
            </article>
            <article class="cut-metric">
                <span class="cut-metric-icon is-green"><i class="bi bi-cash-coin"></i></span>
                <span><small>Histórico pagado</small><strong>${{ number_format($paidGrandTotal, 2) }}</strong></span>
            </article>
        </div>

        <div class="cut-tabs" role="tablist" aria-label="Secciones del corte de mantenimiento">
            <button class="cut-tab {{ $showHistory ? '' : 'active' }}" id="pending-tab" data-bs-toggle="tab"
                data-bs-target="#pending-pane" type="button" role="tab" aria-controls="pending-pane"
                aria-selected="{{ $showHistory ? 'false' : 'true' }}">
                <i class="bi bi-list-check"></i>
                Pendientes de pago
                <span>{{ $tickets->count() }}</span>
            </button>
            <button class="cut-tab {{ $showHistory ? 'active' : '' }}" id="history-tab" data-bs-toggle="tab"
                data-bs-target="#history-pane" type="button" role="tab" aria-controls="history-pane"
                aria-selected="{{ $showHistory ? 'true' : 'false' }}">
                <i class="bi bi-clock-history"></i>
                Historial de cortes
                <span>{{ $cuts->total() }}</span>
            </button>
        </div>

        <div class="tab-content">
        <div class="tab-pane fade {{ $showHistory ? '' : 'show active' }}" id="pending-pane" role="tabpanel" aria-labelledby="pending-tab" tabindex="0">
        <section class="cut-panel">
            <div class="cut-panel-heading">
                <div>
                    <h2>Tickets listos para pago</h2>
                    <p>Los importes ya incluyen la suma de todos los registros de costos de cada ticket.</p>
                </div>
                @if ($tickets->isNotEmpty())
                    <button type="button" class="cut-select-all-button" id="selectAllVisible">
                        <i class="bi bi-check2-square"></i> Seleccionar todos
                    </button>
                @endif
            </div>

            @if ($tickets->isEmpty())
                <div class="cut-empty">
                    <span><i class="bi bi-check-circle"></i></span>
                    <h3>Todo está al día</h3>
                    <p>No hay tickets completados pendientes de pago.</p>
                </div>
            @else
                <form method="POST" action="{{ route('maintenance-cuts.store') }}" id="maintenanceCutForm">
                    @csrf
                    <div class="cut-workspace">
                    <div class="cut-table-column">
                    <div class="table-responsive cut-table-wrap">
                        <table class="table cut-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="cut-check-column"><span class="visually-hidden">Seleccionar</span></th>
                                    <th>Ticket</th>
                                    <th>Propiedad</th>
                                    <th>Creado</th>
                                    <th>Completado</th>
                                    <th class="text-end">Mano de obra</th>
                                    <th class="text-end">Materiales</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tickets as $ticket)
                                    @php
                                        $labor = (float) ($ticket->labor_total ?? 0);
                                        $materials = (float) ($ticket->material_total ?? 0);
                                        $grand = (float) ($ticket->grand_total ?? 0);
                                    @endphp
                                    <tr class="cut-ticket-row">
                                        <td class="cut-check-column">
                                            <input class="form-check-input cut-ticket-checkbox" type="checkbox"
                                                name="ticket_ids[]" value="{{ $ticket->id }}"
                                                data-labor="{{ $labor }}" data-materials="{{ $materials }}" data-grand="{{ $grand }}"
                                                aria-label="Seleccionar ticket {{ $ticket->display_reference }}">
                                        </td>
                                        <td>
                                            <a class="cut-ticket-link" href="{{ route('maintenance.show', $ticket) }}">
                                                <strong>#{{ $ticket->display_reference }}</strong>
                                                <span>{{ $ticket->title }}</span>
                                            </a>
                                        </td>
                                        <td>
                                            <strong class="d-block">{{ $ticket->property?->internal_name ?? '-' }}</strong>
                                            <small class="text-muted">{{ $ticket->property?->internal_reference ?: 'Sin referencia' }}</small>
                                        </td>
                                        <td class="text-nowrap">{{ $ticket->created_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                        <td class="text-nowrap">{{ $ticket->completed_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                        <td class="text-end text-nowrap">${{ number_format($labor, 2) }}</td>
                                        <td class="text-end text-nowrap">${{ number_format($materials, 2) }}</td>
                                        <td class="text-end text-nowrap fw-bold">${{ number_format($grand, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>

                    <aside class="cut-summary-card" id="cutPaymentBar" aria-live="polite">
                        <div class="cut-summary-heading">
                            <span class="cut-summary-icon"><i class="bi bi-receipt-cutoff"></i></span>
                            <div>
                                <small>Resumen del corte</small>
                                <h3>Pago seleccionado</h3>
                            </div>
                        </div>
                        <div class="cut-summary-count">
                            <span><strong id="selectedCount">0</strong> tickets seleccionados</span>
                            <i class="bi bi-check2-square"></i>
                        </div>
                        <div class="cut-summary-lines">
                            <div><span>Mano de obra</span><strong id="selectedLabor">$0.00</strong></div>
                            <div><span>Materiales</span><strong id="selectedMaterials">$0.00</strong></div>
                            <div class="cut-summary-total"><span>Total a pagar</span><strong id="selectedGrand">$0.00</strong></div>
                        </div>
                        <p class="cut-summary-note"><i class="bi bi-lock"></i> Al pagar, los costos seleccionados quedarán cerrados.</p>
                        <button class="maintenance-primary-btn cut-pay-button" type="submit" id="paySelectedButton" disabled>
                            <i class="bi bi-lock-fill"></i> Pagar seleccionados
                        </button>
                    </aside>
                    </div>
                </form>
            @endif
        </section>
        </div>

        <div class="tab-pane fade {{ $showHistory ? 'show active' : '' }}" id="history-pane" role="tabpanel" aria-labelledby="history-tab" tabindex="0">
        <section class="cut-panel">
            <div class="cut-panel-heading">
                <div>
                    <h2>Historial de cortes pagados</h2>
                    <p>Los importes registrados aquí son inmutables y conservan la fecha exacta del pago.</p>
                </div>
            </div>

            @forelse ($cuts as $cut)
                <details class="cut-history-item" @if ($loop->first && session('success')) open @endif>
                    <summary>
                        <span class="cut-history-reference">
                            <span class="cut-paid-icon"><i class="bi bi-check-lg"></i></span>
                            <span><strong>{{ $cut->display_reference }}</strong><small>{{ $cut->ticket_count }} tickets</small></span>
                        </span>
                        <span><small>Pagado</small><strong>{{ $cut->paid_at?->format('d/m/Y H:i') }}</strong></span>
                        <span><small>Registró</small><strong>{{ $cut->paidBy?->name ?? 'Usuario eliminado' }}</strong></span>
                        <span><small>Mano de obra</small><strong>${{ number_format((float) $cut->labor_total, 2) }}</strong></span>
                        <span><small>Materiales</small><strong>${{ number_format((float) $cut->material_total, 2) }}</strong></span>
                        <span class="cut-history-total"><small>Total pagado</small><strong>${{ number_format((float) $cut->grand_total, 2) }}</strong></span>
                        <i class="bi bi-chevron-down cut-history-chevron"></i>
                    </summary>
                    <div class="table-responsive cut-history-detail">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Ticket</th><th>Propiedad</th><th>Creado</th><th>Completado</th><th class="text-end">Mano de obra</th><th class="text-end">Materiales</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                @foreach ($cut->items as $item)
                                    <tr>
                                        <td>
                                            @if ($item->ticket)
                                                <a href="{{ route('maintenance.show', $item->ticket) }}" class="cut-ticket-link">
                                                    <strong>#{{ $item->ticket->display_reference }}</strong><span>{{ $item->ticket->title }}</span>
                                                </a>
                                            @else
                                                <span class="text-muted">Ticket no disponible</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->ticket?->property?->internal_name ?? '-' }}</td>
                                        <td class="text-nowrap">{{ $item->ticket?->created_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                        <td class="text-nowrap">{{ $item->ticket?->completed_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                        <td class="text-end text-nowrap">${{ number_format((float) $item->labor_total, 2) }}</td>
                                        <td class="text-end text-nowrap">${{ number_format((float) $item->material_total, 2) }}</td>
                                        <td class="text-end text-nowrap fw-bold">${{ number_format((float) $item->grand_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @empty
                <div class="cut-empty is-compact">
                    <span><i class="bi bi-receipt"></i></span>
                    <h3>Aún no hay cortes pagados</h3>
                    <p>El primer pago aparecerá aquí con su detalle completo.</p>
                </div>
            @endforelse

            @if ($cuts->hasPages())
                <div class="p-3">{{ $cuts->appends(['tab' => 'historial'])->links() }}</div>
            @endif
        </section>
        </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .maintenance-cuts{display:grid;gap:1.25rem}.cut-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem}.cut-eyebrow{text-transform:uppercase;letter-spacing:.12em;color:#ff3366;font-size:.76rem;font-weight:800}.cut-title{margin:.2rem 0;font-size:clamp(1.7rem,3vw,2.35rem);font-weight:800;color:#101d3f}.cut-subtitle{margin:0;color:#77819a}.cut-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.cut-metric{display:flex;align-items:center;gap:.8rem;background:#fff;border:1px solid #e8ebf2;border-radius:18px;padding:1rem;box-shadow:0 8px 24px rgba(20,36,72,.05)}.cut-metric>span:last-child{min-width:0}.cut-metric small,.cut-history-item small{display:block;color:#8490aa;font-size:.76rem}.cut-metric strong{display:block;color:#15213f;font-size:1.05rem;white-space:nowrap}.cut-metric-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:13px;font-size:1.1rem}.cut-metric-icon.is-blue{color:#2878e5;background:#eaf3ff}.cut-metric-icon.is-purple{color:#7b4ee7;background:#f1edff}.cut-metric-icon.is-amber{color:#d98b00;background:#fff5db}.cut-metric-icon.is-green{color:#0aa862;background:#e5f9ef}.cut-panel{background:#fff;border:1px solid #e8ebf2;border-radius:20px;box-shadow:0 10px 32px rgba(20,36,72,.06);overflow:hidden}.cut-panel-heading{padding:1.25rem 1.4rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid #edf0f5}.cut-panel-heading h2{font-size:1.12rem;font-weight:800;color:#15213f;margin:0}.cut-panel-heading p{color:#8490aa;margin:.25rem 0 0}.cut-select-all-button{border:0;background:#fff0f4;color:#e92158;border-radius:12px;padding:.65rem .9rem;font-weight:700;white-space:nowrap}.cut-table th{color:#77819a;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;border-bottom-color:#edf0f5;padding:.9rem .75rem}.cut-table td{padding:1rem .75rem;border-bottom-color:#f0f2f6;color:#303b57}.cut-check-column{width:46px;text-align:center}.cut-ticket-row{cursor:pointer;transition:.15s ease}.cut-ticket-row:hover,.cut-ticket-row.is-selected{background:#fff7f9}.cut-ticket-link{display:flex;flex-direction:column;color:#15213f;text-decoration:none;min-width:170px}.cut-ticket-link strong{color:#ef285c}.cut-ticket-link span{font-size:.86rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px}.cut-payment-bar{position:sticky;bottom:1rem;z-index:3;margin:1rem;display:flex;align-items:center;gap:1rem;background:#17213b;color:#fff;border-radius:17px;padding:.85rem 1rem;box-shadow:0 15px 32px rgba(16,29,63,.2)}.cut-payment-count{min-width:105px;border-right:1px solid rgba(255,255,255,.16)}.cut-payment-count span{font-size:1.5rem;font-weight:800;display:block;line-height:1}.cut-payment-count small,.cut-payment-amounts small{color:#aeb9d1;display:block;font-size:.72rem}.cut-payment-amounts{display:flex;align-items:center;gap:1.5rem;flex:1}.cut-payment-amounts strong{display:block;font-size:.94rem}.cut-payment-amounts .is-total strong{font-size:1.2rem;color:#74e4ae}.cut-pay-button:disabled{opacity:.45;cursor:not-allowed}.cut-empty{text-align:center;padding:3rem 1rem}.cut-empty>span{display:grid;place-items:center;margin:0 auto .8rem;width:52px;height:52px;border-radius:16px;background:#e9f9f0;color:#0aa862;font-size:1.35rem}.cut-empty h3{font-size:1.05rem;margin:0;color:#15213f}.cut-empty p{color:#8490aa;margin:.3rem 0 0}.cut-empty.is-compact{padding:2rem}.cut-history-item{border-bottom:1px solid #edf0f5}.cut-history-item summary{list-style:none;cursor:pointer;display:grid;grid-template-columns:1.15fr repeat(4,1fr) 1.1fr auto;align-items:center;gap:1rem;padding:1rem 1.35rem}.cut-history-item summary::-webkit-details-marker{display:none}.cut-history-item summary>span>strong{display:block;color:#24304d;font-size:.88rem}.cut-history-reference{display:flex;align-items:center;gap:.65rem}.cut-paid-icon{display:grid;width:34px;height:34px;place-items:center;border-radius:11px;background:#e5f9ef;color:#0aa862}.cut-history-reference small{margin-top:.1rem}.cut-history-total strong{color:#0a9a5a!important;font-size:1rem!important}.cut-history-chevron{color:#8490aa;transition:transform .2s}.cut-history-item[open] .cut-history-chevron{transform:rotate(180deg)}.cut-history-detail{background:#f8f9fc;border-top:1px solid #edf0f5;padding:.35rem 1.1rem}.cut-history-detail table{background:#fff}.cut-history-detail th{font-size:.75rem;color:#8490aa}.form-check-input:checked{background-color:#ff3366;border-color:#ff3366}
    @media(max-width:1100px){.cut-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.cut-history-item summary{grid-template-columns:1.3fr repeat(2,1fr) 1.1fr auto}.cut-history-item summary>span:nth-of-type(3),.cut-history-item summary>span:nth-of-type(4){display:none}}
    @media(max-width:767px){.maintenance-cuts{padding-top:1rem!important;padding-bottom:6rem!important}.cut-heading{align-items:flex-start;flex-direction:column}.cut-heading .maintenance-plain-btn{width:100%;justify-content:center}.cut-metrics{grid-template-columns:1fr 1fr;gap:.6rem}.cut-metric{padding:.75rem;border-radius:14px}.cut-metric-icon{display:none}.cut-metric strong{font-size:.92rem}.cut-panel{border-radius:16px}.cut-panel-heading{align-items:flex-start;padding:1rem;flex-direction:column}.cut-select-all-button{width:100%}.cut-table-wrap{max-height:58vh}.cut-table th,.cut-table td{padding:.75rem .6rem}.cut-payment-bar{bottom:82px;margin:.65rem;display:grid;grid-template-columns:auto 1fr;padding:.75rem}.cut-payment-count{min-width:80px}.cut-payment-amounts{justify-content:flex-end;gap:.7rem}.cut-payment-amounts>span:not(.is-total){display:none}.cut-pay-button{grid-column:1/-1;width:100%;justify-content:center}.cut-history-item summary{grid-template-columns:1fr 1fr auto;gap:.55rem;padding:.9rem 1rem}.cut-history-item summary>span:nth-of-type(3),.cut-history-item summary>span:nth-of-type(4),.cut-history-item summary>span:nth-of-type(5){display:none}.cut-history-total{text-align:right}.cut-history-detail{padding:.2rem}.cut-subtitle{font-size:.9rem}}
    .cut-tabs{display:flex;align-items:center;gap:.45rem;padding:.35rem;background:#eef1f6;border-radius:15px;width:max-content;max-width:100%}.cut-tab{display:flex;align-items:center;gap:.5rem;border:0;background:transparent;color:#6e7892;border-radius:11px;padding:.7rem 1rem;font-weight:700;transition:.18s ease}.cut-tab span{display:grid;place-items:center;min-width:23px;height:23px;padding:0 .35rem;border-radius:999px;background:#dfe4ed;color:#68738d;font-size:.72rem}.cut-tab:hover{color:#17213b}.cut-tab.active{background:#fff;color:#ef285c;box-shadow:0 4px 14px rgba(25,40,75,.09)}.cut-tab.active span{background:#fff0f4;color:#ef285c}.cut-workspace{display:grid;grid-template-columns:minmax(0,1fr) 320px;align-items:start;background:#f7f8fb}.cut-table-column{min-width:0;background:#fff;border-right:1px solid #edf0f5}.cut-table{min-width:980px}.cut-table th:last-child,.cut-table td:last-child{padding-right:1.5rem}.cut-summary-card{position:sticky;top:1rem;margin:1.2rem;background:#17213b;color:#fff;border-radius:18px;padding:1.2rem;box-shadow:0 16px 35px rgba(16,29,63,.17)}.cut-summary-heading{display:flex;align-items:center;gap:.75rem;padding-bottom:1rem;border-bottom:1px solid rgba(255,255,255,.12)}.cut-summary-heading small{color:#9eaac3;display:block;font-size:.72rem}.cut-summary-heading h3{font-size:1rem;margin:.12rem 0 0;color:#fff;font-weight:800}.cut-summary-icon{width:40px;height:40px;display:grid;place-items:center;border-radius:12px;background:rgba(255,51,102,.16);color:#ff5b83}.cut-summary-count{display:flex;align-items:center;justify-content:space-between;margin:1rem 0;padding:.8rem .9rem;border-radius:12px;background:rgba(255,255,255,.07);color:#c4cce0;font-size:.82rem}.cut-summary-count strong{color:#fff;font-size:1.2rem;margin-right:.25rem}.cut-summary-count i{color:#ff5b83;font-size:1.05rem}.cut-summary-lines{display:grid;gap:.75rem}.cut-summary-lines>div{display:flex;align-items:center;justify-content:space-between;gap:1rem;color:#aeb9d1;font-size:.84rem}.cut-summary-lines strong{color:#fff;font-size:.94rem}.cut-summary-lines .cut-summary-total{margin-top:.15rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,.14);color:#fff}.cut-summary-total span{font-weight:700}.cut-summary-total strong{color:#74e4ae;font-size:1.45rem}.cut-summary-note{display:flex;gap:.45rem;margin:1rem 0;color:#96a3bd;font-size:.73rem;line-height:1.4}.cut-summary-card .cut-pay-button{width:100%;justify-content:center;padding:.78rem 1rem}.tab-pane>.cut-panel{margin-top:0}
    @media(max-width:1200px){.cut-workspace{grid-template-columns:1fr}.cut-table-column{border-right:0;border-bottom:1px solid #edf0f5}.cut-summary-card{position:static;margin:1rem;display:grid;grid-template-columns:1fr 1.5fr;column-gap:1rem}.cut-summary-heading{grid-column:1/-1}.cut-summary-count{margin-bottom:0}.cut-summary-lines{grid-row:2/4;grid-column:2}.cut-summary-note{margin:.8rem 0 0}.cut-summary-card .cut-pay-button{align-self:end}}
    @media(max-width:767px){.cut-tabs{width:100%;display:grid;grid-template-columns:1fr 1fr}.cut-tab{justify-content:center;padding:.7rem .5rem;font-size:.82rem}.cut-tab i{display:none}.cut-tab span{min-width:20px;height:20px}.cut-workspace{display:block}.cut-table-wrap{max-height:none}.cut-summary-card{display:block;margin:.75rem;border-radius:15px;padding:1rem}.cut-summary-count{margin:1rem 0}.cut-summary-lines{display:grid}.cut-summary-note{margin:1rem 0}.cut-table th:last-child,.cut-table td:last-child{padding-right:1rem}}
    .cut-workspace{gap:1.25rem;padding:1.25rem}.cut-table-column{border:1px solid #e7ebf2;border-radius:16px;overflow:hidden}.cut-table th:last-child,.cut-table td:last-child{padding-right:2rem}.cut-summary-card{margin:0;background:#fff;color:#17213b;border:1px solid #e2e7f0;box-shadow:0 12px 28px rgba(25,40,75,.09)}.cut-summary-heading{border-bottom-color:#e8ecf3}.cut-summary-heading small{color:#8792aa}.cut-summary-heading h3{color:#17213b}.cut-summary-icon{background:#fff0f4;color:#ef285c}.cut-summary-count{background:#f6f7fa;color:#69758e;border:1px solid #ebedf3}.cut-summary-count strong{color:#17213b}.cut-summary-lines>div{color:#69758e}.cut-summary-lines strong{color:#17213b}.cut-summary-lines .cut-summary-total{border-top-color:#e5e9f0;color:#17213b}.cut-summary-note{color:#7d889f}.cut-summary-note i{color:#ef285c}
    @media(max-width:1200px){.cut-summary-card{margin:0}}
    @media(max-width:767px){.cut-workspace{padding:.75rem;display:grid;gap:.75rem}.cut-summary-card{margin:0}.cut-table th:last-child,.cut-table td:last-child{padding-right:1.35rem}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('maintenanceCutForm');
    if (!form) return;

    const boxes = Array.from(form.querySelectorAll('.cut-ticket-checkbox'));
    const selectAll = document.getElementById('selectAllVisible');
    const payButton = document.getElementById('paySelectedButton');
    const currency = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

    function updateSummary() {
        const selected = boxes.filter(box => box.checked);
        const sum = key => selected.reduce((total, box) => total + Number(box.dataset[key] || 0), 0);
        document.getElementById('selectedCount').textContent = selected.length;
        document.getElementById('selectedLabor').textContent = currency.format(sum('labor'));
        document.getElementById('selectedMaterials').textContent = currency.format(sum('materials'));
        document.getElementById('selectedGrand').textContent = currency.format(sum('grand'));
        payButton.disabled = selected.length === 0;
        boxes.forEach(box => box.closest('tr')?.classList.toggle('is-selected', box.checked));
        if (selectAll) {
            selectAll.innerHTML = selected.length === boxes.length
                ? '<i class="bi bi-square"></i> Quitar selección'
                : '<i class="bi bi-check2-square"></i> Seleccionar todos';
        }
    }

    boxes.forEach(box => {
        box.addEventListener('change', updateSummary);
        box.closest('tr')?.addEventListener('click', event => {
            if (event.target.closest('a, input, button')) return;
            box.checked = !box.checked;
            updateSummary();
        });
    });
    selectAll?.addEventListener('click', () => {
        const shouldSelect = boxes.some(box => !box.checked);
        boxes.forEach(box => box.checked = shouldSelect);
        updateSummary();
    });
    form.addEventListener('submit', event => {
        const count = boxes.filter(box => box.checked).length;
        if (!count || !window.confirm(`¿Confirmas el pago de ${count} ticket(s)? Después del pago sus costos no podrán modificarse.`)) {
            event.preventDefault();
        } else {
            payButton.disabled = true;
            payButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Registrando pago...';
        }
    });
    updateSummary();
});
</script>
@endpush
