@extends('layouts.app')

@section('title', 'Suscripción | Naboo')

@push('styles')
    <style>
        .subscription-hero {
            border: 0;
            border-radius: 14px;
            color: #fff;
            background: linear-gradient(135deg, #202936 0%, #303d50 68%, #ff3364 160%);
        }

        .subscription-stat {
            height: 100%;
            border: 1px solid var(--border-soft, #e7eaf0);
            border-radius: 12px;
            background: var(--bs-body-bg, #fff);
        }

        .subscription-stat__value {
            color: var(--bs-emphasis-color, #1f2632);
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -.04em;
        }

        .subscription-total {
            color: #ff3364;
            font-size: clamp(2rem, 5vw, 3.4rem);
            font-weight: 800;
            letter-spacing: -.055em;
        }
    </style>
@endpush

@section('content')
    @php
        $amount = (int) data_get($central, 'billing.calculated_amount', 0);
        $status = data_get($central, 'billing.subscription_status');
        $statusLabels = [
            'active' => 'Activa',
            'trialing' => 'En prueba',
            'past_due' => 'Pago pendiente',
            'unpaid' => 'Sin pagar',
            'incomplete' => 'Pago incompleto',
            'canceled' => 'Cancelada',
        ];
    @endphp

    <div class="card subscription-hero mb-6">
        <div class="card-body p-7 p-lg-10">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-6">
                <div>
                    <span class="badge badge-light-danger mb-4">Facturación SaaS</span>
                    <h1 class="text-white fw-bold mb-3">Suscripción de esta instancia</h1>
                    <p class="text-white text-opacity-75 mb-0 mw-650px">
                        El importe se actualiza con las propiedades y la cobranza real de Demo.
                    </p>
                </div>
                <div class="text-lg-end">
                    <div class="text-white text-opacity-75 fw-semibold mb-1">Mensual estimado</div>
                    <div class="subscription-total">${{ number_format($amount / 100, 2) }}</div>
                    <div class="text-white text-opacity-75">MXN</div>
                </div>
            </div>
        </div>
    </div>

    @if ($syncError)
        <div class="alert alert-warning d-flex align-items-center mb-6">
            <i class="bi bi-exclamation-triangle fs-2 me-3"></i>
            <div>{{ $syncError }} Se muestran los conteos locales actuales.</div>
        </div>
    @endif

    <div class="row g-5 mb-6">
        <div class="col-md-4">
            <div class="subscription-stat p-6">
                <div class="text-muted fw-semibold mb-2">Propiedades totales</div>
                <div class="subscription-stat__value">{{ $snapshot['property_count'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="subscription-stat p-6">
                <div class="text-muted fw-semibold mb-2">Sin renta activa · $20 c/u</div>
                <div class="subscription-stat__value">{{ $snapshot['vacant_property_count'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="subscription-stat p-6">
                <div class="text-muted fw-semibold mb-2">Rentadas · $40 c/u</div>
                <div class="subscription-stat__value">{{ $snapshot['rented_property_count'] }}</div>
                <small class="text-muted">Con inquilino y cobranza pendiente</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-7">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                <div>
                    <h2 class="fw-bold mb-2">Pago domiciliado con Stripe</h2>
                    <p class="text-muted mb-1">Estado: <strong>{{ $statusLabels[$status] ?? 'Sin suscripción' }}</strong></p>
                    <p class="text-muted mb-0">Stripe guardará el método de pago y realizará el cobro mensual automáticamente.</p>
                </div>
                <a href="{{ $billingUrl }}" class="btn btn-primary btn-lg" target="_blank" rel="noopener">
                    <i class="bi bi-credit-card me-2"></i> Domiciliar o administrar pago
                </a>
            </div>
        </div>
    </div>
@endsection
