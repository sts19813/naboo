<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Expense;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const ADVISOR_COMMISSION_RATE = 0.10;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'preset' => ['nullable', 'in:current_month,last_3_months,last_6_months,current_year,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'advisor_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'property_scope' => ['nullable', 'in:mine,all'],
        ]);

        $isAdvisorUser = $this->isAdvisorUser($request);
        $propertyScope = $isAdvisorUser && (($validated['property_scope'] ?? 'mine') === 'all') ? 'all' : 'mine';
        $visiblePropertyIds = $isAdvisorUser && $propertyScope === 'mine'
            ? $this->advisorPropertyIds($request)
            : null;
        $availableAdvisors = $this->availableAdvisors();
        $requestedAdvisorId = isset($validated['advisor_user_id']) ? (int) $validated['advisor_user_id'] : null;
        $selectedAdvisorId = $requestedAdvisorId && $availableAdvisors->contains('id', $requestedAdvisorId)
            ? $requestedAdvisorId
            : null;
        $filteredPropertyIds = $this->intersectPropertyIds(
            $visiblePropertyIds,
            $selectedAdvisorId ? $this->advisorFilterPropertyIds($selectedAdvisorId) : null,
        );

        $dashboardPeriod = $this->resolveDashboardPeriod($validated);
        $periodStart = $dashboardPeriod['start'];
        $periodEnd = $dashboardPeriod['end'];
        $selectedMonth = $periodStart->copy()->startOfMonth();
        $referenceDate = $this->referenceDateForPeriod($periodStart, $periodEnd);

        $kpis = $this->buildKpis($periodStart, $periodEnd, $referenceDate, $filteredPropertyIds);
        $collectionSummary = $this->buildCollectionSummary($periodStart, $periodEnd, $referenceDate, $filteredPropertyIds);
        $alerts = $this->buildImportantAlerts($periodStart, $periodEnd, $referenceDate, $filteredPropertyIds);
        $propertySummaries = $this->buildPropertySummaries($periodStart, $periodEnd, $referenceDate, $filteredPropertyIds);
        $profitability = $this->buildProfitabilitySummary($periodStart, $periodEnd, $filteredPropertyIds);
        $advisorCommissions = $this->buildCurrentMonthAdvisorCommissions();

        return view('dashboard', [
            'selectedMonth' => $selectedMonth,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'periodLabel' => $this->periodLabel($periodStart, $periodEnd),
            'selectedPreset' => $dashboardPeriod['preset'],
            'monthOptions' => $this->monthOptions($selectedMonth, 12),
            'isAdvisorUser' => $isAdvisorUser,
            'propertyScope' => $propertyScope,
            'selectedAdvisorId' => $selectedAdvisorId,
            'availableAdvisors' => $availableAdvisors,
            'dashboardKpis' => $kpis,
            'collectionSummary' => $collectionSummary,
            'importantAlerts' => $alerts,
            'propertySummaries' => $propertySummaries,
            'profitabilitySummary' => $profitability,
            'advisorCommissions' => $advisorCommissions,
            'advisorCommissionMonthLabel' => ucfirst(now()->translatedFormat('F Y')),
        ]);
    }

    private function buildCurrentMonthAdvisorCommissions(): Collection
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $paymentTotals = ChargePayment::query()
            ->selectRaw('properties.advisor_user_id as advisor_id')
            ->selectRaw('SUM(charge_payments.amount) as collected_amount')
            ->selectRaw('COUNT(DISTINCT charges.property_id) as collected_properties_count')
            ->join('charges', 'charges.id', '=', 'charge_payments.charge_id')
            ->join('properties', 'properties.id', '=', 'charges.property_id')
            ->whereNotNull('properties.advisor_user_id')
            ->where('charge_payments.status', ChargePayment::STATUS_SUCCEEDED)
            ->whereBetween('charge_payments.paid_at', [
                $monthStart->copy()->startOfDay(),
                $monthEnd->copy()->endOfDay(),
            ])
            ->groupBy('properties.advisor_user_id')
            ->get()
            ->keyBy(fn ($total) => (int) $total->advisor_id);

        return User::query()
            ->whereHas('assignedProperties')
            ->withCount('assignedProperties')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function (User $advisor) use ($paymentTotals): array {
                $totals = $paymentTotals->get($advisor->id);
                $collectedAmount = round((float) ($totals?->collected_amount ?? 0), 2);

                return [
                    'advisor' => $advisor,
                    'assigned_properties_count' => (int) $advisor->assigned_properties_count,
                    'collected_properties_count' => (int) ($totals?->collected_properties_count ?? 0),
                    'collected_amount' => $collectedAmount,
                    'commission_amount' => round($collectedAmount * self::ADVISOR_COMMISSION_RATE, 2),
                ];
            });
    }

    private function buildKpis(Carbon $periodStart, Carbon $periodEnd, Carbon $referenceDate, ?Collection $visiblePropertyIds): array
    {
        $chargesForMonth = Charge::query()
            ->where('status', '!=', Charge::STATUS_CANCELED)
            ->whereBetween('due_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
        $this->applyPropertyIdFilter($chargesForMonth, $visiblePropertyIds);

        $expectedIncome = (float) (clone $chargesForMonth)->sum('amount');
        $collectionTotals = $this->collectionTotals($periodStart, $periodEnd, $referenceDate, $visiblePropertyIds);

        $propertiesQuery = Property::query();
        $this->applyPropertyPrimaryKeyFilter($propertiesQuery, $visiblePropertyIds);

        $occupiedPropertiesQuery = Property::query()->whereIn('status', $this->occupiedStatuses());
        $this->applyPropertyPrimaryKeyFilter($occupiedPropertiesQuery, $visiblePropertyIds);

        return [
            [
                'label' => 'Total de propiedades',
                'value' => number_format((int) $propertiesQuery->count()),
                'icon' => 'bi-house-door',
                'tone' => 'primary',
            ],
            [
                'label' => 'Propiedades ocupadas',
                'value' => number_format((int) $occupiedPropertiesQuery->count()),
                'icon' => 'bi-buildings',
                'tone' => 'success',
            ],
            [
                'label' => 'Ingreso esperado del periodo',
                'value' => $this->money($expectedIncome),
                'icon' => 'bi-graph-up-arrow',
                'tone' => 'info',
            ],
            [
                'label' => 'Cobrado del periodo',
                'value' => $this->money($collectionTotals['paid']),
                'icon' => 'bi-check2-circle',
                'tone' => 'success',
            ],
            [
                'label' => 'Pendiente por cobrar',
                'value' => $this->money($collectionTotals['pending']),
                'icon' => 'bi-hourglass-split',
                'tone' => 'warning',
            ],
            [
                'label' => 'Cantidad vencida del periodo',
                'value' => $this->money($collectionTotals['overdue']),
                'icon' => 'bi-exclamation-octagon',
                'tone' => 'danger',
            ],
        ];
    }

    private function buildCollectionSummary(Carbon $periodStart, Carbon $periodEnd, Carbon $referenceDate, ?Collection $visiblePropertyIds): array
    {
        $totals = $this->collectionTotals($periodStart, $periodEnd, $referenceDate, $visiblePropertyIds);

        $paid = $totals['paid'];
        $pending = $totals['pending'];
        $overdue = $totals['overdue'];
        $total = max(1, $paid + $pending + $overdue);

        return [
            'total' => $total,
            'series' => [round($paid, 2), round($pending, 2), round($overdue, 2)],
            'segments' => [
                [
                    'label' => 'Cobrado',
                    'value' => round($paid, 2),
                    'percent' => round(($paid / $total) * 100),
                    'color' => '#0bb783',
                ],
                [
                    'label' => 'Pendiente',
                    'value' => round($pending, 2),
                    'percent' => round(($pending / $total) * 100),
                    'color' => '#f59e0b',
                ],
                [
                    'label' => 'Vencido',
                    'value' => round($overdue, 2),
                    'percent' => round(($overdue / $total) * 100),
                    'color' => '#f1416c',
                ],
            ],
        ];
    }

    private function collectionTotals(Carbon $periodStart, Carbon $periodEnd, Carbon $referenceDate, ?Collection $visiblePropertyIds): array
    {
        $charges = Charge::query()
            ->where('status', '!=', Charge::STATUS_CANCELED)
            ->whereBetween('due_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
        $this->applyPropertyIdFilter($charges, $visiblePropertyIds);
        $charges = $charges->get();

        $paymentsQuery = ChargePayment::query()
            ->where('status', ChargePayment::STATUS_SUCCEEDED)
            ->whereBetween('paid_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()]);
        $this->applyPaymentPropertyFilter($paymentsQuery, $visiblePropertyIds);

        $paid = (float) $paymentsQuery->sum('amount');
        $pending = 0.0;
        $overdue = 0.0;

        foreach ($charges as $charge) {
            $outstanding = max(0, (float) $charge->amount - (float) $charge->paid_amount);

            if ($outstanding <= 0) {
                continue;
            }

            if ($this->isChargeOverdueForReference($charge, $referenceDate)) {
                $overdue += $outstanding;
            } else {
                $pending += $outstanding;
            }
        }

        return [
            'paid' => round($paid, 2),
            'pending' => round($pending, 2),
            'overdue' => round($overdue, 2),
        ];
    }

    private function buildImportantAlerts(Carbon $periodStart, Carbon $periodEnd, Carbon $referenceDate, ?Collection $visiblePropertyIds): Collection
    {
        $contractAlerts = Property::query()
            ->with(['tenant:id,full_name'])
            ->whereBetween('contract_expires_at', [$periodStart->toDateString(), $periodEnd->toDateString()]);
        $this->applyPropertyPrimaryKeyFilter($contractAlerts, $visiblePropertyIds);
        $contractAlerts = $contractAlerts
            ->orderBy('contract_expires_at')
            ->get()
            ->map(function (Property $property): array {
                return [
                    'tone' => 'warning',
                    'icon' => 'bi-file-earmark-text',
                    'title' => $property->internal_name,
                    'subtitle' => 'Contrato vence '.optional($property->contract_expires_at)->translatedFormat('d M Y'),
                    'detail' => $property->tenant?->full_name ?: 'Sin inquilino asignado',
                    'route' => route('properties.show', $property),
                ];
            });

        $overdueAlerts = Charge::query()
            ->with(['property:id,uuid,internal_name', 'tenant:id,full_name'])
            ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
            ->whereBetween('due_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
        $this->applyPropertyIdFilter($overdueAlerts, $visiblePropertyIds);
        $overdueAlerts = $overdueAlerts
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Charge $charge) => $this->isChargeOverdueForReference($charge, $referenceDate))
            ->map(function (Charge $charge): array {
                return [
                    'tone' => 'danger',
                    'icon' => 'bi-exclamation-octagon',
                    'title' => $charge->property?->internal_name ?: 'Propiedad',
                    'subtitle' => 'Atraso de cobranza por '.$this->money($charge->outstanding_amount),
                    'detail' => ($charge->tenant?->full_name ?: 'Sin inquilino').' · vence '.optional($charge->due_date)->translatedFormat('d M Y'),
                    'route' => route('charges.show', $charge),
                ];
            });

        return $contractAlerts
            ->concat($overdueAlerts)
            ->take(8)
            ->values();
    }

    private function buildPropertySummaries(Carbon $periodStart, Carbon $periodEnd, Carbon $referenceDate, ?Collection $visiblePropertyIds): Collection
    {
        $query = Property::query()
            ->with([
                'tenant:id,full_name',
                'advisor:id,name',
                'advisors:id,name',
                'charges' => fn ($query) => $query
                    ->where('status', '!=', Charge::STATUS_CANCELED)
                    ->whereBetween('due_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->orderBy('due_date'),
            ])
            ->whereIn('status', $this->occupiedStatuses())
            ->orderBy('internal_name');
        $this->applyPropertyPrimaryKeyFilter($query, $visiblePropertyIds);

        return $query
            ->get()
            ->map(function (Property $property) use ($referenceDate): array {
                $overdueAmount = 0.0;
                $pendingAmount = 0.0;

                foreach ($property->charges as $charge) {
                    $outstanding = max(0, (float) $charge->amount - (float) $charge->paid_amount);
                    if ($outstanding <= 0) {
                        continue;
                    }

                    if ($this->isChargeOverdueForReference($charge, $referenceDate)) {
                        $overdueAmount += $outstanding;
                    } else {
                        $pendingAmount += $outstanding;
                    }
                }

                [$statusLabel, $tone] = match (true) {
                    $overdueAmount > 0 => ['Atrasado', 'danger'],
                    $pendingAmount > 0 => ['Pendiente', 'warning'],
                    default => ['Al corriente', 'success'],
                };

                return [
                    'property' => $property,
                    'tenant_name' => $property->tenant?->full_name ?: $property->current_tenant_name ?: '-',
                    'advisor_name' => $property->advisors->pluck('name')->implode(', ') ?: ($property->advisor?->name ?: 'Sin asesor'),
                    'rent_amount' => (float) ($property->monthly_rent_price ?? 0),
                    'status_label' => $statusLabel,
                    'status_tone' => $tone,
                    'overdue_amount' => round($overdueAmount, 2),
                    'pending_amount' => round($pendingAmount, 2),
                ];
            });
    }

    private function buildProfitabilitySummary(Carbon $periodStart, Carbon $periodEnd, ?Collection $visiblePropertyIds): array
    {
        $months = collect();
        $cursor = $periodStart->copy()->startOfMonth();

        while ($cursor->lte($periodEnd)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        $showYearInLabel = $periodStart->year !== $periodEnd->year || $months->count() > 12;

        $series = $months->map(function (Carbon $month) use ($periodStart, $periodEnd, $showYearInLabel, $visiblePropertyIds): array {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            if ($monthStart->lt($periodStart)) {
                $monthStart = $periodStart->copy();
            }

            if ($monthEnd->gt($periodEnd)) {
                $monthEnd = $periodEnd->copy();
            }

            $incomeQuery = ChargePayment::query()
                ->where('status', ChargePayment::STATUS_SUCCEEDED)
                ->whereBetween('paid_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()]);
            $this->applyPaymentPropertyFilter($incomeQuery, $visiblePropertyIds);
            $income = (float) $incomeQuery->sum('amount');

            $expensesQuery = Expense::query()
                ->includedInTotals()
                ->where(function ($query) use ($monthStart, $monthEnd): void {
                    $query->whereBetween('paid_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
                        ->orWhere(function ($nested) use ($monthStart, $monthEnd): void {
                            $nested->whereNull('paid_at')
                                ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
                        });
                });
            $this->applyPropertyIdFilter($expensesQuery, $visiblePropertyIds);
            $expenses = (float) $expensesQuery->sum('amount');

            return [
                'label' => ucfirst($month->translatedFormat($showYearInLabel ? 'M Y' : 'M')),
                'income' => round($income, 2),
                'expenses' => round($expenses, 2),
                'profit' => round($income - $expenses, 2),
            ];
        });

        return [
            'labels' => $series->pluck('label')->all(),
            'income_series' => $series->pluck('income')->all(),
            'expense_series' => $series->pluck('expenses')->all(),
            'profit_series' => $series->pluck('profit')->all(),
            'income_total' => (float) $series->sum('income'),
            'expense_total' => (float) $series->sum('expenses'),
            'profit_total' => (float) $series->sum('profit'),
        ];
    }

    private function monthOptions(Carbon $selectedMonth, int $count): Collection
    {
        return collect(range(0, $count - 1))
            ->map(fn ($offset) => $selectedMonth->copy()->subMonths($offset))
            ->map(fn (Carbon $month) => [
                'value' => $month->format('Y-m'),
                'label' => ucfirst($month->translatedFormat('F Y')),
            ]);
    }

    private function resolveDashboardPeriod(array $validated): array
    {
        $preset = $validated['preset'] ?? null;

        if ($preset && $preset !== 'custom') {
            return $this->periodForPreset($preset);
        }

        if ($preset === 'custom' || ! empty($validated['start_date']) || ! empty($validated['end_date'])) {
            $start = ! empty($validated['start_date'])
                ? Carbon::parse($validated['start_date'])->startOfDay()
                : (! empty($validated['end_date'])
                    ? Carbon::parse($validated['end_date'])->startOfMonth()
                    : now()->startOfMonth());

            $end = ! empty($validated['end_date'])
                ? Carbon::parse($validated['end_date'])->endOfDay()
                : $start->copy()->endOfMonth();

            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [
                'start' => $start,
                'end' => $end,
                'preset' => 'custom',
            ];
        }

        if (! empty($validated['month'])) {
            $month = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();

            return [
                'start' => $month->copy()->startOfMonth(),
                'end' => $month->copy()->endOfMonth(),
                'preset' => 'custom',
            ];
        }

        return $this->periodForPreset('current_month');
    }

    private function periodForPreset(string $preset): array
    {
        $today = now();

        [$start, $end] = match ($preset) {
            'last_3_months' => [
                $today->copy()->startOfMonth()->subMonths(2),
                $today->copy()->endOfMonth(),
            ],
            'last_6_months' => [
                $today->copy()->startOfMonth()->subMonths(5),
                $today->copy()->endOfMonth(),
            ],
            'current_year' => [
                $today->copy()->startOfYear(),
                $today->copy()->endOfYear(),
            ],
            default => [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
            ],
        };

        return [
            'start' => $start->copy()->startOfDay(),
            'end' => $end->copy()->endOfDay(),
            'preset' => $preset,
        ];
    }

    private function occupiedStatuses(): array
    {
        return [Property::STATUS_OCCUPIED, Property::STATUS_RENTED];
    }

    private function isChargeOverdueForReference(Charge $charge, Carbon $referenceDate): bool
    {
        if (! in_array($charge->status, [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL], true)) {
            return false;
        }

        return $charge->due_date?->lt($referenceDate->copy()->startOfDay()) ?? false;
    }

    private function referenceDateForPeriod(Carbon $periodStart, Carbon $periodEnd): Carbon
    {
        $today = now()->startOfDay();

        if ($periodEnd->copy()->endOfDay()->lt($today)) {
            return $periodEnd->copy()->addDay()->startOfDay();
        }

        if ($periodStart->copy()->startOfDay()->lte($today) && $periodEnd->copy()->endOfDay()->gte($today)) {
            return $today;
        }

        return $periodStart->copy()->startOfDay();
    }

    private function periodLabel(Carbon $periodStart, Carbon $periodEnd): string
    {
        $startsOnYear = $periodStart->isSameDay($periodStart->copy()->startOfYear());
        $endsOnYear = $periodEnd->isSameDay($periodEnd->copy()->endOfYear());

        if ($startsOnYear && $endsOnYear && $periodStart->year === $periodEnd->year) {
            return (string) $periodStart->year;
        }

        $startsOnMonth = $periodStart->isSameDay($periodStart->copy()->startOfMonth());
        $endsOnMonth = $periodEnd->isSameDay($periodEnd->copy()->endOfMonth());

        if ($startsOnMonth && $endsOnMonth) {
            if ($periodStart->isSameMonth($periodEnd)) {
                return ucfirst($periodStart->translatedFormat('F Y'));
            }

            return ucfirst($periodStart->translatedFormat('F Y'))
                .' - '
                .ucfirst($periodEnd->translatedFormat('F Y'));
        }

        return $periodStart->translatedFormat('d M Y').' - '.$periodEnd->translatedFormat('d M Y');
    }

    private function money(float $amount): string
    {
        return '$'.number_format($amount, 2);
    }

    private function isAdvisorUser(Request $request): bool
    {
        $user = $request->user();

        return (bool) $user
            && ! $user->hasRole('administrador')
            && ! $user->hasRole('admin')
            && ($user->hasRole('asesores') || $user->can('propiedades.ver_propias'));
    }

    private function advisorPropertyIds(Request $request): Collection
    {
        $user = $request->user();

        if (! $user) {
            return collect();
        }

        return $user->advisorProperties()
            ->select('properties.id')
            ->pluck('properties.id')
            ->merge(Property::query()->where('advisor_user_id', $user->id)->pluck('id'))
            ->unique()
            ->values();
    }

    private function advisorFilterPropertyIds(int $advisorId): Collection
    {
        return Property::query()
            ->where(function ($query) use ($advisorId): void {
                $query->where('advisor_user_id', $advisorId)
                    ->orWhereHas('advisors', fn ($advisorQuery) => $advisorQuery->whereKey($advisorId));
            })
            ->pluck('id')
            ->unique()
            ->values();
    }

    private function intersectPropertyIds(?Collection $basePropertyIds, ?Collection $filterPropertyIds): ?Collection
    {
        if ($filterPropertyIds === null) {
            return $basePropertyIds;
        }

        if ($basePropertyIds === null) {
            return $filterPropertyIds;
        }

        return $basePropertyIds
            ->intersect($filterPropertyIds)
            ->values();
    }

    private function availableAdvisors(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                'administrador',
                'admin',
                'asesores',
                'asesor',
            ]))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function applyPropertyIdFilter($query, ?Collection $visiblePropertyIds): void
    {
        if ($visiblePropertyIds !== null) {
            $query->whereIn('property_id', $visiblePropertyIds->all());
        }
    }

    private function applyPropertyPrimaryKeyFilter($query, ?Collection $visiblePropertyIds): void
    {
        if ($visiblePropertyIds !== null) {
            $query->whereIn('id', $visiblePropertyIds->all());
        }
    }

    private function applyPaymentPropertyFilter($query, ?Collection $visiblePropertyIds): void
    {
        if ($visiblePropertyIds !== null) {
            $query->whereHas('charge', fn ($chargeQuery) => $chargeQuery->whereIn('property_id', $visiblePropertyIds->all()));
        }
    }
}
