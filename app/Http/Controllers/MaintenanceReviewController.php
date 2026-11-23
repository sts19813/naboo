<?php
namespace App\Http\Controllers;

use App\Models\MaintenanceTicket;
use App\Models\MaintenanceTicketValidation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MaintenanceReviewController extends Controller
{
    private function visibleTickets(Request $request): Builder
    {
        $user = $request->user();
        abort_unless($user?->hasRole('revisor de tickets') && $user->can('mantenimiento.consultar_tickets'), 403);
        $range = $user->maintenanceReviewerAccess?->ticketDateRange();
        return MaintenanceTicket::query()->where('status', 'completado')->when($range, fn (Builder $query) => $query->whereBetween('reported_at', $range), fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    public function index(Request $request): View
    {
        $tickets = $this->visibleTickets($request)->with(['property:id,internal_name', 'currentProvider:id,name', 'validation.validatedBy:id,name'])
            ->latest('reported_at')->latest('id')->paginate(20);
        return view('maintenance.review-index', ['tickets' => $tickets, 'range' => $request->user()->maintenanceReviewerAccess?->ticketDateRange()]);
    }

    public function show(Request $request, MaintenanceTicket $maintenance): View
    {
        $ticket = $this->visibleTickets($request)->with(['property:id,internal_name', 'currentProvider:id,name', 'files', 'costs.expense.files', 'validation.validatedBy:id,name'])
            ->whereKey($maintenance->id)->first();
        abort_unless($ticket, 403);
        return view('maintenance.review-show', compact('ticket'));
    }

    public function validateTickets(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('mantenimiento.validar_tickets'), 403);
        $data = $request->validate(['ticket_uuids' => ['required', 'array', 'min:1', 'max:100'], 'ticket_uuids.*' => ['required', 'uuid', 'distinct']]);
        DB::transaction(function () use ($request, $data): void {
            $tickets = $this->visibleTickets($request)->whereIn('uuid', $data['ticket_uuids'])->lockForUpdate()->get(['id']);
            abort_unless($tickets->count() === count($data['ticket_uuids']), 403);
            foreach ($tickets as $ticket) {
                MaintenanceTicketValidation::firstOrCreate(['ticket_id' => $ticket->id], ['validated_by_user_id' => $request->user()->id, 'validated_at' => now()]);
            }
        });
        return back()->with('success', 'Validación registrada correctamente.');
    }
}
