<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Support\Collection;

class PendingNotificationService
{
    /** @var array<string, array{total: int, route: string, items: array<int, array<string, mixed>>}> */
    private array $cache = [];

    /**
     * @return array{total: int, route: string, items: array<int, array<string, mixed>>}
     */
    public function forUser(?User $user): array
    {
        if (! $user) {
            return $this->emptySummary();
        }

        $cacheKey = $user->id.'|'.now()->toDateString();

        return $this->cache[$cacheKey] ??= match (true) {
            $user->hasAnyRole(['administrador', 'admin']) => $this->forAdministrator(),
            $user->hasAnyRole(['tecnico', 'technician']) => $this->forTechnician($user),
            $user->hasAnyRole(['inquilino', 'tenant']) => $this->forTenant($user),
            $user->hasAnyRole(['asesores', 'asesor', 'advisor']) || $user->can('propiedades.ver_propias') => $this->forAdvisor($user),
            default => $this->emptySummary(),
        };
    }

    /**
     * @return array{total: int, route: string, items: array<int, array<string, mixed>>}
     */
    private function forAdministrator(): array
    {
        $taskUsers = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                'asesores',
                'asesor',
                'advisor',
                'tecnico',
                'technician',
            ]))
            ->with('roles:id,name')
            ->orderBy('name')
            ->get();

        $items = $taskUsers->map(function (User $taskUser): array {
            $isTechnician = $taskUser->hasAnyRole(['tecnico', 'technician']);
            $count = $isTechnician
                ? $this->countTechnicianTasks($taskUser)
                : $this->countPropertyTasks($this->advisorPropertyIds($taskUser));

            return [
                'title' => $taskUser->name,
                'subtitle' => $isTechnician ? 'Técnico' : 'Asesor',
                'count' => $count,
                'route' => route('admin.tasks.index', [
                    'user_id' => $taskUser->id,
                    'range' => 'today',
                    'filter' => 'all',
                ]),
                'icon' => $isTechnician ? 'bi-tools' : 'bi-person-check',
            ];
        })->all();

        $firstPendingRoute = collect($items)
            ->first(fn (array $item): bool => $item['count'] > 0)['route'] ?? route('admin.tasks.index');

        return [
            'total' => collect($items)->sum('count'),
            'route' => $firstPendingRoute,
            'items' => $items,
        ];
    }

    /**
     * @return array{total: int, route: string, items: array<int, array<string, mixed>>}
     */
    private function forAdvisor(User $user): array
    {
        $count = $this->countPropertyTasks($this->advisorPropertyIds($user));
        $route = route('advisor.tasks.index', ['range' => 'today', 'filter' => 'all']);

        return [
            'total' => $count,
            'route' => $route,
            'items' => [[
                'title' => 'Mis pendientes',
                'subtitle' => 'Cobranza, tickets y vencimientos',
                'count' => $count,
                'route' => $route,
                'icon' => 'bi-list-check',
            ]],
        ];
    }

    /**
     * @return array{total: int, route: string, items: array<int, array<string, mixed>>}
     */
    private function forTechnician(User $user): array
    {
        $count = $this->countTechnicianTasks($user);
        $route = route('maintenance.index');

        return [
            'total' => $count,
            'route' => $route,
            'items' => [[
                'title' => 'Tickets asignados',
                'subtitle' => 'Visitas y trabajos por atender',
                'count' => $count,
                'route' => $route,
                'icon' => 'bi-tools',
            ]],
        ];
    }

    /**
     * @return array{total: int, route: string, items: array<int, array<string, mixed>>}
     */
    private function forTenant(User $user): array
    {
        $propertyIds = Property::query()
            ->whereHas('tenant', fn ($query) => $query->where('email', $user->email))
            ->pluck('id');
        $activeStatuses = array_diff(array_keys(MaintenanceTicket::STATUS_LABELS), ['completado', 'cancelado']);
        $count = $propertyIds->isEmpty()
            ? 0
            : MaintenanceTicket::query()
                ->whereIn('property_id', $propertyIds)
                ->whereIn('status', $activeStatuses)
                ->count();
        $route = route('maintenance.index');

        return [
            'total' => $count,
            'route' => $route,
            'items' => [[
                'title' => 'Mis tickets',
                'subtitle' => 'Solicitudes de mantenimiento activas',
                'count' => $count,
                'route' => $route,
                'icon' => 'bi-tools',
            ]],
        ];
    }

    private function countPropertyTasks(Collection $propertyIds): int
    {
        if ($propertyIds->isEmpty()) {
            return 0;
        }

        $ids = $propertyIds->all();
        $today = now()->toDateString();
        $endOfToday = now()->endOfDay();
        $activeMaintenanceStatuses = array_diff(array_keys(MaintenanceTicket::STATUS_LABELS), ['completado', 'cancelado']);

        return Charge::query()
            ->whereIn('property_id', $ids)
            ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
            ->whereDate('due_date', '<=', $today)
            ->count()
            + MaintenanceTicket::query()
                ->whereIn('property_id', $ids)
                ->whereIn('status', $activeMaintenanceStatuses)
                ->whereNotNull('scheduled_visit_at')
                ->where('scheduled_visit_at', '<=', $endOfToday)
                ->count()
            + Property::query()
                ->whereIn('id', $ids)
                ->whereNotNull('contract_expires_at')
                ->whereDate('contract_expires_at', '<=', $today)
                ->count()
            + PropertyDocument::query()
                ->whereIn('property_id', $ids)
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', $today)
                ->count()
            + TenantDocument::query()
                ->whereHas('tenant.properties', fn ($query) => $query->whereIn('properties.id', $ids))
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', $today)
                ->count();
    }

    private function countTechnicianTasks(User $user): int
    {
        $providerIds = MaintenanceProvider::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        if ($providerIds->isEmpty()) {
            return 0;
        }

        $activeStatuses = array_diff(array_keys(MaintenanceTicket::STATUS_LABELS), ['completado', 'cancelado']);

        return MaintenanceTicket::query()
            ->whereIn('current_provider_id', $providerIds)
            ->whereIn('status', $activeStatuses)
            ->where(function ($query): void {
                $query->whereNull('scheduled_visit_at')
                    ->orWhere('scheduled_visit_at', '<=', now()->endOfDay());
            })
            ->count();
    }

    private function advisorPropertyIds(User $user): Collection
    {
        return $user->advisorProperties()
            ->select('properties.id')
            ->pluck('properties.id')
            ->merge(Property::query()->where('advisor_user_id', $user->id)->pluck('id'))
            ->unique()
            ->values();
    }

    /**
     * @return array{total: int, route: string, items: array<int, array<string, mixed>>}
     */
    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'route' => route('dashboard'),
            'items' => [],
        ];
    }
}
