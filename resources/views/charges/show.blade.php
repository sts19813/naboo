@extends('layouts.app')

@section('title', 'Detalle de cargo | SuWork')

@section('content')
    <div class="py-10 charges-module">
        <div class="mb-8">
            <a href="{{ $returnUrl }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i>
                {{ str_contains($returnUrl, '/propiedades/') ? 'Volver a la propiedad' : 'Volver a cobranza' }}
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-information fs-2hx text-warning me-4"></i>
                <div class="fw-semibold">{{ session('warning') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-cross-circle fs-2hx text-danger me-4"></i>
                <div class="fw-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <div class="card mb-8">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="fw-bold">Detalle del cargo</h3>
                </div>
                <div class="card-toolbar d-flex gap-2">
                    @if ($canManageCharges && in_array($charge->status, [\App\Models\Charge::STATUS_PENDING, \App\Models\Charge::STATUS_PARTIAL, \App\Models\Charge::STATUS_IN_VALIDATION], true))
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#registerPaymentModal">
                            Registrar pago
                        </button>
                    @endif
                    @if ($canManageCharges)
                        <button class="btn btn-light-primary btn-sm" data-bs-toggle="modal" data-bs-target="#notifyChargeModal">
                            Notificacion
                        </button>
                    @endif
                    @if ($canDeleteCharge)
                        <form method="POST" action="{{ route('charges.destroy', $charge) }}"
                            class="d-inline js-delete-charge-form"
                            data-charge-concept="{{ $charge->concept }}"
                            data-charge-paid="{{ $charge->status === \App\Models\Charge::STATUS_PAID ? 'true' : 'false' }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="deletion_note" value="">
                            <input type="hidden" name="return_to" value="{{ $returnUrl }}">
                            <button type="submit" class="btn btn-light-danger btn-sm">Eliminar cargo</button>
                        </form>
                    @endif
                    <a href="{{ route('charges.public.show', ['token' => $charge->payment_token]) }}" target="_blank" class="btn btn-light btn-sm">
                        Abrir link de pago
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-6">
                    <div class="col-lg-6">
                        <div class="text-muted fs-7 mb-1">Concepto</div>
                        <div class="fw-bold fs-4">{{ $charge->concept }}</div>
                    </div>
                    <div class="col-lg-3">
                        <div class="text-muted fs-7 mb-1">Tipo</div>
                        <div class="fw-semibold">{{ $charge->type_label }}</div>
                    </div>
                    <div class="col-lg-3">
                        <div class="text-muted fs-7 mb-1">Estado</div>
                        <span class="badge {{ $charge->status_badge_class }}">{{ $charge->display_status_label }}</span>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted fs-7 mb-1">Inquilino</div>
                        <div class="fw-semibold">{{ $charge->tenant?->full_name ?? '-' }}</div>
                        <div class="text-muted fs-8">{{ $charge->tenant?->email ?? '-' }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted fs-7 mb-1">Propiedad</div>
                        <div class="fw-semibold">{{ $charge->property?->internal_name ?? '-' }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted fs-7 mb-1">Vencimiento</div>
                        <div class="fw-semibold">{{ $charge->due_date?->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted fs-7 mb-1">Monto</div>
                        <div class="fw-bold fs-4">${{ number_format((float) $charge->amount, 2) }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted fs-7 mb-1">Pagado</div>
                        <div class="fw-bold fs-4 text-success">${{ number_format((float) $charge->paid_amount, 2) }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted fs-7 mb-1">Saldo pendiente</div>
                        <div class="fw-bold fs-4 text-danger">${{ number_format((float) $charge->outstanding_amount, 2) }}</div>
                    </div>
                    <div class="col-lg-6">
                        <div class="text-muted fs-7 mb-1">Periodo</div>
                        <div class="fw-semibold">{{ str_pad((string) $charge->period_month, 2, '0', STR_PAD_LEFT) }}/{{ $charge->period_year }}</div>
                    </div>
                    <div class="col-lg-6">
                        <div class="text-muted fs-7 mb-1">Pago confirmado en</div>
                        <div class="fw-semibold">{{ $charge->paid_at?->format('d/m/Y H:i') ?? '-' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted fs-7 mb-1">Notas</div>
                        <div class="fw-semibold">{{ $charge->notes ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-8">
            <div class="card-header">
                <h3 class="card-title fw-bold">Pagos registrados</h3>
            </div>
            <div class="card-body py-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-4 mb-0">
                        <thead>
                            <tr class="text-muted text-uppercase fs-8">
                                <th>Monto</th>
                                <th>Metodo</th>
                                <th>Fecha</th>
                                <th>Referencia</th>
                                <th>Estado</th>
                                <th>Comprobante</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($charge->payments as $payment)
                                <tr>
                                    <td class="fw-bold">${{ number_format((float) $payment->amount, 2) }}</td>
                                    <td>{{ $payment->method_label }}</td>
                                    <td>{{ $payment->payment_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ $payment->reference ?: '-' }}</td>
                                    <td>
                                        @php
                                            $statusClass = match ($payment->status) {
                                                \App\Models\ChargePayment::STATUS_SUCCEEDED => 'badge-light-success text-success',
                                                \App\Models\ChargePayment::STATUS_PENDING_VALIDATION => 'badge-light-primary text-primary',
                                                \App\Models\ChargePayment::STATUS_FAILED => 'badge-light-danger text-danger',
                                                default => 'badge-light-secondary text-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $payment->status_label }}</span>
                                    </td>
                                    <td>
                                        @if ($payment->receipt_path)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($payment->receipt_path) }}"
                                                target="_blank" class="btn btn-sm btn-light-primary">
                                                Ver
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($canManageCharges && $payment->status === \App\Models\ChargePayment::STATUS_PENDING_VALIDATION)
                                            <form method="POST" action="{{ route('charges.payments.validate', [$charge, $payment]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">Validar comprobante</button>
                                            </form>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-10">Sin pagos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($canManageCharges)
    <div class="modal fade" id="registerPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('charges.payments.store', $charge) }}" enctype="multipart/form-data" class="h-100 d-flex flex-column">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Registrar pago</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="bg-light rounded p-4 mb-6">
                            <div class="text-muted fs-7 mb-1">Cargo a cubrir</div>
                            <div class="fw-bold fs-4">{{ $charge->concept }}</div>
                            <div class="text-muted fs-6">
                                Saldo pendiente: <span class="text-danger fw-bold">${{ number_format($charge->outstanding_amount, 2) }}</span>
                            </div>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Monto (MXN)</label>
                                <input type="number" min="0.01" step="0.01" max="{{ number_format($charge->outstanding_amount, 2, '.', '') }}"
                                    name="amount" value="{{ old('amount', number_format($charge->outstanding_amount, 2, '.', '')) }}"
                                    class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha de pago</label>
                                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}"
                                    class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Metodo de pago</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($paymentMethods as $methodValue => $methodLabel)
                                        <option value="{{ $methodValue }}" {{ old('payment_method') === $methodValue ? 'selected' : '' }}>
                                            {{ $methodLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Referencia / folio</label>
                                <input type="text" name="reference" class="form-control" value="{{ old('reference') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Comprobante de pago</label>
                                <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Registrar pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="notifyChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <form method="POST" action="{{ route('charges.notify', $charge) }}">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Enviar notificacion</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-12">
                                <label class="form-label required">Canal</label>
                                <select name="channel" class="form-select" required>
                                    <option value="email" selected>Correo</option>
                                    <option value="whatsapp">WhatsApp (proximamente)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Dias antes del vencimiento</label>
                                <input type="number" name="days_before" min="0" max="30" value="3" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Mensaje adicional</label>
                                <textarea name="message" class="form-control" rows="3" placeholder="Mensaje opcional al inquilino"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    @include('charges.partials.delete-confirmation-script')

    @if ($canManageCharges && $errors->registerPayment->any())
        <script>
            (() => {
                const modalEl = document.getElementById('registerPaymentModal');
                if (!modalEl) return;
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif

    @if ($canManageCharges && $errors->chargeReminder->any())
        <script>
            (() => {
                const modalEl = document.getElementById('notifyChargeModal');
                if (!modalEl) return;
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif
@endpush
