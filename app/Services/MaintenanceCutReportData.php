<?php

namespace App\Services;

use App\Models\MaintenanceCut;
use Illuminate\Support\Collection;

class MaintenanceCutReportData
{
    /**
     * @param  Collection<int, MaintenanceCut>  $cuts
     * @return array<int, array<string, string>>
     */
    public function rows(Collection $cuts): array
    {
        return $cuts
            ->map(fn (MaintenanceCut $cut): array => [
                'reference' => $cut->display_reference,
                'provider' => $this->providerName($cut),
                'paid_at' => $cut->paid_at?->format('d/m/Y H:i') ?: '-',
                'registered_by' => $cut->paidBy?->name ?? 'Usuario eliminado',
                'ticket_count' => (string) $cut->ticket_count,
                'labor_total' => '$'.number_format((float) $cut->labor_total, 2),
                'material_total' => '$'.number_format((float) $cut->material_total, 2),
                'grand_total' => '$'.number_format((float) $cut->grand_total, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, MaintenanceCut>  $cuts
     * @return array<int, array{label: string, value: string}>
     */
    public function indicators(Collection $cuts): array
    {
        return [
            ['label' => 'Cortes pagados', 'value' => (string) $cuts->count()],
            ['label' => 'Tickets pagados', 'value' => (string) $cuts->sum('ticket_count')],
            ['label' => 'Mano de obra', 'value' => '$'.number_format((float) $cuts->sum('labor_total'), 2)],
            ['label' => 'Materiales', 'value' => '$'.number_format((float) $cuts->sum('material_total'), 2)],
            ['label' => 'Total pagado', 'value' => '$'.number_format((float) $cuts->sum('grand_total'), 2)],
        ];
    }

    public function providerName(MaintenanceCut $cut): string
    {
        $names = $cut->items
                ->map(fn ($item) => $item->ticket?->currentProvider?->name)
                ->filter()
                ->unique()->values();
        return $names->isEmpty() ? 'Sin técnico asignado' : $names->implode(', ');
    }
}
