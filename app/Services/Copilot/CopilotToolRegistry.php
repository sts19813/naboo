<?php

namespace App\Services\Copilot;

use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Expense;
use App\Models\MaintenanceTicket;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\StorageItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CopilotToolRegistry
{
    public function definitions(): array
    {
        return [
            $this->tool('get_dashboard_summary', 'Obtiene indicadores generales del dashboard: propiedades, ingreso esperado del periodo, cobrado, pendiente, vencido, gastos, mantenimiento, documentos y almacen.', [
                'period' => ['type' => 'string', 'description' => 'current_month, last_month, next_30_days o all. Si el usuario dice periodo o este mes, usa current_month.'],
                'start_date' => ['type' => 'string', 'description' => 'Fecha inicial YYYY-MM-DD para un periodo personalizado.'],
                'end_date' => ['type' => 'string', 'description' => 'Fecha final YYYY-MM-DD para un periodo personalizado.'],
            ]),
            $this->tool('search_properties', 'Busca propiedades por texto, estado, zona o referencia.', [
                'query' => ['type' => 'string', 'description' => 'Texto libre: nombre, referencia, direccion, zona o inquilino.'],
                'status' => ['type' => 'string', 'description' => 'Estado de propiedad: draft, available, in_process, blocked, occupied, rented.'],
                'limit' => ['type' => 'integer', 'description' => 'Maximo de resultados, de 1 a 20.'],
            ]),
            $this->tool('get_property_detail', 'Obtiene detalle operativo de una propiedad concreta por nombre, referencia o UUID.', [
                'property' => ['type' => 'string', 'description' => 'Nombre, referencia interna o UUID de la propiedad.'],
            ], ['property']),
            $this->tool('list_charges', 'Lista cargos de cobranza con filtros de estado, periodo, tipo o propiedad.', [
                'status' => ['type' => 'string', 'description' => 'pending, partial, in_validation, paid o canceled.'],
                'period' => ['type' => 'string', 'description' => 'overdue, current_month, next_30_days o all.'],
                'property' => ['type' => 'string', 'description' => 'Nombre o referencia de propiedad.'],
                'limit' => ['type' => 'integer', 'description' => 'Maximo de resultados, de 1 a 30.'],
            ]),
            $this->tool('list_expenses', 'Lista gastos por estado calculado, periodo o propiedad.', [
                'status' => ['type' => 'string', 'description' => 'pending, overdue o paid.'],
                'period' => ['type' => 'string', 'description' => 'overdue, current_month, next_30_days o all.'],
                'property' => ['type' => 'string', 'description' => 'Nombre o referencia de propiedad.'],
                'limit' => ['type' => 'integer', 'description' => 'Maximo de resultados, de 1 a 30.'],
            ]),
            $this->tool('list_maintenance_tickets', 'Lista tickets de mantenimiento por estado, prioridad, categoria o propiedad.', [
                'status' => ['type' => 'string', 'description' => 'pendiente, revisado, asignado, programado, en_proceso, esperando_material, completado, cancelado o reabierto.'],
                'priority' => ['type' => 'string', 'description' => 'sin_asignar, baja, media, alta o urgente.'],
                'category' => ['type' => 'string', 'description' => 'plomeria, electricidad, aire_acondicionado, limpieza, seguridad, electrodomesticos o estructural.'],
                'property' => ['type' => 'string', 'description' => 'Nombre o referencia de propiedad.'],
                'limit' => ['type' => 'integer', 'description' => 'Maximo de resultados, de 1 a 30.'],
            ]),
            $this->tool('list_documents_status', 'Consulta expedientes y documentos por entidad, estado o vencimiento.', [
                'entity_type' => ['type' => 'string', 'description' => 'property, owner, tenant o all.'],
                'status' => ['type' => 'string', 'description' => 'pending, uploaded, approved, rejected, expired o all.'],
                'expires_within_days' => ['type' => 'integer', 'description' => 'Documentos que vencen dentro de N dias.'],
                'limit' => ['type' => 'integer', 'description' => 'Maximo de resultados, de 1 a 30.'],
            ]),
            $this->tool('search_storage_items', 'Busca articulos de almacen por texto, condicion, bodega o zona.', [
                'query' => ['type' => 'string', 'description' => 'Texto libre: nombre, marca, tipo, descripcion, bodega o zona.'],
                'condition' => ['type' => 'string', 'description' => 'bueno, regular o malo.'],
                'limit' => ['type' => 'integer', 'description' => 'Maximo de resultados, de 1 a 30.'],
            ]),
            $this->tool('search_system_knowledge', 'Busqueda transversal tipo RAG lite sobre notas, descripciones, expedientes, tickets, inquilinos, propietarios y almacen.', [
                'query' => ['type' => 'string', 'description' => 'Pregunta o terminos relevantes a buscar.'],
                'limit' => ['type' => 'integer', 'description' => 'Maximo de resultados, de 1 a 20.'],
            ], ['query']),
        ];
    }

    public function execute(string $name, array $arguments, User $user): array
    {
        return match ($name) {
            'get_dashboard_summary' => $this->dashboardSummary($arguments, $user),
            'search_properties' => $this->searchProperties($arguments, $user),
            'get_property_detail' => $this->propertyDetail($arguments, $user),
            'list_charges' => $this->listCharges($arguments, $user),
            'list_expenses' => $this->listExpenses($arguments, $user),
            'list_maintenance_tickets' => $this->listMaintenanceTickets($arguments, $user),
            'list_documents_status' => $this->listDocumentsStatus($arguments, $user),
            'search_storage_items' => $this->searchStorageItems($arguments),
            'search_system_knowledge' => $this->searchSystemKnowledge($arguments, $user),
            default => throw new InvalidArgumentException("Herramienta no disponible: {$name}"),
        };
    }

    private function tool(string $name, string $description, array $properties, array $required = []): array
    {
        return [
            'type' => 'function',
            'name' => $name,
            'description' => $description,
            'parameters' => [
                'type' => 'object',
                'properties' => $properties === [] ? (object) [] : $properties,
                'required' => $required,
                'additionalProperties' => false,
            ],
        ];
    }

    private function dashboardSummary(array $arguments, User $user): array
    {
        [$periodStart, $periodEnd] = $this->dashboardPeriod($arguments);
        $propertyQuery = $this->visibleProperties($user);
        $propertyIds = (clone $propertyQuery)->pluck('id');

        $charges = Charge::query()->whereIn('property_id', $propertyIds);
        $chargesForPeriod = Charge::query()
            ->whereIn('property_id', $propertyIds)
            ->where('status', '!=', Charge::STATUS_CANCELED)
            ->whereBetween('due_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
        $expenses = Expense::query()->whereIn('property_id', $propertyIds);
        $tickets = MaintenanceTicket::query()->whereIn('property_id', $propertyIds);
        $expectedIncome = (float) (clone $chargesForPeriod)->sum('amount');
        $paidThisPeriod = (float) ChargePayment::query()
            ->whereHas('charge', fn (Builder $query) => $query->whereIn('property_id', $propertyIds))
            ->where('status', ChargePayment::STATUS_SUCCEEDED)
            ->whereBetween('paid_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->sum('amount');

        $pendingThisPeriod = 0.0;
        $overdueThisPeriod = 0.0;

        (clone $chargesForPeriod)->get()->each(function (Charge $charge) use (&$pendingThisPeriod, &$overdueThisPeriod): void {
            $outstanding = max(0, (float) $charge->amount - (float) $charge->paid_amount);

            if ($outstanding <= 0) {
                return;
            }

            if (in_array($charge->status, [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL], true)
                && $charge->due_date
                && $charge->due_date->lt(now()->startOfDay())) {
                $overdueThisPeriod += $outstanding;

                return;
            }

            $pendingThisPeriod += $outstanding;
        });

        return [
            'properties' => [
                'total' => (clone $propertyQuery)->count(),
                'by_status' => $this->countBy((clone $propertyQuery), 'status'),
            ],
            'charges' => [
                'period' => [
                    'start_date' => $periodStart->toDateString(),
                    'end_date' => $periodEnd->toDateString(),
                ],
                'expected_income_this_period' => round($expectedIncome, 2),
                'paid_this_period' => round($paidThisPeriod, 2),
                'pending_this_period' => round($pendingThisPeriod, 2),
                'overdue_this_period' => round($overdueThisPeriod, 2),
                'open_amount_this_period' => round($pendingThisPeriod + $overdueThisPeriod, 2),
                'total_open_amount' => (float) (clone $charges)
                    ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
                    ->sum(\DB::raw('amount - paid_amount')),
                'overdue_count' => (clone $charges)
                    ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
                'paid_this_month' => (float) ChargePayment::query()
                    ->whereHas('charge', fn (Builder $query) => $query->whereIn('property_id', $propertyIds))
                    ->where('status', ChargePayment::STATUS_SUCCEEDED)
                    ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('amount'),
                'by_status' => $this->countBy((clone $charges), 'status'),
            ],
            'expenses' => [
                'pending_amount' => (float) (clone $expenses)->whereNull('paid_at')->sum('amount'),
                'overdue_count' => (clone $expenses)->whereNull('paid_at')->whereDate('due_date', '<', now()->toDateString())->count(),
                'paid_this_month' => (float) (clone $expenses)->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            ],
            'maintenance' => [
                'open_count' => (clone $tickets)->whereNotIn('status', ['completado', 'cancelado'])->count(),
                'urgent_open_count' => (clone $tickets)->whereNotIn('status', ['completado', 'cancelado'])->where('priority', 'urgente')->count(),
                'by_status' => $this->countBy((clone $tickets), 'status'),
            ],
            'documents' => [
                'properties_pending' => PropertyDocument::query()->whereIn('property_id', $propertyIds)->where('status', 'pending')->count(),
                'properties_uploaded' => PropertyDocument::query()->whereIn('property_id', $propertyIds)->where('status', 'uploaded')->count(),
                'properties_expiring_30_days' => PropertyDocument::query()
                    ->whereIn('property_id', $propertyIds)
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->count(),
            ],
            'storage' => [
                'items' => StorageItem::query()->count(),
                'low_quality_items' => StorageItem::query()->whereIn('condition', ['regular', 'malo'])->count(),
            ],
        ];
    }

    private function searchProperties(array $arguments, User $user): array
    {
        $query = trim((string) ($arguments['query'] ?? ''));
        $status = $this->filterValue($arguments['status'] ?? null);
        $limit = $this->limit($arguments, 10, 20);

        $properties = $this->visibleProperties($user)
            ->with(['type', 'zone', 'tenant', 'owners', 'advisor'])
            ->when($status !== null, fn (Builder $builder) => $builder->where('status', $status))
            ->when($query !== '', fn (Builder $builder) => $this->propertyTextFilter($builder, $query))
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Property $property): array => $this->propertyCard($property))
            ->all();

        return [
            'count' => count($properties),
            'properties' => $properties,
        ];
    }

    private function propertyDetail(array $arguments, User $user): array
    {
        $needle = trim((string) ($arguments['property'] ?? ''));

        $properties = $this->visibleProperties($user)
            ->with([
                'type',
                'zone',
                'tenant',
                'owners',
                'advisor',
                'charges' => fn ($query) => $query->latest('due_date')->limit(8),
                'expenses' => fn ($query) => $query->latest('due_date')->limit(8),
                'maintenanceTickets' => fn ($query) => $query->latest('reported_at')->limit(8),
                'documents',
                'inventoryAreas.items',
            ])
            ->where(function (Builder $builder) use ($needle): void {
                if ($needle === '') {
                    return;
                }

                $builder->where('uuid', $needle)
                    ->orWhere('internal_reference', 'like', "%{$needle}%")
                    ->orWhere(function (Builder $query) use ($needle): void {
                        $this->propertyTextFilter($query, $needle);
                    });
            })
            ->limit(4)
            ->get();

        if ($properties->isEmpty()) {
            return ['found' => false, 'message' => 'No se encontro una propiedad visible con ese criterio.'];
        }

        if ($properties->count() > 1) {
            return [
                'found' => false,
                'ambiguous' => true,
                'message' => 'Hay mas de una propiedad que coincide con ese criterio.',
                'matches' => $properties->map(fn (Property $property): array => $this->propertyCard($property))->all(),
            ];
        }

        $property = $properties->first();

        return [
            'found' => true,
            'property' => $this->propertyCard($property) + [
                'details' => $property->details,
                'description' => $property->description,
                'amenities' => $property->amenities,
                'rental_requirements' => $property->rental_requirements,
                'owners' => $property->owners->pluck('name')->all(),
                'charges' => $property->charges->map(fn (Charge $charge): array => $this->chargeCard($charge))->all(),
                'expenses' => $property->expenses->map(fn (Expense $expense): array => $this->expenseCard($expense))->all(),
                'maintenance' => $property->maintenanceTickets->map(fn (MaintenanceTicket $ticket): array => $this->ticketCard($ticket))->all(),
                'documents' => $property->documents->map(fn (PropertyDocument $document): array => [
                    'label' => $document->label,
                    'status' => $document->status,
                    'expires_at' => $document->expires_at?->toDateString(),
                ])->all(),
                'inventory' => $property->inventoryAreas->map(fn ($area): array => [
                    'area' => $area->name,
                    'items' => $area->items->map(fn ($item): array => [
                        'name' => $item->name,
                        'condition' => $item->condition,
                        'notes' => $item->notes,
                    ])->all(),
                ])->all(),
            ],
        ];
    }

    private function listCharges(array $arguments, User $user): array
    {
        $limit = $this->limit($arguments, 12, 30);
        $propertyIds = $this->visibleProperties($user)->pluck('id');
        $status = $this->filterValue($arguments['status'] ?? null);

        $charges = Charge::query()
            ->with(['property', 'tenant'])
            ->whereIn('property_id', $propertyIds)
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when(filled($arguments['property'] ?? null), fn (Builder $query) => $query->whereHas('property', fn (Builder $propertyQuery) => $this->propertyTextFilter($propertyQuery, (string) $arguments['property'])))
            ->tap(fn (Builder $query) => $this->applyDatePeriod($query, 'due_date', (string) ($arguments['period'] ?? 'all')))
            ->orderBy('due_date')
            ->limit($limit)
            ->get();

        return [
            'count' => $charges->count(),
            'total_amount' => (float) $charges->sum('amount'),
            'total_outstanding' => (float) $charges->sum(fn (Charge $charge) => max(0, (float) $charge->amount - (float) $charge->paid_amount)),
            'charges' => $charges->map(fn (Charge $charge): array => $this->chargeCard($charge))->all(),
        ];
    }

    private function listExpenses(array $arguments, User $user): array
    {
        $limit = $this->limit($arguments, 12, 30);
        $propertyIds = $this->visibleProperties($user)->pluck('id');
        $status = (string) ($arguments['status'] ?? '');

        $expenses = Expense::query()
            ->with('property')
            ->whereIn('property_id', $propertyIds)
            ->when($status === 'paid', fn (Builder $query) => $query->whereNotNull('paid_at'))
            ->when($status === 'pending', fn (Builder $query) => $query->whereNull('paid_at')->whereDate('due_date', '>=', now()->toDateString()))
            ->when($status === 'overdue', fn (Builder $query) => $query->whereNull('paid_at')->whereDate('due_date', '<', now()->toDateString()))
            ->when(filled($arguments['property'] ?? null), fn (Builder $query) => $query->whereHas('property', fn (Builder $propertyQuery) => $this->propertyTextFilter($propertyQuery, (string) $arguments['property'])))
            ->tap(fn (Builder $query) => $this->applyDatePeriod($query, 'due_date', (string) ($arguments['period'] ?? 'all')))
            ->orderBy('due_date')
            ->limit($limit)
            ->get();

        return [
            'count' => $expenses->count(),
            'total_amount' => (float) $expenses->sum('amount'),
            'expenses' => $expenses->map(fn (Expense $expense): array => $this->expenseCard($expense))->all(),
        ];
    }

    private function listMaintenanceTickets(array $arguments, User $user): array
    {
        $limit = $this->limit($arguments, 12, 30);
        $propertyIds = $this->visibleProperties($user)->pluck('id');
        $status = $this->filterValue($arguments['status'] ?? null);
        $priority = $this->filterValue($arguments['priority'] ?? null);
        $category = $this->filterValue($arguments['category'] ?? null);

        $tickets = MaintenanceTicket::query()
            ->with(['property', 'currentProvider'])
            ->whereIn('property_id', $propertyIds)
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($priority !== null, fn (Builder $query) => $query->where('priority', $priority))
            ->when($category !== null, fn (Builder $query) => $query->where('category', $category))
            ->when(filled($arguments['property'] ?? null), fn (Builder $query) => $query->whereHas('property', fn (Builder $propertyQuery) => $this->propertyTextFilter($propertyQuery, (string) $arguments['property'])))
            ->latest('reported_at')
            ->limit($limit)
            ->get();

        return [
            'count' => $tickets->count(),
            'tickets' => $tickets->map(fn (MaintenanceTicket $ticket): array => $this->ticketCard($ticket))->all(),
        ];
    }

    private function listDocumentsStatus(array $arguments, User $user): array
    {
        $limit = $this->limit($arguments, 12, 30);
        $entityType = (string) ($arguments['entity_type'] ?? 'all');
        $status = (string) ($arguments['status'] ?? 'all');
        $expiresWithinDays = isset($arguments['expires_within_days'])
            ? max(0, min(365, (int) $arguments['expires_within_days']))
            : null;

        $propertyIds = $this->visibleProperties($user)->pluck('id');
        $rows = collect();

        if (in_array($entityType, ['all', 'property'], true)) {
            $rows = $rows->merge(PropertyDocument::query()
                ->with('property')
                ->whereIn('property_id', $propertyIds)
                ->when($status !== 'all' && $status !== '', fn (Builder $query) => $query->where('status', $status))
                ->when($expiresWithinDays !== null, fn (Builder $query) => $query->whereNotNull('expires_at')->whereBetween('expires_at', [now()->toDateString(), now()->addDays($expiresWithinDays)->toDateString()]))
                ->limit($limit)
                ->get()
                ->map(fn (PropertyDocument $document): array => [
                    'entity_type' => 'property',
                    'entity_name' => $document->property?->internal_name,
                    'label' => $document->label,
                    'status' => $document->status,
                    'expires_at' => $document->expires_at?->toDateString(),
                ]));
        }

        if (in_array($entityType, ['all', 'tenant'], true)) {
            $visibleTenantIds = Tenant::query()->whereHas('properties', fn (Builder $query) => $query->whereIn('properties.id', $propertyIds))->pluck('tenants.id');
            $rows = $rows->merge(\App\Models\TenantDocument::query()
                ->with('tenant')
                ->whereIn('tenant_id', $visibleTenantIds)
                ->when($status !== 'all' && $status !== '', fn (Builder $query) => $query->where('status', $status))
                ->when($expiresWithinDays !== null, fn (Builder $query) => $query->whereNotNull('expires_at')->whereBetween('expires_at', [now()->toDateString(), now()->addDays($expiresWithinDays)->toDateString()]))
                ->limit($limit)
                ->get()
                ->map(fn ($document): array => [
                    'entity_type' => 'tenant',
                    'entity_name' => $document->tenant?->full_name,
                    'label' => $document->label,
                    'status' => $document->status,
                    'expires_at' => $document->expires_at?->toDateString(),
                ]));
        }

        if (in_array($entityType, ['all', 'owner'], true)) {
            $visibleOwnerIds = Owner::query()->whereHas('properties', fn (Builder $query) => $query->whereIn('properties.id', $propertyIds))->pluck('owners.id');
            $rows = $rows->merge(\App\Models\OwnerDocument::query()
                ->with('owner')
                ->whereIn('owner_id', $visibleOwnerIds)
                ->when($status !== 'all' && $status !== '', fn (Builder $query) => $query->where('status', $status))
                ->when($expiresWithinDays !== null, fn (Builder $query) => $query->whereNotNull('expires_at')->whereBetween('expires_at', [now()->toDateString(), now()->addDays($expiresWithinDays)->toDateString()]))
                ->limit($limit)
                ->get()
                ->map(fn ($document): array => [
                    'entity_type' => 'owner',
                    'entity_name' => $document->owner?->name,
                    'label' => $document->label,
                    'status' => $document->status,
                    'expires_at' => $document->expires_at?->toDateString(),
                ]));
        }

        return [
            'count' => $rows->count(),
            'documents' => $rows->take($limit)->values()->all(),
        ];
    }

    private function searchStorageItems(array $arguments): array
    {
        $query = trim((string) ($arguments['query'] ?? ''));
        $condition = trim((string) ($arguments['condition'] ?? ''));
        $limit = $this->limit($arguments, 12, 30);

        $items = StorageItem::query()
            ->with(['warehouse', 'zone'])
            ->when($condition !== '', fn (Builder $builder) => $builder->where('condition', $condition))
            ->when($query !== '', function (Builder $builder) use ($query): void {
                $like = "%{$query}%";
                $builder->where(function (Builder $subQuery) use ($like): void {
                    $subQuery
                        ->where('name', 'like', $like)
                        ->orWhere('brand', 'like', $like)
                        ->orWhere('product_type', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('warehouse', fn (Builder $warehouseQuery) => $warehouseQuery->where('name', 'like', $like))
                        ->orWhereHas('zone', fn (Builder $zoneQuery) => $zoneQuery->where('name', 'like', $like));
                });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (StorageItem $item): array => [
                'name' => $item->name,
                'type' => $item->product_type,
                'brand' => $item->brand,
                'condition' => $item->condition,
                'quantity' => $item->quantity,
                'warehouse' => $item->warehouse?->name,
                'zone' => $item->zone?->name,
                'description' => Str::limit((string) $item->description, 180),
                'url' => route('storage_items.show', $item),
            ])
            ->all();

        return [
            'count' => count($items),
            'items' => $items,
        ];
    }

    private function searchSystemKnowledge(array $arguments, User $user): array
    {
        $query = trim((string) ($arguments['query'] ?? ''));
        $limit = $this->limit($arguments, 10, 20);
        $propertyIds = $this->visibleProperties($user)->pluck('id');

        if ($query === '') {
            return ['count' => 0, 'results' => []];
        }

        $like = "%{$query}%";
        $results = collect();

        $results = $results->merge(Property::query()
            ->whereIn('id', $propertyIds)
            ->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where('internal_name', 'like', $like)
                    ->orWhere('internal_reference', 'like', $like)
                    ->orWhere('full_address', 'like', $like)
                    ->orWhere('details', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('amenities', 'like', $like);
            })
            ->limit($limit)
            ->get()
            ->map(fn (Property $property): array => [
                'source' => 'properties',
                'title' => $property->internal_reference.' - '.$property->internal_name,
                'snippet' => Str::limit(trim($property->description.' '.$property->details.' '.$property->amenities), 260),
            ]));

        $results = $results->merge(MaintenanceTicket::query()
            ->whereIn('property_id', $propertyIds)
            ->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('additional_notes', 'like', $like)
                    ->orWhere('exact_location', 'like', $like);
            })
            ->limit($limit)
            ->get()
            ->map(fn (MaintenanceTicket $ticket): array => [
                'source' => 'maintenance',
                'title' => ($ticket->reference ?: $ticket->display_reference).' - '.$ticket->title,
                'snippet' => Str::limit($ticket->description.' '.$ticket->additional_notes, 260),
            ]));

        $results = $results->merge(PropertyDocument::query()
            ->with('property')
            ->whereIn('property_id', $propertyIds)
            ->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where('label', 'like', $like)
                    ->orWhere('document_type', 'like', $like)
                    ->orWhere('status', 'like', $like);
            })
            ->limit($limit)
            ->get()
            ->map(fn (PropertyDocument $document): array => [
                'source' => 'property_documents',
                'title' => $document->label.' - '.$document->property?->internal_name,
                'snippet' => 'Estado: '.$document->status.', vence: '.($document->expires_at?->toDateString() ?: 'sin vencimiento'),
            ]));

        $results = $results->merge(StorageItem::query()
            ->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('product_type', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->limit($limit)
            ->get()
            ->map(fn (StorageItem $item): array => [
                'source' => 'storage',
                'title' => $item->name,
                'snippet' => Str::limit($item->product_type.' '.$item->brand.' '.$item->description, 260),
            ]));

        return [
            'count' => $results->count(),
            'results' => $results->take($limit)->values()->all(),
        ];
    }

    private function visibleProperties(User $user): Builder
    {
        $query = Property::query();

        if ($user->hasRole('administrador') || $user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('asesores') || $user->hasRole('asesor') || $user->can('propiedades.ver_propias')) {
            return $query->where(function (Builder $builder) use ($user): void {
                $builder
                    ->where('advisor_user_id', $user->id)
                    ->orWhereHas('advisors', fn (Builder $advisorQuery) => $advisorQuery->where('users.id', $user->id));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    private function propertyTextFilter(Builder $builder, string $query): void
    {
        $tokens = collect(preg_split('/\s+/', Str::lower(trim($query))) ?: [])
            ->map(fn (string $token): string => trim($token))
            ->filter(fn (string $token): bool => $token !== '')
            ->values();

        if ($tokens->isEmpty()) {
            return;
        }

        $builder->where(function (Builder $outerQuery) use ($tokens): void {
            $tokens->each(function (string $token) use ($outerQuery): void {
                $like = "%{$token}%";

                $outerQuery->where(function (Builder $subQuery) use ($like): void {
                    $subQuery
                        ->where('internal_name', 'like', $like)
                        ->orWhere('internal_reference', 'like', $like)
                        ->orWhere('full_address', 'like', $like)
                        ->orWhere('zone_text', 'like', $like)
                        ->orWhere('complex_name', 'like', $like)
                        ->orWhere('current_tenant_name', 'like', $like);
                });
            });
        });
    }

    private function applyDatePeriod(Builder $query, string $column, string $period): void
    {
        match ($period) {
            'overdue' => $query->whereDate($column, '<', now()->toDateString()),
            'current_month' => $query->whereBetween($column, [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]),
            'next_30_days' => $query->whereBetween($column, [now()->toDateString(), now()->addDays(30)->toDateString()]),
            default => null,
        };
    }

    private function dashboardPeriod(array $arguments): array
    {
        $startDate = filled($arguments['start_date'] ?? null)
            ? Carbon::parse((string) $arguments['start_date'])->startOfDay()
            : null;
        $endDate = filled($arguments['end_date'] ?? null)
            ? Carbon::parse((string) $arguments['end_date'])->endOfDay()
            : null;

        if ($startDate && $endDate) {
            return [$startDate, $endDate];
        }

        return match ((string) ($arguments['period'] ?? 'current_month')) {
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'next_30_days' => [now()->startOfDay(), now()->addDays(30)->endOfDay()],
            'all' => [Carbon::create(2000, 1, 1)->startOfDay(), now()->addYears(10)->endOfDay()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    private function propertyCard(Property $property): array
    {
        return [
            'reference' => $property->internal_reference,
            'name' => $property->internal_name,
            'status' => $property->status,
            'status_label' => $property->status_label,
            'type' => $property->type?->name,
            'zone' => $property->zone?->name ?? $property->zone_text,
            'address' => $property->full_address,
            'monthly_rent_price' => (float) $property->monthly_rent_price,
            'tenant' => $property->tenant?->full_name ?? $property->current_tenant_name,
            'contract_expires_at' => $property->contract_expires_at?->toDateString(),
            'advisor' => $property->advisor?->name,
            'url' => route('properties.show', $property),
        ];
    }

    private function chargeCard(Charge $charge): array
    {
        return [
            'concept' => $charge->concept,
            'property' => $charge->property?->internal_name,
            'tenant' => $charge->tenant?->full_name,
            'type' => $charge->type,
            'status' => $charge->status,
            'status_label' => $charge->display_status_label,
            'due_date' => $charge->due_date?->toDateString(),
            'amount' => (float) $charge->amount,
            'paid_amount' => (float) $charge->paid_amount,
            'outstanding_amount' => (float) $charge->outstanding_amount,
            'is_overdue' => (bool) $charge->is_overdue,
            'url' => route('charges.show', $charge),
            'property_url' => $charge->property ? route('properties.show', $charge->property) : null,
        ];
    }

    private function expenseCard(Expense $expense): array
    {
        return [
            'concept' => $expense->concept,
            'property' => $expense->property?->internal_name,
            'status' => $expense->computed_status,
            'status_label' => $expense->status_label,
            'due_date' => $expense->due_date?->toDateString(),
            'amount' => (float) $expense->amount,
            'paid_at' => $expense->paid_at?->toDateString(),
            'description' => Str::limit((string) $expense->description, 160),
            'property_url' => $expense->property ? route('properties.show', $expense->property) : null,
        ];
    }

    private function ticketCard(MaintenanceTicket $ticket): array
    {
        return [
            'reference' => $ticket->reference ?: $ticket->display_reference,
            'title' => $ticket->title,
            'property' => $ticket->property?->internal_name,
            'status' => $ticket->status,
            'status_label' => MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status,
            'priority' => $ticket->priority,
            'priority_label' => MaintenanceTicket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority,
            'category' => $ticket->category,
            'category_label' => MaintenanceTicket::CATEGORY_LABELS[$ticket->category] ?? $ticket->category,
            'provider' => $ticket->currentProvider?->name,
            'reported_at' => $ticket->reported_at?->toDateTimeString(),
            'scheduled_visit_at' => $ticket->scheduled_visit_at?->toDateTimeString(),
            'description' => Str::limit((string) $ticket->description, 180),
            'url' => route('maintenance.show', $ticket),
            'property_url' => $ticket->property ? route('properties.show', $ticket->property) : null,
        ];
    }

    private function countBy(Builder $query, string $column): array
    {
        return $query
            ->selectRaw($column.', COUNT(*) as total')
            ->groupBy($column)
            ->pluck('total', $column)
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function limit(array $arguments, int $default, int $max): int
    {
        return max(1, min($max, (int) ($arguments['limit'] ?? $default)));
    }

    private function filterValue(mixed $value): ?string
    {
        $value = trim(Str::lower((string) $value));

        return in_array($value, ['', 'all', 'any', 'todos', 'todas'], true) ? null : $value;
    }
}
