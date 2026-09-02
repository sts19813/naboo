<?php

namespace App\Http\Controllers;

use App\Models\CashCut;
use App\Models\ChargePayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashCutController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdministrator($request);

        $payments = $this->pendingPaymentsQuery()
            ->orderBy('registered_by')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $cuts = CashCut::query()
            ->with([
                'advisor:id,name',
                'receivedBy:id,name',
                'items' => fn ($query) => $query->orderBy('id'),
                'items.payment:id,charge_id',
                'items.payment.charge:id,uuid',
            ])
            ->latest('received_at')
            ->latest('id')
            ->paginate(12);

        return view('finance.cash-cuts.index', [
            'payments' => $payments,
            'cuts' => $cuts,
            'pendingTotal' => round((float) $payments->sum('amount'), 2),
            'pendingAdvisorCount' => $payments->pluck('registered_by')->unique()->count(),
            'receivedGrandTotal' => (float) CashCut::query()->sum('grand_total'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdministrator($request);

        $validated = $request->validate([
            'payment_ids' => ['required', 'array', 'min:1'],
            'payment_ids.*' => ['required', 'integer', 'distinct', 'exists:charge_payments,id'],
        ], [
            'payment_ids.required' => 'Selecciona al menos un pago en efectivo para confirmar la recepción.',
            'payment_ids.min' => 'Selecciona al menos un pago en efectivo para confirmar la recepción.',
        ]);

        $paymentIds = collect($validated['payment_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $cut = DB::transaction(function () use ($paymentIds, $request): CashCut {
            $payments = $this->pendingPaymentsQuery()
                ->whereIn('id', $paymentIds)
                ->lockForUpdate()
                ->get();

            if ($payments->count() !== $paymentIds->count()) {
                throw ValidationException::withMessages([
                    'payment_ids' => 'Uno o más pagos ya fueron recibidos o dejaron de ser pagos válidos en efectivo. Actualiza la página e inténtalo nuevamente.',
                ]);
            }

            if ($payments->pluck('registered_by')->unique()->count() > 1) {
                throw ValidationException::withMessages([
                    'payment_ids' => 'Todos los pagos del corte deben pertenecer al mismo asesor.',
                ]);
            }

            /** @var ChargePayment $firstPayment */
            $firstPayment = $payments->first();
            $advisorName = $firstPayment->registeredBy?->name ?? 'Usuario no disponible';
            $receiverName = $request->user()?->name ?? 'Administrador no disponible';

            $rows = $payments->map(function (ChargePayment $payment): array {
                return [
                    'charge_payment_id' => $payment->id,
                    'charge_uuid' => $payment->charge?->uuid,
                    'charge_concept' => $payment->charge?->concept ?: 'Concepto no disponible',
                    'property_name' => $payment->charge?->property?->internal_name,
                    'tenant_name' => $payment->charge?->tenant?->full_name,
                    'amount' => round((float) $payment->amount, 2),
                    'currency' => strtolower($payment->currency ?: 'mxn'),
                    'payment_date' => $payment->payment_date ?? $payment->paid_at?->toDateString(),
                ];
            });

            $cut = CashCut::create([
                'advisor_user_id' => $firstPayment->registered_by,
                'advisor_name' => $advisorName,
                'received_by_user_id' => $request->user()?->id,
                'received_by_name' => $receiverName,
                'payment_count' => $rows->count(),
                'grand_total' => round((float) $rows->sum('amount'), 2),
                'received_at' => now(),
            ]);
            $cut->items()->createMany($rows->all());

            return $cut;
        });

        return redirect()
            ->route('cash-cuts.index', ['tab' => 'historial'])
            ->with('success', "{$cut->display_reference} recibido correctamente con {$cut->payment_count} pago(s) en efectivo.");
    }

    private function pendingPaymentsQuery(): Builder
    {
        return ChargePayment::query()
            ->where('status', ChargePayment::STATUS_SUCCEEDED)
            ->where('payment_method', ChargePayment::METHOD_CASH)
            ->whereDoesntHave('cashCutItem')
            ->with([
                'registeredBy:id,name,email',
                'charge:id,uuid,property_id,tenant_id,concept,type',
                'charge.property:id,internal_name,internal_reference',
                'charge.tenant:id,full_name',
            ]);
    }

    private function ensureAdministrator(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasRole('administrador') && ! $user->hasRole('admin'))) {
            abort(403);
        }
    }
}
