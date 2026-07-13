<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PropertyControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PropertyControlController extends Controller
{
    public function __construct(
        private readonly PropertyControlService $propertyControlService,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless(
            $request->user()?->can('propiedades.control_ver')
                || $request->user()?->hasRole('administrador')
                || $request->user()?->hasRole('admin'),
            403
        );

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', 'string', 'in:all,complete,incomplete,no_advisor,no_contract,no_charges,no_dossier'],
        ]);

        $rawSearch = trim((string) ($filters['q'] ?? ''));
        $search = mb_strtolower($rawSearch);
        $statusFilter = $filters['status'] ?? 'all';

        $properties = Property::query()
            ->with([
                'advisor:id,name,email',
                'advisors:id,name,email',
                'tenant:id,full_name',
                'owners.documents:id,owner_id,file_path',
                'documents:id,property_id,file_path',
                'tenant.documents:id,tenant_id,file_path',
                'inventoryAreas.items:id,property_inventory_area_id',
                'charges:id,property_id',
            ])
            ->orderBy('internal_name')
            ->get();

        $snapshots = $this->propertyControlService->buildCollection($properties);
        $statusOptions = [
            'all' => 'Todas',
            'complete' => 'Completas',
            'incomplete' => 'Incompletas',
            'no_advisor' => 'Sin asesor',
            'no_contract' => 'Sin contrato',
            'no_charges' => 'Sin cobranza',
            'no_dossier' => 'Sin expediente',
        ];

        return view('properties.control', [
            'filters' => [
                'q' => $rawSearch,
                'status' => $statusFilter,
            ],
            'summary' => [
                'total' => $snapshots->count(),
                'complete' => $snapshots->where('is_complete', true)->count(),
                'incomplete' => $snapshots->where('is_complete', false)->count(),
                'without_advisor' => $snapshots->filter(fn ($row) => !($row['checks']['advisor'] ?? false))->count(),
                'overall_progress' => (int) round($snapshots->avg('progress_percent') ?? 0),
            ],
            'resultCount' => $this->filterSnapshots($snapshots, $search, $statusFilter)->count(),
            'snapshots' => $snapshots->values(),
            'statusOptions' => $statusOptions,
            'statusCounts' => collect(array_keys($statusOptions))
                ->mapWithKeys(fn (string $status) => [$status => $this->filterSnapshots($snapshots, '', $status)->count()])
                ->all(),
            'checkLabels' => $this->propertyControlService->checkLabels(),
        ]);
    }

    private function filterSnapshots(Collection $snapshots, string $search, string $statusFilter): Collection
    {
        return $snapshots
            ->when($search !== '', fn (Collection $collection) => $collection->filter(
                fn (array $row) => str_contains($row['search_text'], $search)
            ))
            ->when($statusFilter !== 'all', function (Collection $collection) use ($statusFilter): Collection {
                return $collection->filter(fn (array $row): bool => $this->matchesStatus($row, $statusFilter));
            });
    }

    private function matchesStatus(array $row, string $statusFilter): bool
    {
        return match ($statusFilter) {
            'complete' => $row['is_complete'],
            'incomplete' => !$row['is_complete'],
            'no_advisor' => !($row['checks']['advisor'] ?? false),
            'no_contract' => !($row['checks']['contract'] ?? false),
            'no_charges' => !($row['checks']['charges'] ?? false),
            'no_dossier' => $row['has_dossier_gap'],
            default => true,
        };
    }
}
