@extends('layouts.payment')

@section('title', 'Pago recibido')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body text-center py-15 px-10">
                    <div class="symbol symbol-90px mx-auto mb-6">
                        <span class="symbol-label bg-light-success">
                            <i class="ki-outline ki-check-circle fs-1 text-success"></i>
                        </span>
                    </div>
                    <h1 class="fw-bold mb-3">Pago completado</h1>
                    <p class="text-muted fs-6 mb-7">
                        Tu pago fue procesado correctamente.
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
                            <span class="text-muted">Estado</span>
                            <span class="badge {{ $charge->status_badge_class }}">{{ $charge->display_status_label }}</span>
                        </div>
                    </div>

                    <a href="{{ route('charges.public.show', ['token' => $charge->payment_token]) }}" class="btn btn-light-primary">
                        Ver detalle
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
