<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceCut;
use App\Models\MaintenanceTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaintenanceCutController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdministrator($request);

        $tickets = MaintenanceTicket::query()
            ->where('status', 'completado')
            ->whereDoesntHave('cutItem')
            ->with('property:id,uuid,internal_name,internal_reference')
            ->withSum('costs as labor_total', 'labor_cost')
            ->withSum('costs as material_total', 'material_cost')
            ->withSum('costs as grand_total', 'final_cost')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get();

        $cuts = MaintenanceCut::query()
            ->with([
                'paidBy:id,name,email',
                'items' => fn ($query) => $query->orderBy('id'),
                'items.ticket:id,uuid,property_id,reference,title,reported_at,completed_at,created_at',
                'items.ticket.property:id,uuid,internal_name,internal_reference',
            ])
            ->latest('paid_at')
            ->latest('id')
            ->paginate(12);

        return view('maintenance.cuts.index', [
            'tickets' => $tickets,
            'cuts' => $cuts,
            'pendingTotals' => $this->totalsFor($tickets),
            'paidGrandTotal' => (float) MaintenanceCut::query()->sum('grand_total'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdministrator($request);

        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['required', 'integer', 'distinct', 'exists:maintenance_tickets,id'],
        ], [
            'ticket_ids.required' => 'Selecciona al menos un ticket para generar el pago.',
            'ticket_ids.min' => 'Selecciona al menos un ticket para generar el pago.',
        ]);

        $ticketIds = collect($validated['ticket_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $cut = DB::transaction(function () use ($ticketIds, $request): MaintenanceCut {
            $tickets = MaintenanceTicket::query()
                ->whereIn('id', $ticketIds)
                ->where('status', 'completado')
                ->whereDoesntHave('cutItem')
                ->with('costs:id,ticket_id,labor_cost,material_cost,final_cost')
                ->lockForUpdate()
                ->get();

            if ($tickets->count() !== $ticketIds->count()) {
                throw ValidationException::withMessages([
                    'ticket_ids' => 'Uno o más tickets ya fueron pagados o dejaron de estar completados. Actualiza la página e inténtalo nuevamente.',
                ]);
            }

            $rows = $tickets->map(function (MaintenanceTicket $ticket): array {
                $labor = round((float) $ticket->costs->sum('labor_cost'), 2);
                $materials = round((float) $ticket->costs->sum('material_cost'), 2);
                $total = round((float) $ticket->costs->sum('final_cost'), 2);

                return [
                    'ticket_id' => $ticket->id,
                    'labor_total' => $labor,
                    'material_total' => $materials,
                    'grand_total' => $total,
                ];
            });
            $totals = $this->totalsFor($rows);

            $cut = MaintenanceCut::create([
                'paid_by_user_id' => $request->user()?->id,
                'ticket_count' => $rows->count(),
                'labor_total' => $totals['labor'],
                'material_total' => $totals['materials'],
                'grand_total' => $totals['grand'],
                'paid_at' => now(),
            ]);
            $cut->items()->createMany($rows->all());

            return $cut;
        });

        return redirect()
            ->route('maintenance-cuts.index')
            ->with('success', "{$cut->display_reference} pagado correctamente con {$cut->ticket_count} ticket(s).");
    }

    private function ensureAdministrator(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasRole('administrador') && ! $user->hasRole('admin'))) {
            abort(403);
        }
    }

    private function totalsFor(Collection $rows): array
    {
        return [
            'labor' => round((float) $rows->sum(fn ($row) => (float) (data_get($row, 'labor_total') ?? 0)), 2),
            'materials' => round((float) $rows->sum(fn ($row) => (float) (data_get($row, 'material_total') ?? 0)), 2),
            'grand' => round((float) $rows->sum(fn ($row) => (float) (data_get($row, 'grand_total') ?? 0)), 2),
        ];
    }
}
