@extends('layouts.payment')

@section('title', 'Cargo pagado')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body text-center py-15 px-10">
                    <div class="symbol symbol-90px mx-auto mb-6">
                        <span class="symbol-label bg-light-success">
                            <i class="ki-outline ki-shield-tick fs-1 text-success"></i>
                        </span>
                    </div>
                    <h1 class="fw-bold mb-3">Este cargo ya esta pagado</h1>
                    <p class="text-muted fs-6 mb-7">
                        No es necesario realizar otra transaccion.
                    </p>

                    <div class="bg-light rounded p-6 mb-7 text-start">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Concepto</span>
                            <span class="fw-semibold">{{ $charge->concept }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Monto</span>
                            <span class="fw-semibold">${{ number_format((float) $charge->amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Fecha de pago</span>
                            <span class="fw-semibold">{{ $charge->paid_at?->format('d/m/Y H:i') ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
