<?php
namespace App\Http\Controllers;

use App\Models\MaintenanceCut;
use App\Models\MaintenanceTicket;
use App\Services\MaintenanceCutReportData;
use App\Services\MaintenanceCutReportExcelExporter;
use App\Services\MaintenanceCutReportPdfExporter;
use App\Services\MaintenanceTicketReportData;
use App\Services\MaintenanceTicketReportExcelExporter;
use App\Services\MaintenanceTicketReportPdfExporter;
use App\Support\PropertyVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MaintenanceReportController extends Controller
{
    private function ensureAdministrator(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['administrador', 'admin']) && ! app(PropertyVisibility::class)->shouldLimitToOwnProperties($request->user()), 403);
    }

    public function tickets(Request $request, string $format)
    {
        $this->ensureAdministrator($request);
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);
        $f = $request->validate([
            'technician_ids' => ['nullable', 'array'], 'technician_ids.*' => ['integer', Rule::exists('maintenance_providers', 'id')->where('type', 'tecnico_interno')],
            'statuses' => ['nullable', 'array'], 'statuses.*' => [Rule::in(array_keys(MaintenanceTicket::STATUS_LABELS))],
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort_completed' => ['nullable', Rule::in(['completed_desc', 'completed_asc'])],
        ]);
        $tickets = MaintenanceTicket::query()->with(['property:id,internal_name,internal_reference', 'currentProvider:id,name', 'cutItem:id,maintenance_cut_id,ticket_id,labor_total,material_total,grand_total', 'cutItem.cut:id,uuid,paid_at,grand_total'])
            ->when(!empty($f['technician_ids']), fn ($q) => $q->whereIn('current_provider_id', $f['technician_ids']))
            ->when(!empty($f['statuses']), fn ($q) => $q->whereIn('status', $f['statuses']))
            ->when(!empty($f['date_from']), fn ($q) => $q->whereDate('completed_at', '>=', $f['date_from']))
            ->when(!empty($f['date_to']), fn ($q) => $q->whereDate('completed_at', '<=', $f['date_to']))
            ->orderByRaw('completed_at IS NULL')->orderBy('completed_at', ($f['sort_completed'] ?? '') === 'completed_asc' ? 'asc' : 'desc')->orderBy('id')->get();
        $names = \App\Models\MaintenanceProvider::query()->whereIn('id', $f['technician_ids'] ?? [])->orderBy('name')->pluck('name');
        $statuses = collect($f['statuses'] ?? [])->map(fn ($status) => MaintenanceTicket::STATUS_LABELS[$status]);
        $summary = 'Técnicos: '.($names->isEmpty() ? 'todos' : $names->implode(', ')).' · Estados: '.($statuses->isEmpty() ? 'todos' : $statuses->implode(', ')).' · Fecha de finalización: '.($f['date_from'] ?? 'sin inicio').' a '.($f['date_to'] ?? 'sin fin');
        $data = app(MaintenanceTicketReportData::class); $now = now();
        return $this->response($format, app(MaintenanceTicketReportPdfExporter::class), app(MaintenanceTicketReportExcelExporter::class), $data->rows($tickets), $summary, $data->indicators($tickets), 'tickets_mantenimiento', $now);
    }

    public function cuts(Request $request, string $format)
    {
        $this->ensureAdministrator($request);
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);
        $f = $request->validate([
            'provider_ids' => ['nullable', 'array'], 'provider_ids.*' => ['integer', Rule::exists('maintenance_providers', 'id')],
            'property_ids' => ['nullable', 'array'], 'property_ids.*' => ['integer', Rule::exists('properties', 'id')],
            'search' => ['nullable', 'string', 'max:100'], 'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort_paid' => ['nullable', Rule::in(['paid_desc', 'paid_asc'])],
        ]);
        $cuts = MaintenanceCut::query()->with(['paidBy:id,name', 'items.ticket:id,current_provider_id', 'items.ticket.currentProvider:id,name'])
            ->when(!empty($f['provider_ids']), fn ($q) => $q->whereHas('items.ticket', fn ($ticket) => $ticket->whereIn('current_provider_id', $f['provider_ids'])))
            ->when(!empty($f['property_ids']), fn ($q) => $q->whereHas('items.ticket', fn ($ticket) => $ticket->where(fn ($t) => $t->whereIn('property_id', $f['property_ids'])->orWhereHas('childTickets', fn ($child) => $child->whereIn('property_id', $f['property_ids'])))))
            ->when(!empty($f['date_from']), fn ($q) => $q->whereDate('paid_at', '>=', $f['date_from']))
            ->when(!empty($f['date_to']), fn ($q) => $q->whereDate('paid_at', '<=', $f['date_to']))
            ->when(!empty($f['search']), fn ($q) => $q->whereHas('items.ticket', fn ($ticket) => $ticket->where(fn ($t) => $t->where('title', 'like', '%'.$f['search'].'%')->orWhere('reference', 'like', '%'.$f['search'].'%'))))
            ->orderBy('paid_at', ($f['sort_paid'] ?? '') === 'paid_asc' ? 'asc' : 'desc')->orderBy('id')->get();
        $names = \App\Models\MaintenanceProvider::query()->whereIn('id', $f['provider_ids'] ?? [])->orderBy('name')->pluck('name');
        $properties = \App\Models\Property::query()->whereIn('id', $f['property_ids'] ?? [])->orderBy('internal_name')->pluck('internal_name');
        $summary = 'Proveedores: '.($names->isEmpty() ? 'todos' : $names->implode(', ')).' · Propiedades: '.($properties->isEmpty() ? 'todas' : $properties->implode(', ')).' · Fecha de pago: '.($f['date_from'] ?? 'sin inicio').' a '.($f['date_to'] ?? 'sin fin').(!empty($f['search']) ? ' · Búsqueda: '.$f['search'] : '');
        $data = app(MaintenanceCutReportData::class); $now = now();
        return $this->response($format, app(MaintenanceCutReportPdfExporter::class), app(MaintenanceCutReportExcelExporter::class), $data->rows($cuts), $summary, $data->indicators($cuts), 'cortes_mantenimiento', $now);
    }

    private function response(string $format, object $pdfExporter, object $excelExporter, array $rows, string $summary, ?array $indicators, string $name, $now)
    {
        if ($format === 'pdf') {
            return response($pdfExporter->generate($rows, $summary, $now, $indicators), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="reporte_'.$name.'_'.$now->format('Ymd_His').'.pdf"']);
        }
        $spreadsheet = $excelExporter->generate($rows, $summary, $now, $indicators);
        return response()->streamDownload(function () use ($spreadsheet): void {
            try { (new Xlsx($spreadsheet))->save('php://output'); } finally { $spreadsheet->disconnectWorksheets(); }
        }, 'reporte_'.$name.'_'.$now->format('Ymd_His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
