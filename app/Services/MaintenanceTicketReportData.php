<?php

namespace App\Services;

use App\Models\MaintenanceTicket;
use Illuminate\Support\Collection;

class MaintenanceTicketReportData
{
    /**
     * @param  Collection<int, MaintenanceTicket>  $tickets
     * @return array<int, array<string, string>>
     */
    public function rows(Collection $tickets): array
    {
        return $tickets
            ->map(function (MaintenanceTicket $ticket): array {
                return [
                    'folio' => $ticket->reference ?: '#'.$ticket->id,
                    'property' => trim(implode(' · ', array_filter([
                        $ticket->property?->internal_name ?: 'Sin propiedad',
                        $ticket->property?->internal_reference,
                    ]))),
                    'title' => $ticket->title ?: 'Sin título',
                    'technician' => $ticket->currentProvider?->name ?: 'Sin asignar',
                    'status' => $ticket->status === 'completado' && $ticket->cutItem?->cut
                        ? 'Completado - Pagado'
                        : (MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status),
                    'created_at' => $ticket->reported_at?->format('d/m/Y') ?: '-',
                    'completed_at' => $ticket->completed_at?->format('d/m/Y') ?: '-',
                    'cut' => $ticket->cutItem?->cut
                        ? $ticket->cutItem->cut->display_reference."\n"
                            .$ticket->cutItem->cut->paid_at?->format('d/m/Y')."\n$"
                            .number_format((float) $ticket->cutItem->grand_total, 2)
                        : '-',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, MaintenanceTicket>  $tickets
     * @return array<int, array{label: string, value: string}>|null
     */
    public function indicators(Collection $tickets): ?array
    {
        $completedTickets = $tickets
            ->filter(fn (MaintenanceTicket $ticket): bool => $ticket->status === 'completado')
            ->values();
        $paidTickets = $completedTickets
            ->filter(fn (MaintenanceTicket $ticket): bool => $ticket->cutItem?->cut !== null)
            ->values();

        if ($paidTickets->isEmpty()) {
            return null;
        }

        $laborPaid = (float) $paidTickets->sum(fn (MaintenanceTicket $ticket): float => (float) ($ticket->cutItem?->labor_total ?? 0));
        $materialsPaid = (float) $paidTickets->sum(fn (MaintenanceTicket $ticket): float => (float) ($ticket->cutItem?->material_total ?? 0));
        $totalPaid = (float) $paidTickets->sum(fn (MaintenanceTicket $ticket): float => (float) ($ticket->cutItem?->grand_total ?? 0));

        return [
            ['label' => 'Total de tickets pagados', 'value' => (string) $paidTickets->count()],
            ['label' => 'Mano de obra pagada', 'value' => '$'.number_format($laborPaid, 2)],
            ['label' => 'Materiales pagados', 'value' => '$'.number_format($materialsPaid, 2)],
            ['label' => 'Total pagado', 'value' => '$'.number_format($totalPaid, 2)],
        ];
    }
}
