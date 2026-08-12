<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceSettlement;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaintenanceSettlementController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCanManageSettlements($request->user());

        $pendingTickets = $this->pendingTicketsQuery()
            ->with(['property:id,uuid,internal_name,internal_reference', 'currentProvider:id,name'])
            ->withSum('costs as labor_total', 'labor_cost')
            ->withSum('costs as material_total', 'material_cost')
            ->withSum('costs as advance_total', 'advance_cost')
            ->withSum('costs as final_total', 'final_cost')
            ->latest('reported_at')
            ->get();

        $settlements = MaintenanceSettlement::query()
            ->with('creator:id,name,email')
            ->latest('settled_at')
            ->latest('id')
            ->paginate(12);

        return view('maintenance.settlements.index', [
            'pendingTickets' => $pendingTickets,
            'settlements' => $settlements,
            'pendingTotals' => $this->totalsFromTickets($pendingTickets),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManageSettlements($request->user());

        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'distinct'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $settlement = DB::transaction(function () use ($request, $validated): MaintenanceSettlement {
            $tickets = $this->pendingTicketsQuery()
                ->whereIn('id', $validated['ticket_ids'])
                ->with(['costs.expense', 'property:id,internal_name'])
                ->lockForUpdate()
                ->get();

            if ($tickets->count() !== count($validated['ticket_ids'])) {
                throw ValidationException::withMessages([
                    'ticket_ids' => 'Algunos tickets ya fueron liquidados o no tienen costos pendientes para corte.',
                ]);
            }

            $totals = $this->totalsFromTickets($tickets);
            $settledAt = now();

            $settlement = MaintenanceSettlement::create([
                'reference' => $this->nextReference(),
                'created_by_user_id' => $request->user()?->id,
                'status' => MaintenanceSettlement::STATUS_SETTLED,
                'total_tickets' => $tickets->count(),
                'total_labor_cost' => $totals['labor'],
                'total_material_cost' => $totals['material'],
                'total_advance_cost' => $totals['advance'],
                'total_amount' => $totals['final'],
                'currency' => 'MXN',
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
                'settled_at' => $settledAt,
            ]);

            foreach ($tickets as $ticket) {
                $ticketTotals = $this->totalsFromTicket($ticket);

                $settlement->tickets()->attach($ticket->id, [
                    'labor_cost' => $ticketTotals['labor'],
                    'material_cost' => $ticketTotals['material'],
                    'advance_cost' => $ticketTotals['advance'],
                    'final_cost' => $ticketTotals['final'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $ticket->costs->each(function ($cost) use ($settledAt): void {
                    if ($cost->expense && $cost->expense->paid_at === null) {
                        $cost->expense->forceFill(['paid_at' => $settledAt])->save();
                    }
                });

                $ticket->forceFill([
                    'settlement_status' => MaintenanceTicket::SETTLEMENT_STATUS_SETTLED,
                    'settled_at' => $settledAt,
                ])->save();
            }

            return $settlement;
        });

        return redirect()
            ->route('maintenance.settlements.show', $settlement)
            ->with('success', 'Corte generado y tickets liquidados correctamente.');
    }

    public function show(Request $request, MaintenanceSettlement $settlement): View
    {
        $this->ensureCanManageSettlements($request->user());

        $settlement->load([
            'creator:id,name,email',
            'tickets.property:id,uuid,internal_name,internal_reference',
            'tickets.currentProvider:id,name',
            'tickets.costs.expense.files',
        ]);

        return view('maintenance.settlements.show', [
            'settlement' => $settlement,
        ]);
    }

    private function ensureCanManageSettlements(?User $user): void
    {
        if (! $user || ! ($user->hasRole('administrador') || $user->hasRole('admin'))) {
            abort(403);
        }
    }

    private function pendingTicketsQuery(): Builder
    {
        return MaintenanceTicket::query()
            ->where('settlement_status', MaintenanceTicket::SETTLEMENT_STATUS_PENDING)
            ->whereHas('costs', fn (Builder $query) => $query->where('final_cost', '>', 0));
    }

    private function totalsFromTickets($tickets): array
    {
        return $tickets->reduce(function (array $totals, MaintenanceTicket $ticket): array {
            $ticketTotals = $this->totalsFromTicket($ticket);

            return [
                'labor' => $totals['labor'] + $ticketTotals['labor'],
                'material' => $totals['material'] + $ticketTotals['material'],
                'advance' => $totals['advance'] + $ticketTotals['advance'],
                'final' => $totals['final'] + $ticketTotals['final'],
            ];
        }, ['labor' => 0.0, 'material' => 0.0, 'advance' => 0.0, 'final' => 0.0]);
    }

    private function totalsFromTicket(MaintenanceTicket $ticket): array
    {
        $costs = $ticket->relationLoaded('costs') ? $ticket->costs : $ticket->costs()->get();

        return [
            'labor' => round((float) $costs->sum('labor_cost'), 2),
            'material' => round((float) $costs->sum('material_cost'), 2),
            'advance' => round((float) $costs->sum('advance_cost'), 2),
            'final' => round((float) $costs->sum('final_cost'), 2),
        ];
    }

    private function nextReference(): string
    {
        $prefix = 'CORTE-MTTO-'.now()->format('Ym').'-';
        $lastReference = MaintenanceSettlement::query()
            ->where('reference', 'like', $prefix.'%')
            ->latest('id')
            ->value('reference');
        $next = $lastReference
            ? ((int) Str::afterLast((string) $lastReference, '-')) + 1
            : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
