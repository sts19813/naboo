@extends('layouts.payment')

@section('title', 'Pagar cargo')

@section('content')
    @php
        $bankContactPhone = $bankOwner?->phone ?: '9911013535';
        $bankContactEmail = $bankOwner?->email ?: 'sts19813@gmail.com';
        $bankName = $bankOwner?->bank_name ?: 'BBVA';
        $bankClabe = $bankOwner?->clabe ?: '101010101010101001';
        $bankNameOwner = $bankOwner?->name ?: 'Beneficiario';
    @endphp

    <div class="row justify-content-center g-8">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Pago de cargo</h3>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success mb-6">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (request()->boolean('cancelled'))
                        <div class="alert alert-warning mb-6">
                            El pago fue cancelado. Puedes intentarlo de nuevo.
                        </div>
                    @endif

                    <div class="mb-7">
                        <div class="text-muted fs-7 mb-1">Concepto</div>
                        <div class="fw-bold fs-4 text-dark">{{ $charge->concept }}</div>
                        <div class="text-muted fs-7">{{ $charge->type_label }}</div>
                    </div>

                    <div class="row g-5 mb-7">
                        <div class="col-sm-6">
                            <div class="text-muted fs-7 mb-1">Inquilino</div>
                            <div class="fw-semibold text-dark">{{ $charge->tenant?->full_name ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted fs-7 mb-1">Propiedad</div>
                            <div class="fw-semibold text-dark">{{ $charge->property?->internal_name ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted fs-7 mb-1">Periodo</div>
                            <div class="fw-semibold text-dark">
                                {{ str_pad((string) $charge->period_month, 2, '0', STR_PAD_LEFT) }}/{{ $charge->period_year }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted fs-7 mb-1">Vencimiento</div>
                            <div class="fw-semibold text-dark">{{ $charge->due_date?->format('d/m/Y') }}</div>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-6"></div>

                    <div class="d-flex justify-content-between align-items-center mb-7">
                        <span class="fw-semibold fs-5">Total pendiente</span>
                        <span class="fw-bold fs-2 text-primary">${{ number_format($charge->outstanding_amount, 2) }}</span>
                    </div>

                    <form method="POST" action="{{ route('charges.public.checkout', ['token' => $charge->payment_token]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            Pagar con Stripe
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Pagar por transferencia</h3>
                </div>
                <div class="card-body">
                    <div class="bg-light rounded p-4 mb-6">
                        <div class="fw-semibold mb-1"> Nombre Beneficiario: {{ $bankNameOwner }} </div>
                        <div class="text-muted">Banco: {{ $bankName }} | CLABE: {{ $bankClabe }}</div>
                    </div>

                    @if ($errors->transferProof->any())
                        <div class="alert alert-danger mb-5">
                            Revisa la informacion del comprobante.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('charges.public.transfer-proof', ['token' => $charge->payment_token]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label required">Monto transferido (MXN)</label>
                                <input type="number" min="0.01" step="0.01" name="amount" class="form-control"
                                    value="{{ old('amount', number_format($charge->outstanding_amount, 2, '.', '')) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Fecha en la que se realizó el pago</label>
                                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}"
                                    class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Referencia / folio</label>
                                <input type="text" name="reference" value="{{ old('reference') }}" class="form-control"
                                    placeholder="Numero de referencia bancaria">
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Comprobante (imagen)</label>
                                <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-light-primary w-100 fw-bold">
                                    Enviar comprobante para validacion
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
