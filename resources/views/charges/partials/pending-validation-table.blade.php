<div class="charges-list-table-card">
    <div class="table-responsive">
        <table class="table table-row-bordered align-middle mb-0" id="pendingValidationTable">
            <thead>
                <tr class="fw-bold text-muted text-uppercase fs-8">
                    <th class="ps-7 min-w-220px">Concepto</th>
                    <th class="min-w-220px">Inquilino / Propiedad</th>
                    <th class="min-w-150px">Fecha del pago</th>
                    <th class="min-w-140px">Monto enviado</th>
                    <th class="min-w-150px">Referencia</th>
                    <th class="min-w-150px">Comprobante</th>
                    <th class="min-w-180px text-end pe-7">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendingValidationPayments as $payment)
                    @php($charge = $payment->charge)
                    <tr class="charges-list-row" data-pending-validation-row>
                        <td class="ps-7">
                            <div class="charges-list-title">{{ $charge?->concept ?? 'Cargo' }}</div>
                            <div class="charges-list-meta">{{ $charge?->type_label ?? '-' }}</div>
                        </td>
                        <td data-mobile-label="Inquilino / propiedad">
                            <div class="charges-list-value">{{ $charge?->tenant?->full_name ?? '-' }}</div>
                            <div class="charges-list-meta">
                                {{ $charge?->property?->internal_name ?: '-' }}
                                @if ($charge?->property?->internal_reference)
                                    · Ref. {{ $charge->property->internal_reference }}
                                @endif
                            </div>
                        </td>
                        <td data-mobile-label="Fecha del pago">
                            <div class="charges-list-value">{{ $payment->payment_date?->format('d M Y') ?? $payment->created_at?->format('d M Y') ?? '-' }}</div>
                        </td>
                        <td data-mobile-label="Monto enviado">
                            <div class="charges-list-value">${{ number_format((float) $payment->amount, 2) }}</div>
                        </td>
                        <td data-mobile-label="Referencia">
                            <div class="charges-list-value">{{ $payment->reference ?: '-' }}</div>
                        </td>
                        <td data-mobile-label="Comprobante">
                            @if (count($payment->receipt_files) > 0)
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($payment->receipt_files as $index => $receiptPath)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($receiptPath) }}" target="_blank"
                                            rel="noopener" class="btn btn-sm btn-light-primary">
                                            Ver {{ count($payment->receipt_files) > 1 ? $index + 1 : 'comprobante' }}
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">Sin comprobante</span>
                            @endif
                        </td>
                        <td class="text-end pe-7" data-mobile-label="Acciones">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                @if ($charge)
                                    <a class="btn btn-sm btn-light" href="{{ route('charges.show', array_filter([
                                        'charge' => $charge,
                                        'property' => $selectedProperty?->uuid,
                                        'return_to' => request()->fullUrl(),
                                    ])) }}">Ver cargo</a>

                                    @if ($canManageCharges)
                                        <form method="POST" action="{{ route('charges.payments.validate', [$charge, $payment]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">Validar</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
