<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Property;
use App\Services\PropertyMapLocationResolver;
use App\Support\PropertyVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PropertyMapController extends Controller
{
    public function __construct(private readonly PropertyVisibility $propertyVisibility)
    {
    }

    public function index(Request $request): View
    {
        $this->ensureCanAccessMap($request);
        $properties = $this->mapPropertiesQuery($request)
            ->whereNotNull('map_url')
            ->where('map_url', '<>', '')
            ->whereNotNull('map_latitude')
            ->whereNotNull('map_longitude')
            ->orderBy('internal_name')
            ->get();

        $markers = $properties->map(fn (Property $property) => $this->markerFor($property))->values();

        $statusCounts = $markers
            ->groupBy('status')
            ->map(fn ($items) => $items->count())
            ->all();

        return view('properties.map', [
            'markers' => $markers,
            'totalWithMapUrl' => $this->propertyVisibility->scopeVisibleToUser(Property::query(), $request->user())
                ->whereNull('archived_at')
                ->whereNotNull('map_url')
                ->where('map_url', '<>', '')
                ->count(),
            'pendingCoordinatesCount' => $this->pendingCoordinatesQuery($request)->count(),
            'statusCounts' => $statusCounts,
        ]);
    }

    public function syncPending(Request $request, PropertyMapLocationResolver $resolver): JsonResponse
    {
        $this->ensureCanAccessMap($request);
        $pending = $this->pendingCoordinatesQuery($request)
            ->orderBy('id')
            ->limit(3)
            ->get();

        $resolvedIds = [];

        foreach ($pending as $property) {
            $location = $resolver->resolve((string) $property->map_url);

            $updated = Property::query()->whereKey($property->id)->where('map_url', $property->map_url)->whereNull('map_coordinates_checked_at')->update([
                'map_latitude' => $location['latitude'] ?? null,
                'map_longitude' => $location['longitude'] ?? null,
                'map_resolved_url' => $location['resolved_url'] ?? null,
                'map_coordinates_resolved_at' => $location ? now() : null,
                'map_coordinates_checked_at' => now(),
            ]);

            if ($location && $updated) {
                $resolvedIds[] = $property->id;
            }
        }

        $markers = $this->mapPropertiesQuery($request)
            ->whereKey($resolvedIds)
            ->get()
            ->map(fn (Property $property) => $this->markerFor($property))
            ->values();

        return response()->json([
            'markers' => $markers,
            'processed' => $pending->count(),
            'resolved' => count($resolvedIds),
            'remaining' => $this->pendingCoordinatesQuery($request)->count(),
        ]);
    }

    private function mapPropertiesQuery(Request $request)
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return $this->propertyVisibility->scopeVisibleToUser(Property::query(), $request->user())
            ->whereNull('archived_at')
            ->with([
                'type',
                'zone',
                'tenant',
                'owners:id,name',
                'advisor:id,name',
                'charges' => fn ($query) => $query
                    ->where('status', '!=', Charge::STATUS_CANCELED)
                    ->where(function ($chargeQuery) use ($monthStart, $monthEnd): void {
                        $chargeQuery
                            ->whereIn('status', [
                                Charge::STATUS_PENDING,
                                Charge::STATUS_PARTIAL,
                                Charge::STATUS_IN_VALIDATION,
                            ])
                            ->orWhereHas('payments', fn ($paymentQuery) => $paymentQuery
                                ->where(function ($statusQuery) use ($monthStart, $monthEnd): void {
                                    $statusQuery
                                        ->where('status', ChargePayment::STATUS_PENDING_VALIDATION)
                                        ->orWhere(function ($paidQuery) use ($monthStart, $monthEnd): void {
                                            $paidQuery
                                                ->where('status', ChargePayment::STATUS_SUCCEEDED)
                                                ->whereBetween('paid_at', [$monthStart, $monthEnd]);
                                        });
                                }));
                    })
                    ->with(['payments' => fn ($query) => $query
                        ->where(function ($paymentQuery) use ($monthStart, $monthEnd): void {
                            $paymentQuery
                                ->where('status', ChargePayment::STATUS_PENDING_VALIDATION)
                                ->orWhere(function ($paidQuery) use ($monthStart, $monthEnd): void {
                                    $paidQuery
                                        ->where('status', ChargePayment::STATUS_SUCCEEDED)
                                        ->whereBetween('paid_at', [$monthStart, $monthEnd]);
                                });
                        })])
                    ->orderByDesc('due_date')
                    ->orderByDesc('id'),
            ])
            ->withCount([
                'maintenanceTickets as open_tickets_count' => fn ($query) => $query->whereNotIn('status', ['completado', 'cancelado']),
                'charges as pending_charges_count' => fn ($query) => $query->whereIn('status', [
                    Charge::STATUS_PENDING,
                    Charge::STATUS_PARTIAL,
                    Charge::STATUS_IN_VALIDATION,
                ]),
            ]);
    }

    private function pendingCoordinatesQuery(Request $request)
    {
        return $this->propertyVisibility->scopeVisibleToUser(Property::query(), $request->user())
            ->whereNull('archived_at')
            ->whereNotNull('map_url')
            ->where('map_url', '<>', '')
            ->where(function ($query): void {
                $query->whereNull('map_latitude')->orWhereNull('map_longitude');
            })
            ->whereNull('map_coordinates_checked_at');
    }

    private function ensureCanAccessMap(Request $request): void
    {
        $user = $request->user();
        if ($user && ! $user->hasAnyRole(['administrador', 'admin'])) {
            abort_if($user->hasAnyRole(['inquilino', 'tenant', 'propietario', 'owner', 'tecnico', 'technician', 'proveedor', 'provider']), 403);
        }
    }

    private function markerFor(Property $property): array
    {
        $openCharges = $property->charges->whereIn('status', [
            Charge::STATUS_PENDING,
            Charge::STATUS_PARTIAL,
            Charge::STATUS_IN_VALIDATION,
        ]);
        $pendingAmount = $openCharges->sum(fn (Charge $charge): float => $charge->outstanding_amount);
        $overdueAmount = $openCharges
            ->filter(fn (Charge $charge): bool => $charge->is_overdue)
            ->sum(fn (Charge $charge): float => $charge->outstanding_amount);
        $payments = $property->charges->flatMap->payments;
        $collectedThisMonth = $payments
            ->where('status', ChargePayment::STATUS_SUCCEEDED)
            ->sum(fn (ChargePayment $payment): float => (float) $payment->amount);
        $pendingValidationCount = $payments
            ->where('status', ChargePayment::STATUS_PENDING_VALIDATION)
            ->count();

        return [
            'id' => $property->id,
            'uuid' => $property->uuid,
            'name' => $property->internal_name,
            'billing_currency' => strtoupper($property->billing_currency ?? 'mxn'),
            'reference' => $property->internal_reference,
            'address' => $property->full_address,
            'type' => $property->type?->name,
            'zone' => $property->zone?->name ?: $property->zone_text,
            'status' => $property->status,
            'status_label' => $property->status_label,
            'status_badge_class' => $property->status_badge_class,
            'latitude' => (float) $property->map_latitude,
            'longitude' => (float) $property->map_longitude,
            'photo_url' => $property->facade_photo_path
                ? Storage::url($property->facade_photo_path)
                : asset('metronic/assets/media/svg/files/blank-image.svg'),
            'map_url' => $property->map_url,
            'show_url' => route('properties.show', $property),
            'edit_url' => route('properties.edit', $property),
            'rent' => $property->monthly_rent_price !== null ? number_format((float) $property->monthly_rent_price, 2) : null,
            'tenant' => $property->tenant?->full_name ?: $property->current_tenant_name,
            'owner_names' => $property->owners->pluck('name')->filter()->values()->all(),
            'advisor' => $property->advisor?->name,
            'contract_starts_at' => $property->contract_starts_at?->format('d/m/Y'),
            'contract_expires_at' => $property->contract_expires_at?->format('d/m/Y'),
            'open_tickets_count' => (int) $property->open_tickets_count,
            'pending_charges_count' => (int) $property->pending_charges_count,
            'charges_url' => route('charges.index', ['property' => $property->uuid]),
            'charge_summary' => [
                'pending_amount' => round((float) $pendingAmount, 2),
                'overdue_amount' => round((float) $overdueAmount, 2),
                'collected_month' => round((float) $collectedThisMonth, 2),
                'pending_validation_count' => $pendingValidationCount,
                'recent_charges' => $property->charges
                    ->take(5)
                    ->map(fn (Charge $charge): array => [
                        'concept' => $charge->concept,
                        'due_date' => $charge->due_date?->format('d/m/Y'),
                        'amount' => round((float) $charge->amount, 2),
                        'outstanding_amount' => round($charge->outstanding_amount, 2),
                        'status_label' => $charge->display_status_label,
                        'status_badge_class' => $charge->status_badge_class,
                        'show_url' => route('charges.show', $charge),
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }
}
