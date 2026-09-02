@extends('layouts.app')

@section('title', 'Corte de efectivo | '.config('app.name'))

@section('content')
    @php
        $showHistory = !$errors->any() && (session('success') || request('tab') === 'historial');
    @endphp

    <div class="cash-cuts py-8">
        <div class="cash-cut-heading">
            <div>
                <div class="cash-cut-eyebrow">Finanzas · Cobranza</div>
                <h1>Corte de efectivo</h1>
                <p>Confirma el dinero en efectivo que entregan los asesores y conserva el historial de cada recepción.</p>
            </div>
            <a href="{{ route('charges.index') }}" class="maintenance-plain-btn">
                <i class="bi bi-wallet2"></i> Ir a cobranza
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>No se pudo confirmar la recepción.</strong>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <div class="cash-cut-metrics">
            <article><span class="is-blue"><i class="bi bi-cash-stack"></i></span><div><small>Pagos por recibir</small><strong>{{ $payments->count() }}</strong></div></article>
            <article><span class="is-purple"><i class="bi bi-person-badge"></i></span><div><small>Asesores con efectivo</small><strong>{{ $pendingAdvisorCount }}</strong></div></article>
            <article><span class="is-amber"><i class="bi bi-hourglass-split"></i></span><div><small>Efectivo pendiente</small><strong>${{ number_format($pendingTotal, 2) }}</strong></div></article>
            <article><span class="is-green"><i class="bi bi-patch-check"></i></span><div><small>Histórico recibido</small><strong>${{ number_format($receivedGrandTotal, 2) }}</strong></div></article>
        </div>

        <div class="cash-cut-tabs" role="tablist" aria-label="Secciones del corte de efectivo">
            <button class="cash-cut-tab {{ $showHistory ? '' : 'active' }}" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-pane" type="button" role="tab" aria-controls="pending-pane" aria-selected="{{ $showHistory ? 'false' : 'true' }}">
                <i class="bi bi-list-check"></i> Pendientes de recibir <span>{{ $payments->count() }}</span>
            </button>
            <button class="cash-cut-tab {{ $showHistory ? 'active' : '' }}" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab" aria-controls="history-pane" aria-selected="{{ $showHistory ? 'true' : 'false' }}">
                <i class="bi bi-clock-history"></i> Historial de cortes <span>{{ $cuts->total() }}</span>
            </button>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade {{ $showHistory ? '' : 'show active' }}" id="pending-pane" role="tabpanel" aria-labelledby="pending-tab" tabindex="0">
                <section class="cash-cut-panel">
                    <div class="cash-cut-panel-heading">
                        <div>
                            <h2>Efectivo en poder de asesores</h2>
                            <p>Solo aparecen pagos de cobranza exitosos y registrados como efectivo. Cada corte corresponde a un solo asesor.</p>
                        </div>
                        @if ($payments->isNotEmpty())
                            <button type="button" class="cash-cut-select-all" id="selectAllVisible"><i class="bi bi-check2-square"></i> Seleccionar todos</button>
                        @endif
                    </div>

                    @if ($payments->isEmpty())
                        <div class="cash-cut-empty">
                            <span><i class="bi bi-check-circle"></i></span>
                            <h3>Todo el efectivo está conciliado</h3>
                            <p>No hay pagos en efectivo pendientes de recibir.</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('cash-cuts.store') }}" id="cashCutForm">
                            @csrf
                            <div class="cash-cut-workspace">
                                <div class="cash-cut-table-column">
                                    <div class="table-responsive">
                                        <table class="table cash-cut-table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="cash-cut-check"><span class="visually-hidden">Seleccionar</span></th>
                                                    <th>Concepto</th>
                                                    <th>Propiedad</th>
                                                    <th>Inquilino</th>
                                                    <th>Asesor</th>
                                                    <th>Fecha de pago</th>
                                                    <th class="text-end">Efectivo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($payments as $payment)
                                                    @php
                                                        $advisorKey = 'advisor-'.($payment->registered_by ?? 'none');
                                                        $advisorName = $payment->registeredBy?->name ?? 'Usuario no disponible';
                                                    @endphp
                                                    <tr class="cash-cut-row">
                                                        <td class="cash-cut-check">
                                                            <input class="form-check-input cash-payment-checkbox" type="checkbox" name="payment_ids[]" value="{{ $payment->id }}"
                                                                data-amount="{{ (float) $payment->amount }}" data-advisor-key="{{ $advisorKey }}" data-advisor-name="{{ $advisorName }}"
                                                                @checked(in_array($payment->id, old('payment_ids', []))) aria-label="Seleccionar pago {{ $payment->id }}">
                                                        </td>
                                                        <td>
                                                            <a class="cash-cut-concept" href="{{ route('charges.show', $payment->charge) }}">
                                                                <strong>{{ $payment->charge?->concept ?? 'Concepto no disponible' }}</strong>
                                                                <span>Pago #{{ $payment->id }} · {{ $payment->charge?->type_label }}</span>
                                                            </a>
                                                        </td>
                                                        <td><strong>{{ $payment->charge?->property?->internal_name ?? '-' }}</strong><small>{{ $payment->charge?->property?->internal_reference ?: 'Sin referencia' }}</small></td>
                                                        <td>{{ $payment->charge?->tenant?->full_name ?? '-' }}</td>
                                                        <td><span class="cash-cut-advisor"><i class="bi bi-person"></i>{{ $advisorName }}</span></td>
                                                        <td class="text-nowrap">{{ ($payment->payment_date ?? $payment->paid_at)?->format('d/m/Y') ?: '-' }}</td>
                                                        <td class="text-end text-nowrap fw-bold">${{ number_format((float) $payment->amount, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <aside class="cash-cut-summary" aria-live="polite">
                                    <div class="cash-cut-summary-heading"><span><i class="bi bi-receipt-cutoff"></i></span><div><small>Resumen del corte</small><h3>Recepción seleccionada</h3></div></div>
                                    <div class="cash-cut-summary-count"><span><strong id="selectedCount">0</strong> pagos seleccionados</span><i class="bi bi-check2-square"></i></div>
                                    <div class="cash-cut-summary-lines">
                                        <div><span>Entrega</span><strong id="selectedAdvisor">Sin seleccionar</strong></div>
                                        <div class="is-total"><span>Total a recibir</span><strong id="selectedTotal">$0.00</strong></div>
                                    </div>
                                    <p><i class="bi bi-shield-check"></i> Al confirmar, cada pago quedará marcado como recibido y pasará al historial.</p>
                                    <button class="maintenance-primary-btn" type="submit" id="receiveSelectedButton" disabled><i class="bi bi-check2-circle"></i> Confirmar recepción</button>
                                </aside>
                            </div>
                        </form>
                    @endif
                </section>
            </div>

            <div class="tab-pane fade {{ $showHistory ? 'show active' : '' }}" id="history-pane" role="tabpanel" aria-labelledby="history-tab" tabindex="0">
                <section class="cash-cut-panel">
                    <div class="cash-cut-panel-heading"><div><h2>Historial de efectivo recibido</h2><p>Cada corte conserva el asesor, el administrador receptor y los importes confirmados.</p></div></div>

                    @forelse ($cuts as $cut)
                        <details class="cash-cut-history" @if ($loop->first && session('success')) open @endif>
                            <summary>
                                <span class="cash-cut-reference"><span><i class="bi bi-check-lg"></i></span><span><strong>{{ $cut->display_reference }}</strong><small>{{ $cut->payment_count }} pagos · Recibido</small></span></span>
                                <span><small>Entregó</small><strong>{{ $cut->advisor_name }}</strong></span>
                                <span><small>Recibió</small><strong>{{ $cut->received_by_name }}</strong></span>
                                <span><small>Fecha de recepción</small><strong>{{ $cut->received_at?->format('d/m/Y H:i') }}</strong></span>
                                <span class="cash-cut-history-total"><small>Total recibido</small><strong>${{ number_format((float) $cut->grand_total, 2) }}</strong></span>
                                <i class="bi bi-chevron-down cash-cut-chevron"></i>
                            </summary>
                            <div class="table-responsive cash-cut-history-detail">
                                <table class="table align-middle mb-0">
                                    <thead><tr><th>Concepto</th><th>Propiedad</th><th>Inquilino</th><th>Fecha de pago</th><th class="text-end">Importe recibido</th><th>Estado</th></tr></thead>
                                    <tbody>
                                        @foreach ($cut->items as $item)
                                            <tr>
                                                <td>
                                                    @if ($item->payment?->charge)
                                                        <a href="{{ route('charges.show', $item->payment->charge) }}" class="cash-cut-history-link">{{ $item->charge_concept }}</a>
                                                    @else
                                                        <strong>{{ $item->charge_concept }}</strong>
                                                    @endif
                                                </td>
                                                <td>{{ $item->property_name ?: '-' }}</td>
                                                <td>{{ $item->tenant_name ?: '-' }}</td>
                                                <td class="text-nowrap">{{ $item->payment_date?->format('d/m/Y') ?: '-' }}</td>
                                                <td class="text-end text-nowrap fw-bold">${{ number_format((float) $item->amount, 2) }}</td>
                                                <td><span class="cash-cut-received-badge"><i class="bi bi-check-circle-fill"></i> Recibido</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    @empty
                        <div class="cash-cut-empty is-compact"><span><i class="bi bi-receipt"></i></span><h3>Aún no hay cortes recibidos</h3><p>La primera recepción confirmada aparecerá aquí.</p></div>
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
    .cash-cuts{display:grid;gap:1.25rem}.cash-cut-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem}.cash-cut-heading h1{margin:.2rem 0;font-size:clamp(1.7rem,3vw,2.35rem);font-weight:800;color:#101d3f}.cash-cut-heading p{margin:0;color:#77819a}.cash-cut-eyebrow{text-transform:uppercase;letter-spacing:.12em;color:#ff3366;font-size:.76rem;font-weight:800}.cash-cut-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.cash-cut-metrics article{display:flex;align-items:center;gap:.8rem;background:#fff;border:1px solid #e8ebf2;border-radius:18px;padding:1rem;box-shadow:0 8px 24px rgba(20,36,72,.05)}.cash-cut-metrics article>span{width:42px;height:42px;display:grid;place-items:center;border-radius:13px;font-size:1.1rem}.cash-cut-metrics small,.cash-cut-history small{display:block;color:#8490aa;font-size:.76rem}.cash-cut-metrics strong{display:block;color:#15213f;font-size:1.05rem;white-space:nowrap}.cash-cut-metrics .is-blue{color:#2878e5;background:#eaf3ff}.cash-cut-metrics .is-purple{color:#7b4ee7;background:#f1edff}.cash-cut-metrics .is-amber{color:#d98b00;background:#fff5db}.cash-cut-metrics .is-green{color:#0aa862;background:#e5f9ef}.cash-cut-tabs{display:flex;align-items:center;gap:.45rem;padding:.35rem;background:#eef1f6;border-radius:15px;width:max-content;max-width:100%}.cash-cut-tab{display:flex;align-items:center;gap:.5rem;border:0;background:transparent;color:#6e7892;border-radius:11px;padding:.7rem 1rem;font-weight:700}.cash-cut-tab>span{display:grid;place-items:center;min-width:23px;height:23px;padding:0 .35rem;border-radius:999px;background:#dfe4ed;font-size:.72rem}.cash-cut-tab.active{background:#fff;color:#ef285c;box-shadow:0 4px 14px rgba(25,40,75,.09)}.cash-cut-tab.active>span{background:#fff0f4}.cash-cut-panel{background:#fff;border:1px solid #e8ebf2;border-radius:20px;box-shadow:0 10px 32px rgba(20,36,72,.06);overflow:hidden}.cash-cut-panel-heading{padding:1.25rem 1.4rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid #edf0f5}.cash-cut-panel-heading h2{font-size:1.12rem;font-weight:800;color:#15213f;margin:0}.cash-cut-panel-heading p{color:#8490aa;margin:.25rem 0 0}.cash-cut-select-all{border:0;background:#fff0f4;color:#e92158;border-radius:12px;padding:.65rem .9rem;font-weight:700;white-space:nowrap}.cash-cut-workspace{display:grid;grid-template-columns:minmax(0,1fr) 320px;align-items:start;gap:1.25rem;padding:1.25rem;background:#f7f8fb}.cash-cut-table-column{min-width:0;background:#fff;border:1px solid #e7ebf2;border-radius:16px;overflow:hidden}.cash-cut-table{table-layout:fixed}.cash-cut-table th{color:#77819a;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;padding:.9rem .55rem}.cash-cut-table td{padding:1rem .55rem;border-bottom-color:#f0f2f6;color:#303b57}.cash-cut-table th:last-child,.cash-cut-table td:last-child{padding-right:2rem!important}.cash-cut-table th:nth-child(2){width:21%}.cash-cut-table th:nth-child(3){width:16%}.cash-cut-table th:nth-child(4){width:15%}.cash-cut-table th:nth-child(5){width:16%}.cash-cut-table th:nth-child(6){width:12%}.cash-cut-table th:nth-child(7){width:11%}.cash-cut-check{width:42px!important;text-align:center}.cash-cut-row{cursor:pointer;transition:.15s ease}.cash-cut-row:hover,.cash-cut-row.is-selected{background:#fff7f9}.cash-cut-row.is-incompatible{background:#f8f9fc;cursor:not-allowed;opacity:.5}.cash-cut-concept{display:flex;flex-direction:column;color:#15213f;text-decoration:none}.cash-cut-concept strong{color:#ef285c;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.cash-cut-concept span,.cash-cut-table td small{display:block;color:#8490aa;font-size:.8rem}.cash-cut-advisor{display:flex;align-items:center;gap:.35rem;font-weight:700}.cash-cut-summary{position:sticky;top:1rem;background:#fff;border:1px solid #e2e7f0;border-radius:18px;padding:1.2rem;box-shadow:0 12px 28px rgba(25,40,75,.09)}.cash-cut-summary-heading{display:flex;align-items:center;gap:.75rem;padding-bottom:1rem;border-bottom:1px solid #e8ecf3}.cash-cut-summary-heading>span{width:40px;height:40px;display:grid;place-items:center;border-radius:12px;background:#fff0f4;color:#ef285c}.cash-cut-summary-heading small{display:block;color:#8792aa;font-size:.72rem}.cash-cut-summary-heading h3{font-size:1rem;margin:.12rem 0 0;color:#17213b;font-weight:800}.cash-cut-summary-count{display:flex;align-items:center;justify-content:space-between;margin:1rem 0;padding:.8rem .9rem;border-radius:12px;background:#f6f7fa;color:#69758e;border:1px solid #ebedf3;font-size:.82rem}.cash-cut-summary-count strong{color:#17213b;font-size:1.2rem;margin-right:.25rem}.cash-cut-summary-count i{color:#ff5b83}.cash-cut-summary-lines{display:grid;gap:.8rem}.cash-cut-summary-lines>div{display:flex;justify-content:space-between;gap:1rem;color:#69758e;font-size:.84rem}.cash-cut-summary-lines strong{color:#17213b;text-align:right}.cash-cut-summary-lines .is-total{margin-top:.2rem;padding-top:1rem;border-top:1px solid #e5e9f0;align-items:center;font-weight:700;color:#17213b}.cash-cut-summary-lines .is-total strong{color:#0a9a5a;font-size:1.45rem}.cash-cut-summary>p{display:flex;gap:.45rem;margin:1rem 0;color:#7d889f;font-size:.73rem;line-height:1.4}.cash-cut-summary>p i{color:#ef285c}.cash-cut-summary button{width:100%;justify-content:center}.cash-cut-summary button:disabled{opacity:.45;cursor:not-allowed}.cash-cut-empty{text-align:center;padding:3rem 1rem}.cash-cut-empty>span{display:grid;place-items:center;margin:0 auto .8rem;width:52px;height:52px;border-radius:16px;background:#e9f9f0;color:#0aa862;font-size:1.35rem}.cash-cut-empty h3{font-size:1.05rem;margin:0;color:#15213f}.cash-cut-empty p{color:#8490aa;margin:.3rem 0 0}.cash-cut-empty.is-compact{padding:2rem}.cash-cut-history{border-bottom:1px solid #edf0f5}.cash-cut-history summary{list-style:none;cursor:pointer;display:grid;grid-template-columns:1.3fr 1fr 1fr 1fr 1fr auto;align-items:center;gap:1rem;padding:1rem 1.35rem}.cash-cut-history summary::-webkit-details-marker{display:none}.cash-cut-history summary>span>strong{display:block;color:#24304d;font-size:.88rem}.cash-cut-reference{display:flex;align-items:center;gap:.65rem}.cash-cut-reference>span:first-child{display:grid;width:34px;height:34px;place-items:center;border-radius:11px;background:#e5f9ef;color:#0aa862}.cash-cut-history-total strong{color:#0a9a5a!important;font-size:1rem!important}.cash-cut-chevron{color:#8490aa;transition:transform .2s}.cash-cut-history[open] .cash-cut-chevron{transform:rotate(180deg)}.cash-cut-history-detail{background:#f8f9fc;border-top:1px solid #edf0f5;padding:.35rem 1.1rem}.cash-cut-history-detail table{background:#fff}.cash-cut-history-detail th{font-size:.75rem;color:#8490aa}.cash-cut-history-link{color:#ef285c;font-weight:700;text-decoration:none}.cash-cut-received-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .55rem;border-radius:999px;background:#e5f9ef;color:#087f4b;font-size:.76rem;font-weight:800}.form-check-input:checked{background-color:#ff3366;border-color:#ff3366}
    @media(max-width:1200px){.cash-cut-workspace{grid-template-columns:1fr}.cash-cut-summary{position:static}.cash-cut-history summary{grid-template-columns:1.3fr 1fr 1fr 1fr auto}.cash-cut-history summary>span:nth-of-type(3){display:none}}
    @media(max-width:900px){.cash-cut-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.cash-cut-table{min-width:980px;table-layout:auto}.cash-cut-history summary{grid-template-columns:1.3fr 1fr 1fr auto}.cash-cut-history summary>span:nth-of-type(2),.cash-cut-history summary>span:nth-of-type(3){display:none}}
    @media(max-width:767px){.cash-cuts{padding-top:1rem!important;padding-bottom:6rem!important}.cash-cut-heading{align-items:flex-start;flex-direction:column}.cash-cut-heading .maintenance-plain-btn{width:100%;justify-content:center}.cash-cut-heading p{font-size:.9rem}.cash-cut-metrics{gap:.6rem}.cash-cut-metrics article{padding:.75rem;border-radius:14px}.cash-cut-metrics article>span{display:none}.cash-cut-metrics strong{font-size:.92rem}.cash-cut-tabs{width:100%;display:grid;grid-template-columns:1fr 1fr}.cash-cut-tab{justify-content:center;padding:.7rem .4rem;font-size:.8rem}.cash-cut-tab>i{display:none}.cash-cut-panel{border-radius:16px}.cash-cut-panel-heading{align-items:flex-start;padding:1rem;flex-direction:column}.cash-cut-select-all{width:100%}.cash-cut-workspace{padding:.75rem}.cash-cut-summary{padding:1rem}.cash-cut-history summary{grid-template-columns:1fr 1fr auto;padding:.9rem 1rem}.cash-cut-history summary>span:nth-of-type(4){display:none}.cash-cut-history-total{text-align:right}.cash-cut-history-detail{padding:.2rem}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('cashCutForm');
    if (!form) return;

    const boxes = Array.from(form.querySelectorAll('.cash-payment-checkbox'));
    const selectAll = document.getElementById('selectAllVisible');
    const receiveButton = document.getElementById('receiveSelectedButton');
    const currency = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

    function updateSummary() {
        const selected = boxes.filter(box => box.checked);
        const advisorKey = selected[0]?.dataset.advisorKey || null;
        const compatible = advisorKey ? boxes.filter(box => box.dataset.advisorKey === advisorKey) : boxes;
        boxes.forEach(box => {
            const incompatible = Boolean(advisorKey && box.dataset.advisorKey !== advisorKey);
            box.disabled = incompatible;
            box.closest('tr')?.classList.toggle('is-selected', box.checked);
            box.closest('tr')?.classList.toggle('is-incompatible', incompatible);
        });
        document.getElementById('selectedCount').textContent = selected.length;
        document.getElementById('selectedAdvisor').textContent = selected[0]?.dataset.advisorName || 'Sin seleccionar';
        document.getElementById('selectedTotal').textContent = currency.format(selected.reduce((total, box) => total + Number(box.dataset.amount || 0), 0));
        receiveButton.disabled = selected.length === 0;
        if (selectAll) {
            selectAll.innerHTML = selected.length > 0 && selected.length === compatible.length
                ? '<i class="bi bi-square"></i> Quitar selección'
                : '<i class="bi bi-check2-square"></i> Seleccionar todos';
        }
    }

    boxes.forEach(box => {
        box.addEventListener('change', updateSummary);
        box.closest('tr')?.addEventListener('click', event => {
            if (box.disabled || event.target.closest('a, input, button')) return;
            box.checked = !box.checked;
            updateSummary();
        });
    });
    selectAll?.addEventListener('click', () => {
        const selected = boxes.filter(box => box.checked);
        const advisorKey = selected[0]?.dataset.advisorKey || boxes[0]?.dataset.advisorKey;
        const compatible = boxes.filter(box => box.dataset.advisorKey === advisorKey);
        const shouldSelect = compatible.some(box => !box.checked);
        compatible.forEach(box => box.checked = shouldSelect);
        updateSummary();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const count = boxes.filter(box => box.checked).length;
        if (!count || receiveButton.disabled) return;

        const result = await window.Swal.fire({
            title: 'Confirmar recepción',
            text: `¿Confirmas que recibiste el efectivo de ${count} pago(s)?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar recepción',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-light',
            },
        });
        if (!result.isConfirmed) return;

        receiveButton.disabled = true;
        receiveButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Confirmando recepción...';
        form.submit();
    });
    updateSummary();
});
</script>
@endpush
