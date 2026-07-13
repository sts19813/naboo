@extends('layouts.app')

@section('title', 'Dashboard | SuWork')

@push('styles')
    <style>
        @media (min-width: 1200px) {
            .executive-dashboard .dashboard-scroll-card {
                display: flex;
                flex-direction: column;
                min-height: 0;
            }

            .executive-dashboard .dashboard-scroll-card .card-body {
                display: flex;
                flex: 1 1 auto;
                flex-direction: column;
                min-height: 0;
            }

            .executive-dashboard .dashboard-properties-scroll {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                padding-right: 0.25rem;
            }
        }

        @media (max-width: 767.98px) {
            .executive-dashboard {
                padding-top: 0 !important;
            }

            .executive-dashboard .dashboard-mobile-shell {
                margin-bottom: 1rem !important;
            }

            .executive-dashboard .dashboard-filter-panel {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.75rem !important;
                width: 100%;
                padding: 0.85rem;
                border: 1px solid #e9edf4;
                border-radius: 8px;
                background: #fff;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
            }

            .executive-dashboard .dashboard-filter-field {
                min-width: 0;
            }

            .executive-dashboard .dashboard-filter-field--wide {
                grid-column: 1 / -1;
            }

            .executive-dashboard .dashboard-filter-panel .form-label {
                margin-bottom: 0.35rem !important;
                color: #8b96b2 !important;
                font-size: 0.62rem !important;
                letter-spacing: 0.04em;
            }

            .executive-dashboard .dashboard-filter-panel .form-select,
            .executive-dashboard .dashboard-filter-panel .form-control {
                width: 100% !important;
                min-height: 42px;
                border-color: #e4e9f2;
                border-radius: 8px;
                color: #1d2638;
                font-size: 0.78rem;
                font-weight: 700;
                box-shadow: none;
            }

            .executive-dashboard .dashboard-filter-submit {
                grid-column: 2;
                align-self: end;
                min-height: 42px;
                border-radius: 8px;
                font-size: 0.76rem;
                font-weight: 800;
                padding-inline: 0.75rem;
            }

            .executive-dashboard .dashboard-kpi-row {
                --bs-gutter-x: 0.75rem;
                --bs-gutter-y: 0.75rem;
                margin-bottom: 1rem !important;
            }

            .executive-dashboard .dashboard-kpi-col {
                width: 50%;
                flex: 0 0 auto;
            }

            .executive-dashboard .dashboard-kpi-col .card,
            .executive-dashboard > .row .card {
                border: 1px solid #edf1f7;
                border-radius: 8px;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
            }

            .executive-dashboard .dashboard-kpi-col .card-body {
                min-height: 128px;
                padding: 1rem !important;
            }

            .executive-dashboard .dashboard-kpi-col .text-gray-600 {
                min-height: 2.15rem;
                color: #8b96b2 !important;
                font-size: 0.68rem !important;
                line-height: 1.35;
            }

            .executive-dashboard .dashboard-kpi-col .fs-2x {
                font-size: 1.08rem !important;
                line-height: 1.2;
                word-break: break-word;
            }

            .executive-dashboard .executive-kpi-icon {
                width: 34px;
                height: 34px;
                border-radius: 8px;
                background: #f6f8fc;
                font-size: 0.95rem;
                flex: 0 0 auto;
            }

            .executive-dashboard .dashboard-content-row {
                --bs-gutter-x: 0;
                --bs-gutter-y: 0.95rem;
                margin-bottom: 0.95rem !important;
            }

            .executive-dashboard .card-header {
                min-height: 0;
                padding: 1rem 1rem 0 !important;
            }

            .executive-dashboard .card-title {
                margin-bottom: 0.2rem;
                font-size: 1rem;
            }

            .executive-dashboard .card-body {
                padding: 1rem !important;
            }

            .executive-dashboard #dashboard_collection_pie {
                min-height: 210px !important;
                margin-top: -0.75rem;
            }

            .executive-dashboard .executive-alert {
                gap: 0.8rem !important;
                padding: 0.85rem;
                border-radius: 8px;
            }

            .executive-dashboard .executive-alert__icon {
                width: 38px;
                height: 38px;
                border-radius: 8px;
            }

            .executive-dashboard #dashboard_properties_card {
                display: flex;
                flex-direction: column;
                max-height: min(560px, 72vh) !important;
                overflow: hidden !important;
            }

            .executive-dashboard #dashboard_properties_card .card-header {
                flex: 0 0 auto;
                background: #fff;
                position: relative;
                z-index: 2;
            }

            .executive-dashboard #dashboard_properties_card .card-body {
                flex: 1 1 auto;
                min-height: 0;
                overflow: hidden;
            }

            .executive-dashboard .dashboard-properties-scroll {
                max-height: 100%;
                overflow: auto;
                overscroll-behavior: contain;
                -webkit-overflow-scrolling: touch;
            }

            .executive-dashboard #dashboard_properties_card .table-responsive {
                border-radius: 8px;
                overflow: visible;
            }

            .executive-dashboard #dashboard_properties_card table {
                min-width: 620px;
            }

            .executive-dashboard #dashboard_properties_card thead th {
                position: sticky;
                top: 0;
                z-index: 1;
                background: #f8fafc;
            }

            .executive-dashboard #dashboard_advisor_commissions_card table {
                min-width: 620px;
            }

            .executive-dashboard .executive-summary-box {
                min-height: 92px;
                padding: 0.85rem !important;
                border-radius: 8px;
            }

            .executive-dashboard .executive-summary-box .fs-2 {
                font-size: 1rem !important;
                line-height: 1.25;
                word-break: break-word;
            }

            .executive-dashboard #dashboard_profitability_chart {
                min-height: 260px !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 executive-dashboard">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8 dashboard-mobile-shell">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Panel ejecutivo</h1>
                <div class="text-muted fs-6">{{ $periodLabel }}</div>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap align-items-end gap-3 dashboard-filter-panel">
                @if ($isAdvisorUser)
                    <div class="dashboard-filter-field dashboard-filter-field--wide">
                        <label class="form-label fs-8 fw-bold text-muted text-uppercase mb-1">Propiedades</label>
                        <select name="property_scope" class="form-select w-200px">
                            <option value="mine" {{ $propertyScope !== 'all' ? 'selected' : '' }}>Mis propiedades</option>
                            <option value="all" {{ $propertyScope === 'all' ? 'selected' : '' }}>Todas las propiedades</option>
                        </select>
                    </div>
                @endif
                <div class="dashboard-filter-field dashboard-filter-field--wide">
                    <label class="form-label fs-8 fw-bold text-muted text-uppercase mb-1">Asesor</label>
                    <select name="advisor_user_id" class="form-select w-225px">
                        <option value="">Todos los asesores</option>
                        @foreach ($availableAdvisors as $advisor)
                            <option value="{{ $advisor->id }}" {{ (string) $selectedAdvisorId === (string) $advisor->id ? 'selected' : '' }}>
                                {{ $advisor->name }}{{ $advisor->email ? ' · ' . $advisor->email : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="dashboard-filter-field">
                    <label class="form-label fs-8 fw-bold text-muted text-uppercase mb-1">Periodo</label>
                    <select name="preset" id="dashboard_period_preset" class="form-select w-200px">
                        <option value="current_month" {{ $selectedPreset === 'current_month' ? 'selected' : '' }}>Este mes</option>
                        <option value="last_3_months" {{ $selectedPreset === 'last_3_months' ? 'selected' : '' }}>Últimos 3 meses</option>
                        <option value="last_6_months" {{ $selectedPreset === 'last_6_months' ? 'selected' : '' }}>Últimos 6 meses</option>
                        <option value="current_year" {{ $selectedPreset === 'current_year' ? 'selected' : '' }}>Este año</option>
                        <option value="custom" {{ $selectedPreset === 'custom' ? 'selected' : '' }}>Rango personalizado</option>
                    </select>
                </div>
                <div class="dashboard-filter-field">
                    <label class="form-label fs-8 fw-bold text-muted text-uppercase mb-1">Desde</label>
                    <input type="date" name="start_date" id="dashboard_period_start" value="{{ $periodStart->toDateString() }}" class="form-control w-175px">
                </div>
                <div class="dashboard-filter-field">
                    <label class="form-label fs-8 fw-bold text-muted text-uppercase mb-1">Hasta</label>
                    <input type="date" name="end_date" id="dashboard_period_end" value="{{ $periodEnd->toDateString() }}" class="form-control w-175px">
                </div>
                <button type="submit" class="btn btn-primary dashboard-filter-submit">Aplicar filtro</button>
            </form>
        </div>

        <div class="row g-5 mb-8 dashboard-kpi-row">
            @foreach ($dashboardKpis as $kpi)
                <div class="col-md-6 col-xl-4 col-xxl-2 dashboard-kpi-col">
                    <div class="card h-100">
                        <div class="card-body p-7">
                            <div class="d-flex align-items-start justify-content-between mb-5">
                                <div class="text-gray-600 fw-semibold fs-7">{{ $kpi['label'] }}</div>
                                <span class="executive-kpi-icon text-{{ $kpi['tone'] }}">
                                    <i class="bi {{ $kpi['icon'] }}"></i>
                                </span>
                            </div>
                            <div class="fw-bold text-dark fs-2x">{{ $kpi['value'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>  

        <div class="row g-5 mb-8 dashboard-content-row">
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header border-0 pt-7">
                        <div>
                            <h3 class="card-title fw-bold text-dark">Resumen de cobranza</h3>
                            <div class="text-muted fs-7">Cobrado, pendiente y vencido del periodo seleccionado</div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row align-items-center">
                            <div class="col-lg-5">
                                <div id="dashboard_collection_pie" class="min-h-250px"></div>
                            </div>
                            <div class="col-lg-7">
                                @foreach ($collectionSummary['segments'] as $segment)
                                    <div class="mb-5">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center gap-2 fw-semibold text-dark">
                                                <span class="executive-dot" style="background: {{ $segment['color'] }}"></span>
                                                {{ $segment['label'] }}
                                            </div>
                                            <span class="text-muted fw-bold">{{ $segment['percent'] }}%</span>
                                        </div>
                                        <div class="progress h-8px bg-light mb-2">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $segment['percent'] }}%; background: {{ $segment['color'] }};"></div>
                                        </div>
                                        <div class="text-gray-700 fw-semibold">{{ '$' . number_format($segment['value'], 2) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header border-0 pt-7">
                        <div>
                            <h3 class="card-title fw-bold text-dark">Alertas importantes</h3>
                            <div class="text-muted fs-7">Contratos por vencer y atrasos de cobranza del periodo</div>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-danger text-danger fs-7 fw-bold">{{ $importantAlerts->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-2 " style="max-height: 330px; overflow-y: auto;">
                        @forelse ($importantAlerts as $alert)
                            <a href="{{ $alert['route'] }}"
                                class="executive-alert executive-alert-{{ $alert['tone'] }} d-flex align-items-center gap-4 mb-4 text-decoration-none">
                                <span class="executive-alert__icon">
                                    <i class="bi {{ $alert['icon'] }}"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark">{{ $alert['title'] }}</div>
                                    <div class="text-gray-700 fw-semibold">{{ $alert['subtitle'] }}</div>
                                    <div class="text-muted fs-7">{{ $alert['detail'] }}</div>
                                </div>
                                <i class="bi bi-chevron-right text-gray-500"></i>
                            </a>
                        @empty
                            <div class="rounded border border-dashed border-success bg-light-success p-8 text-success fw-semibold">
                                No hay alertas importantes para este periodo.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 dashboard-content-row">
            <div class="col-xl-7">
                <div id="dashboard_properties_card" class="card h-100 dashboard-scroll-card" style="max-height: 530px !important;
    overflow-y: scroll;">
                    <div class="card-header border-0 pt-7">
                        <div>
                            <h3 class="card-title fw-bold text-dark">Resumen de propiedades</h3>
                            <div class="text-muted fs-7">Estado de cobranza por propiedad ocupada</div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="dashboard-properties-scroll">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle gs-0 gy-4">
                                    <thead>
                                        <tr class="fw-bold text-muted text-uppercase fs-8">
                                            <th>Propiedad</th>
                                            <th>Asesor</th>
                                            
                                            <th class="text-end">Renta</th>
                                            <th class="text-end">Atrasado</th>
                                            <th class="text-end">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($propertySummaries as $summary)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('properties.show', $summary['property']) }}" class="fw-bold text-dark text-hover-primary">
                                                        {{ $summary['property']->internal_name }}
                                                    </a>
                                                </td>
                                                <td class="text-gray-700">{{ $summary['advisor_name'] }}</td>
                                                
                                                <td class="text-end fw-bold">{{ '$' . number_format($summary['rent_amount'], 2) }}</td>
                                                <td class="text-end {{ $summary['overdue_amount'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                                    {{ $summary['overdue_amount'] > 0 ? '$' . number_format($summary['overdue_amount'], 2) : '-' }}
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge badge-light-{{ $summary['status_tone'] }} text-{{ $summary['status_tone'] }}">
                                                        {{ $summary['status_label'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-10">No hay propiedades ocupadas para mostrar.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div id="dashboard_profitability_card" class="card h-100">
                    <div class="card-header border-0 pt-7">
                        <div>
                            <h3 class="card-title fw-bold text-dark">Rentabilidad general</h3>
                            <div class="text-muted fs-7">Ingresos, gastos y utilidad del periodo seleccionado</div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-3 mb-6">
                            <div class="col-4">
                                <div class="executive-summary-box">
                                    <div class="text-muted fs-8 fw-bold text-uppercase">Ingresos</div>
                                    <div class="fs-2 fw-bold text-dark">{{ '$' . number_format($profitabilitySummary['income_total'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="executive-summary-box">
                                    <div class="text-muted fs-8 fw-bold text-uppercase">Gastos</div>
                                    <div class="fs-2 fw-bold text-danger">{{ '$' . number_format($profitabilitySummary['expense_total'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="executive-summary-box">
                                    <div class="text-muted fs-8 fw-bold text-uppercase">Utilidad</div>
                                    <div class="fs-2 fw-bold {{ $profitabilitySummary['profit_total'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ '$' . number_format($profitabilitySummary['profit_total'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="dashboard_profitability_chart" class="min-h-300px"></div>
                    </div>
                </div>
            </div>

            <div id="dashboard_advisor_commissions_card" class="card mb-8">
            <div class="card-header border-0 pt-7">
                <div>
                    <h3 class="card-title fw-bold text-dark">Comisiones de asesores</h3>
                    <div class="text-muted fs-7">
                        10% de los cobros confirmados de propiedades durante {{ $advisorCommissionMonthLabel }}
                    </div>
                </div>
                <div class="card-toolbar">
                    <span class="badge badge-light-success text-success fs-7 fw-bold">
                        Total: ${{ number_format((float) $advisorCommissions->sum('commission_amount'), 2) }}
                    </span>
                </div>
            </div>
            <div class="card-body pt-2">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle gs-0 gy-4 mb-0">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase fs-8">
                                <th>Asesor responsable</th>
                                <th class="text-end">Propiedades asignadas</th>
                                <th class="text-end">Propiedades cobradas</th>
                                <th class="text-end">Monto cobrado</th>
                                <th class="text-end">Comisión (10%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($advisorCommissions as $commission)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $commission['advisor']->name }}</div>
                                        <div class="text-muted fs-8">{{ $commission['advisor']->email }}</div>
                                    </td>
                                    <td class="text-end text-gray-700 fw-semibold">
                                        {{ number_format($commission['assigned_properties_count']) }}
                                    </td>
                                    <td class="text-end text-gray-700 fw-semibold">
                                        {{ number_format($commission['collected_properties_count']) }}
                                    </td>
                                    <td class="text-end text-gray-700 fw-semibold">
                                        ${{ number_format($commission['collected_amount'], 2) }}
                                    </td>
                                    <td class="text-end text-success fw-bold">
                                        ${{ number_format($commission['commission_amount'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-10">
                                        No hay usuarios con propiedades asignadas como responsables.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('/metronic/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        (() => {
            const collectionElement = document.getElementById('dashboard_collection_pie');
            const profitabilityElement = document.getElementById('dashboard_profitability_chart');
            const propertiesCard = document.getElementById('dashboard_properties_card');
            const profitabilityCard = document.getElementById('dashboard_profitability_card');
            const periodPreset = document.getElementById('dashboard_period_preset');
            const periodStart = document.getElementById('dashboard_period_start');
            const periodEnd = document.getElementById('dashboard_period_end');

            const padDate = (value) => String(value).padStart(2, '0');
            const formatDate = (date) => `${date.getFullYear()}-${padDate(date.getMonth() + 1)}-${padDate(date.getDate())}`;
            const presetRange = (preset) => {
                const today = new Date();

                switch (preset) {
                    case 'last_3_months':
                        return [
                            new Date(today.getFullYear(), today.getMonth() - 2, 1),
                            new Date(today.getFullYear(), today.getMonth() + 1, 0),
                        ];
                    case 'last_6_months':
                        return [
                            new Date(today.getFullYear(), today.getMonth() - 5, 1),
                            new Date(today.getFullYear(), today.getMonth() + 1, 0),
                        ];
                    case 'current_year':
                        return [
                            new Date(today.getFullYear(), 0, 1),
                            new Date(today.getFullYear(), 11, 31),
                        ];
                    case 'current_month':
                        return [
                            new Date(today.getFullYear(), today.getMonth(), 1),
                            new Date(today.getFullYear(), today.getMonth() + 1, 0),
                        ];
                    default:
                        return null;
                }
            };

            if (periodPreset && periodStart && periodEnd) {
                periodPreset.addEventListener('change', () => {
                    const range = presetRange(periodPreset.value);

                    if (!range) {
                        return;
                    }

                    periodStart.value = formatDate(range[0]);
                    periodEnd.value = formatDate(range[1]);
                });

                [periodStart, periodEnd].forEach((input) => {
                    input.addEventListener('change', () => {
                        periodPreset.value = 'custom';
                    });
                });
            }

            const syncPropertyCardHeight = () => {
                if (!propertiesCard || !profitabilityCard) {
                    return;
                }

                if (!window.matchMedia('(min-width: 1200px)').matches) {
                    propertiesCard.style.height = '';
                    return;
                }

                propertiesCard.style.height = '';

                const profitabilityHeight = profitabilityCard.offsetHeight;

                if (profitabilityHeight > 0) {
                    propertiesCard.style.height = `${profitabilityHeight}px`;
                }
            };

            const requestPropertyCardHeightSync = () => {
                window.requestAnimationFrame(syncPropertyCardHeight);
            };

            if (collectionElement && window.ApexCharts) {
                new ApexCharts(collectionElement, {
                    chart: {
                        type: 'donut',
                        height: 280,
                        toolbar: {
                            show: false,
                        },
                    },
                    series: @json($collectionSummary['series']),
                    labels: @json(collect($collectionSummary['segments'])->pluck('label')->all()),
                    colors: @json(collect($collectionSummary['segments'])->pluck('color')->all()),
                    legend: {
                        show: false,
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    stroke: {
                        width: 4,
                        colors: ['#ffffff'],
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '72%',
                            },
                        },
                    },
                }).render();
            }

            if (profitabilityElement && window.ApexCharts) {
                new ApexCharts(profitabilityElement, {
                    series: [{
                        name: 'Ingresos',
                        type: 'area',
                        data: @json($profitabilitySummary['income_series']),
                    }, {
                        name: 'Gastos',
                        type: 'area',
                        data: @json($profitabilitySummary['expense_series']),
                    }, {
                        name: 'Utilidad',
                        type: 'line',
                        data: @json($profitabilitySummary['profit_series']),
                    }],
                    chart: {
                        height: 320,
                        toolbar: {
                            show: false,
                        },
                    },
                    stroke: {
                        curve: 'smooth',
                        width: [3, 3, 3],
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    fill: {
                        type: 'solid',
                        opacity: [0.18, 0.16, 1],
                    },
                    colors: ['#0bb783', '#f1416c', '#3f4254'],
                    labels: @json($profitabilitySummary['labels']),
                    legend: {
                        position: 'top',
                    },
                    xaxis: {
                        categories: @json($profitabilitySummary['labels']),
                    },
                    yaxis: {
                        labels: {
                            formatter: function(value) {
                                return '$' + Number(value).toLocaleString('en-US');
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) {
                                return '$' + Number(value).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                });
                            }
                        }
                    }
                }).render();
            }

            requestPropertyCardHeightSync();
            window.addEventListener('resize', requestPropertyCardHeightSync);

            if (profitabilityCard && window.ResizeObserver) {
                new ResizeObserver(requestPropertyCardHeightSync).observe(profitabilityCard);
            }
        })();
    </script>
@endpush
