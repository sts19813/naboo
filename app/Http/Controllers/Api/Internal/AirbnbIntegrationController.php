<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\AirbnbFinancialEvent;
use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AirbnbIntegrationController extends Controller
{
    public function availableProperties(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'include_claimed' => ['nullable', 'boolean'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $properties = Property::query()
            ->with(['type:id,name', 'zone:id,name'])
            ->whereNull('archived_at')
            ->whereNotNull('airbnb_enabled_at')
            ->when(
                ! $request->boolean('include_claimed'),
                fn (Builder $query) => $query->whereNull('airbnb_managed_at'),
            )
            ->whereNotIn('status', [Property::STATUS_OCCUPIED, Property::STATUS_RENTED])
            ->whereNull('tenant_id')
            ->whereDoesntHave('charges', fn (Builder $query) => $query
                ->whereIn('status', $this->openChargeStatuses())
                ->orWhereHas('payments', fn (Builder $paymentQuery) => $paymentQuery->whereIn('status', $this->openPaymentStatuses())))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $like = "%{$search}%";

                    $inner
                        ->where('internal_name', 'like', $like)
                        ->orWhere('internal_reference', 'like', $like)
                        ->orWhere('full_address', 'like', $like)
                        ->orWhere('uuid', 'like', $like);
                });
            })
            ->orderBy('internal_name')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $properties->map(fn (Property $property): array => $this->propertyPayload($property))->values(),
        ]);
    }

    public function claimProperty(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'external_property_id' => ['required', 'string', 'max:120'],
            'external_property_code' => ['nullable', 'string', 'max:120'],
            'external_property_name' => ['nullable', 'string', 'max:190'],
        ]);

        $property = DB::transaction(function () use ($property, $validated): Property {
            $locked = Property::query()->whereKey($property->id)->lockForUpdate()->firstOrFail();

            if (
                filled($locked->airbnb_external_property_id)
                && $locked->airbnb_external_property_id !== $validated['external_property_id']
            ) {
                abort(409, 'La propiedad ya está reclamada por otra propiedad de Airbnb.');
            }

            if (filled($locked->airbnb_external_property_id)) {
                Property::withoutEvents(function () use ($locked, $validated): void {
                    $locked->forceFill([
                        'airbnb_enabled_at' => $locked->airbnb_enabled_at ?: now(),
                        'airbnb_external_property_code' => $validated['external_property_code'] ?? $locked->airbnb_external_property_code,
                        'airbnb_external_property_name' => $validated['external_property_name'] ?? $locked->airbnb_external_property_name,
                    ])->save();
                });

                return $locked;
            }

            if (! $locked->airbnb_enabled_at) {
                abort(422, 'La propiedad no está habilitada para administrarse por Airbnb.');
            }

            if ($locked->tenant_id) {
                abort(422, 'La propiedad tiene un inquilino asignado y no puede reclamarse para Airbnb.');
            }

            if (in_array($locked->status, [Property::STATUS_OCCUPIED, Property::STATUS_RENTED], true)) {
                abort(422, 'La propiedad está ocupada o rentada y no puede reclamarse para Airbnb.');
            }

            $hasOpenPayments = $locked->charges()
                ->where(function (Builder $query): void {
                    $query
                        ->whereIn('status', $this->openChargeStatuses())
                        ->orWhereHas('payments', fn (Builder $paymentQuery) => $paymentQuery->whereIn('status', $this->openPaymentStatuses()));
                })
                ->exists();

            if ($hasOpenPayments) {
                abort(422, 'La propiedad tiene cobranza abierta y no puede reclamarse para Airbnb.');
            }

            Property::withoutEvents(function () use ($locked, $validated): void {
                $locked->forceFill([
                    'status' => Property::STATUS_BLOCKED,
                    'airbnb_enabled_at' => $locked->airbnb_enabled_at ?: now(),
                    'airbnb_managed_at' => now(),
                    'airbnb_external_property_id' => $validated['external_property_id'],
                    'airbnb_external_property_code' => $validated['external_property_code'] ?? null,
                    'airbnb_external_property_name' => $validated['external_property_name'] ?? null,
                ])->save();
            });

            return $locked;
        });

        return response()->json([
            'data' => $this->propertyPayload($property->refresh()),
        ]);
    }

    public function releaseProperty(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'external_property_id' => ['required', 'string', 'max:120'],
        ]);

        $property = DB::transaction(function () use ($property, $validated): Property {
            $locked = Property::query()->whereKey($property->id)->lockForUpdate()->firstOrFail();

            if (! filled($locked->airbnb_external_property_id)) {
                return $locked;
            }

            if ($locked->airbnb_external_property_id !== $validated['external_property_id']) {
                abort(409, 'La propiedad está reclamada por otra propiedad de Airbnb.');
            }

            Property::withoutEvents(function () use ($locked): void {
                $locked->forceFill([
                    'status' => $locked->status === Property::STATUS_BLOCKED
                        ? ($locked->airbnb_enabled_at ? Property::STATUS_BLOCKED : Property::STATUS_AVAILABLE)
                        : $locked->status,
                    'airbnb_managed_at' => null,
                    'airbnb_external_property_id' => null,
                    'airbnb_external_property_code' => null,
                    'airbnb_external_property_name' => null,
                ])->save();
            });

            return $locked;
        });

        return response()->json([
            'data' => $this->propertyPayload($property->refresh()),
        ]);
    }

    public function storeFinancialEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_uuid' => ['required', 'uuid', 'exists:properties,uuid'],
            'external_event_id' => ['required', 'string', 'max:120'],
            'event_type' => ['required', 'string', 'max:60'],
            'concept' => ['required', 'string', 'max:190'],
            'customer_name' => ['required', 'string', 'max:190'],
            'payment_method' => ['required', 'string', 'in:'.implode(',', array_keys(ChargePayment::METHOD_LABELS))],
            'external_reservation_id' => ['nullable', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'paid_at' => ['nullable', 'date'],
            'occurred_on' => ['nullable', 'date'],
            'payload' => ['nullable', 'array'],
        ]);

        [$event, $created] = DB::transaction(function () use ($validated): array {
            $property = Property::query()
                ->where('uuid', $validated['property_uuid'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $property->airbnb_managed_at || blank($property->airbnb_external_property_id)) {
                abort(422, 'La propiedad no está enlazada con Airbnb SU.');
            }

            $existing = AirbnbFinancialEvent::query()
                ->where('external_event_id', $validated['external_event_id'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->property_id !== $property->id) {
                    abort(409, 'El external_event_id ya fue usado para otra propiedad.');
                }

                return [$existing, false];
            }

            $paidAt = Carbon::parse($validated['paid_at'] ?? $validated['occurred_on'] ?? now());
            $amount = (float) $validated['amount'];
            $currency = strtolower((string) ($validated['currency'] ?? 'MXN'));

            $charge = Charge::query()->create([
                'property_id' => $property->id,
                'tenant_id' => null,
                'customer_name' => $validated['customer_name'],
                'type' => Charge::TYPE_OTHER,
                'due_date' => $paidAt->toDateString(),
                'amount' => $amount,
                'paid_amount' => $amount,
                'period_month' => (int) $paidAt->month,
                'period_year' => (int) $paidAt->year,
                'concept' => $validated['concept'],
                'notes' => 'Cobranza recibida automáticamente desde Airbnb SU.',
                'status' => Charge::STATUS_PAID,
                'source' => Charge::SOURCE_AIRBNB_SU,
                'paid_at' => $paidAt,
            ]);

            $payment = $charge->payments()->create([
                'amount' => $amount,
                'currency' => $currency,
                'status' => ChargePayment::STATUS_SUCCEEDED,
                'source' => ChargePayment::SOURCE_AIRBNB_SU,
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['external_event_id'],
                'payment_date' => $paidAt->toDateString(),
                'paid_at' => $paidAt,
                'payload' => $validated['payload'] ?? null,
                'notes' => 'Pago confirmado por Airbnb SU.',
            ]);

            $event = AirbnbFinancialEvent::query()->create([
                'property_id' => $property->id,
                'external_event_id' => $validated['external_event_id'],
                'event_type' => $validated['event_type'],
                'concept' => $validated['concept'],
                'customer_name' => $validated['customer_name'],
                'payment_method' => $validated['payment_method'],
                'external_reservation_id' => $validated['external_reservation_id'] ?? null,
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'occurred_on' => $paidAt->toDateString(),
                'payload' => $validated['payload'] ?? null,
                'charge_id' => $charge->id,
                'charge_payment_id' => $payment->id,
            ]);

            return [$event, true];
        });

        $event->loadMissing(['charge', 'chargePayment']);

        return response()->json([
            'data' => [
                'id' => $event->id,
                'external_event_id' => $event->external_event_id,
                'created' => $created,
                'status' => 'paid',
                'charge' => [
                    'uuid' => $event->charge?->uuid,
                    'status' => $event->charge?->status,
                    'source' => $event->charge?->source,
                ],
                'payment' => [
                    'id' => $event->chargePayment?->id,
                    'status' => $event->chargePayment?->status,
                ],
            ],
        ], $created ? 201 : 200);
    }

    private function propertyPayload(Property $property): array
    {
        return [
            'uuid' => $property->uuid,
            'internal_name' => $property->internal_name,
            'internal_reference' => $property->internal_reference,
            'full_address' => $property->full_address,
            'status' => $property->status,
            'status_label' => $property->status_label,
            'type' => $property->type?->name,
            'zone' => $property->zone?->name ?: $property->zone_text,
            'monthly_rent_price' => $property->monthly_rent_price,
            'airbnb_enabled_at' => $property->airbnb_enabled_at?->toISOString(),
            'airbnb_managed_at' => $property->airbnb_managed_at?->toISOString(),
            'airbnb_external_property_id' => $property->airbnb_external_property_id,
            'airbnb_external_property_code' => $property->airbnb_external_property_code,
            'airbnb_external_property_name' => $property->airbnb_external_property_name,
        ];
    }

    /** @return array<int, string> */
    private function openChargeStatuses(): array
    {
        return [
            Charge::STATUS_PENDING,
            Charge::STATUS_PARTIAL,
            Charge::STATUS_IN_VALIDATION,
        ];
    }

    /** @return array<int, string> */
    private function openPaymentStatuses(): array
    {
        return [
            ChargePayment::STATUS_PENDING,
            ChargePayment::STATUS_PENDING_VALIDATION,
        ];
    }
}
