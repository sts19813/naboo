<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceTicketRequest;
use App\Http\Requests\UpdateMaintenanceTicketRequest;
use App\Mail\MaintenanceTicketEventMail;
use App\Models\Expense;
use App\Models\ExpenseFile;
use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\MaintenanceTicketAssignment;
use App\Models\MaintenanceTicketCost;
use App\Models\MaintenanceTicketFile;
use App\Models\MaintenanceTicketMessage;
use App\Models\MaintenanceTicketNotification;
use App\Models\Property;
use App\Models\User;
use App\Support\NotificationSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class MaintenanceController extends Controller
{
    private const MANAGE_TECHNICIANS_PERMISSION = 'administracion de tecnicos';

    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'property' => ['nullable', 'string', 'exists:properties,uuid'],
            'tab' => ['nullable', Rule::in(['activos', 'completados', 'cancelados'])],
            'status' => ['nullable', Rule::in(array_merge([''], array_keys(MaintenanceTicket::STATUS_LABELS)))],
            'priority' => ['nullable', Rule::in(array_merge([''], array_keys(MaintenanceTicket::PRIORITY_LABELS)))],
            'category' => ['nullable', Rule::in(array_merge([''], array_keys(MaintenanceTicket::CATEGORY_LABELS)))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));
        $propertyUuid = trim((string) ($filters['property'] ?? ''));
        $status = (string) ($filters['status'] ?? '');
        $priority = (string) ($filters['priority'] ?? '');
        $category = (string) ($filters['category'] ?? '');
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $activeTab = (string) ($filters['tab'] ?? 'activos');
        $activeStatuses = ['pendiente', 'revisado', 'asignado', 'programado', 'en_proceso', 'esperando_material', 'reabierto'];
        $pendingStatuses = ['pendiente', 'revisado', 'asignado', 'programado', 'esperando_material', 'reabierto'];
        $tabStatuses = match ($activeTab) {
            'completados' => ['completado'],
            'cancelados' => ['cancelado'],
            default => $activeStatuses,
        };

        $properties = $this->accessiblePropertiesQuery($user, $role)
            ->orderBy('internal_name')
            ->get(['id', 'uuid', 'internal_name', 'internal_reference']);
        $selectedProperty = $propertyUuid !== '' ? $properties->firstWhere('uuid', $propertyUuid) : null;
        $selectedPropertyId = $selectedProperty?->id;

        $baseQuery = $this->visibleTicketsQuery($user, $role)
            ->with([
                'property:id,uuid,internal_name,internal_reference',
                'reporter:id,name,email',
                'currentProvider:id,uuid,user_id,name,type,email,phone,specialty,rating,availability',
            ])
            ->withCount(['files', 'messages']);

        $ticketsQuery = (clone $baseQuery)
            ->when($selectedPropertyId, fn(Builder $query) => $query->where('property_id', $selectedPropertyId))
            ->whereIn('status', $tabStatuses)
            ->when($status !== '', fn(Builder $query) => $query->where('status', $status))
            ->when($priority !== '', fn(Builder $query) => $query->where('priority', $priority))
            ->when($category !== '', fn(Builder $query) => $query->where('category', $category))
            ->when($from, fn(Builder $query) => $query->whereDate('reported_at', '>=', $from))
            ->when($to, fn(Builder $query) => $query->whereDate('reported_at', '<=', $to))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('property', function (Builder $propertyQuery) use ($search): void {
                            $propertyQuery
                                ->where('internal_name', 'like', "%{$search}%")
                                ->orWhere('internal_reference', 'like', "%{$search}%");
                        });
                });
            });

        $tickets = $ticketsQuery
            ->latest('reported_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $metricsBase = (clone $baseQuery)
            ->when($selectedPropertyId, fn(Builder $query) => $query->where('property_id', $selectedPropertyId));
        $totalCount = (clone $metricsBase)->count();
        $openCount = (clone $metricsBase)->whereIn('status', $activeStatuses)->count();
        $pendingCount = (clone $metricsBase)->whereIn('status', $pendingStatuses)->count();
        $urgentCount = (clone $metricsBase)->whereIn('status', $activeStatuses)->where('priority', 'urgente')->count();
        $inProgressCount = (clone $metricsBase)->where('status', 'en_proceso')->count();
        $completedCount = (clone $metricsBase)->where('status', 'completado')->count();
        $resolvedTickets = (clone $metricsBase)
            ->whereNotNull('completed_at')
            ->get(['reported_at', 'completed_at']);
        $avgResolutionHours = $resolvedTickets->isEmpty()
            ? null
            : $resolvedTickets
            ->map(function (MaintenanceTicket $ticket): float {
                return max(0, (float) $ticket->reported_at?->diffInMinutes($ticket->completed_at) / 60);
            })
            ->avg();
        $monthlyCost = (float) MaintenanceTicketCost::query()
            ->whereHas('ticket', function (Builder $query) use ($metricsBase): void {
                $query->whereIn('maintenance_tickets.id', (clone $metricsBase)->select('maintenance_tickets.id'));
            })
            ->whereYear('updated_at', now()->year)
            ->whereMonth('updated_at', now()->month)
            ->sum('final_cost');
        $topProperties = MaintenanceTicket::query()
            ->selectRaw('property_id, COUNT(*) as total')
            ->whereNotNull('property_id')
            ->whereIn('id', (clone $metricsBase)->select('maintenance_tickets.id'))
            ->groupBy('property_id')
            ->orderByDesc('total')
            ->with('property:id,uuid,internal_name,internal_reference')
            ->limit(5)
            ->get();
        $providerTickets = MaintenanceTicketAssignment::query()
            ->with([
                'provider:id,uuid,name,type,specialty,rating',
                'ticket:id,reported_at,completed_at',
            ])
            ->whereHas('ticket', function (Builder $query) use ($metricsBase): void {
                $query
                    ->whereNotNull('completed_at')
                    ->whereIn('maintenance_tickets.id', (clone $metricsBase)->select('maintenance_tickets.id'));
            })
            ->get();
        $topProviders = $providerTickets
            ->groupBy('provider_id')
            ->map(function ($rows) {
                $provider = $rows->first()?->provider;
                $hours = $rows
                    ->map(function (MaintenanceTicketAssignment $assignment): ?float {
                        $ticket = $assignment->ticket;
                        if (! $ticket?->reported_at || ! $ticket?->completed_at) {
                            return null;
                        }

                        return max(0, (float) $ticket->reported_at->diffInMinutes($ticket->completed_at) / 60);
                    })
                    ->filter(fn($value) => $value !== null);

                return (object) [
                    'name' => $provider?->name ?? '-',
                    'type' => $provider?->type,
                    'specialty' => $provider?->specialty,
                    'rating' => $provider?->rating,
                    'total_tickets' => $rows->pluck('ticket_id')->unique()->count(),
                    'avg_hours' => $hours->isEmpty() ? null : (float) $hours->avg(),
                ];
            })
            ->sortByDesc('total_tickets')
            ->take(5)
            ->values();
        $calendarItems = (clone $metricsBase)
            ->whereNotNull('scheduled_visit_at')
            ->whereIn('status', $activeStatuses)
            ->with(['property:id,uuid,internal_name,internal_reference', 'currentProvider:id,name'])
            ->orderBy('scheduled_visit_at')
            ->limit(120)
            ->get();

        $providers = MaintenanceProvider::query()
            ->with('user:id,name,email')
            ->orderBy('name')
            ->get();
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('maintenance.index', [
            'tickets' => $tickets,
            'providers' => $providers,
            'users' => $users,
            'properties' => $properties,
            'selectedProperty' => $selectedProperty,
            'status' => $status,
            'priority' => $priority,
            'category' => $category,
            'activeTab' => $activeTab,
            'search' => $search,
            'dateFrom' => $from,
            'dateTo' => $to,
            'statusOptions' => ['' => 'Todos'] + MaintenanceTicket::STATUS_LABELS,
            'priorityOptions' => ['' => 'Todas'] + MaintenanceTicket::PRIORITY_LABELS,
            'categoryOptions' => ['' => 'Todas'] + MaintenanceTicket::CATEGORY_LABELS,
            'metrics' => [
                'total' => $totalCount,
                'open' => $openCount,
                'pending' => $pendingCount,
                'urgent' => $urgentCount,
                'in_progress' => $inProgressCount,
                'completed' => $completedCount,
                'avg_resolution_hours' => $avgResolutionHours ? round((float) $avgResolutionHours, 2) : null,
                'monthly_cost' => $monthlyCost,
                'month_label' => Carbon::create(now()->year, now()->month, 1)->translatedFormat('M Y'),
                'top_properties' => $topProperties,
                'top_providers' => $topProviders,
            ],
            'calendarItems' => $calendarItems,
            'role' => $role,
            'canCreateTicket' => in_array($role, ['administrador', 'inquilino', 'tecnico'], true),
            'canManageProviders' => $this->canManageTechnicians($user),
            'canManageAssignments' => $role === 'administrador',
            'canUpdateTicketMeta' => in_array($role, ['administrador', 'tecnico'], true),
            'canManageCosts' => $role === 'administrador',
            'isTenant' => $role === 'inquilino',
        ]);
    }

    public function technicians(Request $request): View
    {
        $this->ensureCanManageTechnicians($request);

        $providers = MaintenanceProvider::query()
            ->with('user:id,name,email')
            ->orderBy('name')
            ->get();
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('maintenance.technicians', [
            'providers' => $providers,
            'users' => $users,
        ]);
    }

    public function store(StoreMaintenanceTicketRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        if (! in_array($role, ['administrador', 'inquilino', 'tecnico'], true)) {
            abort(403);
        }

        $validated = $request->validated();
        $property = $this->accessiblePropertiesQuery($user, $role)
            ->where('id', (int) $validated['property_id'])
            ->firstOrFail();
        $status = $role === 'administrador' && filled($validated['status'] ?? null)
            ? (string) $validated['status']
            : 'pendiente';
        $providerId = filled($validated['provider_id'] ?? null) ? (int) $validated['provider_id'] : null;
        $scheduledVisitAt = filled($validated['scheduled_visit_at'] ?? null)
            ? Carbon::parse((string) $validated['scheduled_visit_at'])
            : null;
        $forceConflict = $request->boolean('force_conflict');
        if ($providerId && $scheduledVisitAt) {
            $conflicts = $this->findTechnicianScheduleConflicts($providerId, $scheduledVisitAt);
            if ($conflicts !== []) {
                $message = $this->buildTechnicianConflictMessage($conflicts);
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'requires_confirmation' => true,
                        'message' => $message,
                        'conflicts' => $conflicts,
                    ], 422);
                }
                if (! $forceConflict) {
                    return redirect()->back()->withInput()->with('error', $message);
                }
            }
        }

        $ticket = DB::transaction(function () use ($validated, $user, $role, $status, $property, $request, $providerId, $scheduledVisitAt): MaintenanceTicket {
            $category = (string) ($validated['category'] ?? 'sin_categoria');
            $priority = (string) ($validated['priority'] ?? 'sin_asignar');
            $description = trim((string) ($validated['description'] ?? ''));
            $ticket = MaintenanceTicket::create([
                'property_id' => $property->id,
                'reported_by_user_id' => $user?->id,
                'reported_by_role' => $role,
                'reported_by_name' => $user?->name,
                'category' => $category,
                'priority' => $priority,
                'status' => $status,
                'title' => trim((string) $validated['title']),
                'reference' => null,
                'exact_location' => filled($validated['exact_location'] ?? null) ? trim((string) $validated['exact_location']) : null,
                'description' => $description !== '' ? $description : trim((string) $validated['title']),
                'additional_notes' => filled($validated['additional_notes'] ?? null) ? trim((string) $validated['additional_notes']) : null,
                'reported_at' => $validated['reported_at'] ?? now(),
                'scheduled_visit_at' => $scheduledVisitAt,
                'payer' => $validated['payer'] ?? null,
                'payment_rule' => $validated['payment_rule'] ?? null,
                'payment_rule_notes' => filled($validated['payment_rule_notes'] ?? null) ? trim((string) $validated['payment_rule_notes']) : null,
            ]);
            $ticket->reference = str_pad((string) $ticket->id, 8, '0', STR_PAD_LEFT);
            $ticket->save();

            $this->applyOperationalTimestampsForStatus($ticket, $status, null, null);
            $ticket->statusHistory()->create([
                'changed_by_user_id' => $user?->id,
                'from_status' => null,
                'to_status' => $status,
                'notes' => null,
                'changed_at' => now(),
            ]);
            $this->storeTicketFiles($ticket, (array) $request->file('files', []), 'reporte', $user?->id);
            if ($providerId) {
                $this->applyProviderAssignment(
                    $ticket,
                    $providerId,
                    $user?->id,
                    'Asignación inicial',
                    $scheduledVisitAt
                );
            }

            return $ticket;
        });

        $this->notifyTicketEvent($ticket, 'nuevo_reporte', 'Nuevo reporte de mantenimiento');

        return redirect()
            ->route('maintenance.show', $ticket)
            ->with('success', 'Ticket de mantenimiento creado correctamente.');
    }

    public function show(Request $request, MaintenanceTicket $maintenance): View
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $this->ensureTicketVisible($maintenance, $user, $role);

        $maintenance->load([
            'property:id,uuid,tenant_id,internal_name,internal_reference,full_address,map_url,facade_photo_path,current_tenant_name',
            'property.tenant:id,full_name,phone_primary,email',
            'reporter:id,name,email',
            'currentProvider:id,uuid,user_id,name,type,email,phone,specialty,average_cost,rating,availability',
            'assignments.provider:id,uuid,user_id,name,type,email,phone,specialty,average_cost,rating,availability',
            'assignments.assignedBy:id,name,email',
            'files.uploader:id,name,email',
            'costs.expense.files',
            'statusHistory.changedBy:id,name,email',
            'messages.sender:id,name,email',
            'messages.recipient:id,name,email',
            'notifications',
        ]);

        $providers = MaintenanceProvider::query()
            ->where('is_active', true)
            ->with('user:id,name,email')
            ->orderBy('name')
            ->get();
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $properties = match ($role) {
            'administrador' => $this->accessiblePropertiesQuery($user, $role)
                ->orderBy('internal_name')
                ->get(['id', 'uuid', 'internal_name', 'internal_reference']),
            'tecnico' => Property::query()
                ->orderBy('internal_name')
                ->get(['id', 'uuid', 'internal_name', 'internal_reference']),
            default => collect(),
        };
        $statusOptions = $role === 'administrador'
            ? MaintenanceTicket::STATUS_LABELS
            : array_intersect_key(
                MaintenanceTicket::STATUS_LABELS,
                array_flip(['revisado', 'programado', 'en_proceso', 'esperando_material', 'completado', 'cancelado', 'reabierto'])
            );

        return view('maintenance.show', [
            'ticket' => $maintenance,
            'providers' => $providers,
            'users' => $users,
            'properties' => $properties,
            'role' => $role,
            'statusOptions' => $statusOptions,
            'priorityOptions' => MaintenanceTicket::PRIORITY_LABELS,
            'categoryOptions' => MaintenanceTicket::CATEGORY_LABELS,
            'payerOptions' => MaintenanceTicket::COST_PAYER_LABELS,
            'costPayerOptions' => MaintenanceTicket::COST_PAYER_LABELS,
            'paymentRuleOptions' => MaintenanceTicket::PAYMENT_RULE_LABELS,
            'messageChannels' => MaintenanceTicketMessage::CHANNEL_LABELS,
            'canManageAssignments' => in_array($role, ['administrador', 'tecnico'], true),
            'canManageCosts' => $role === 'administrador',
            'canEditTicket' => $role === 'administrador',
            'canChangeStatus' => in_array($role, ['administrador', 'tecnico'], true),
            'canQuickScheduleVisit' => in_array($role, ['administrador', 'tecnico'], true),
        ]);
    }

    public function update(UpdateMaintenanceTicketRequest $request, MaintenanceTicket $maintenance): RedirectResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $this->ensureTicketVisible($maintenance, $user, $role);
        if ($role !== 'administrador') {
            abort(403);
        }

        $validated = $request->validated();
        $property = $this->accessiblePropertiesQuery($user, $role)
            ->where('id', (int) $validated['property_id'])
            ->firstOrFail();
        $maintenance->update([
            'property_id' => $property->id,
            'category' => (string) $validated['category'],
            'priority' => (string) $validated['priority'],
            'title' => trim((string) $validated['title']),
            'reference' => $maintenance->reference ?: str_pad((string) $maintenance->id, 8, '0', STR_PAD_LEFT),
            'exact_location' => trim((string) $validated['exact_location']),
            'description' => trim((string) $validated['description']),
            'additional_notes' => filled($validated['additional_notes'] ?? null) ? trim((string) $validated['additional_notes']) : null,
            'reported_at' => $validated['reported_at'],
            'scheduled_visit_at' => $validated['scheduled_visit_at'] ?? null,
            'payer' => $validated['payer'] ?? null,
            'payment_rule' => $validated['payment_rule'] ?? null,
            'payment_rule_notes' => filled($validated['payment_rule_notes'] ?? null) ? trim((string) $validated['payment_rule_notes']) : null,
        ]);
        $this->storeTicketFiles($maintenance, (array) $request->file('files', []), 'reporte', $user?->id);

        return redirect()->back()->with('success', 'Ticket actualizado correctamente.');
    }

    public function changeStatus(Request $request, MaintenanceTicket $maintenance): RedirectResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $this->ensureTicketVisible($maintenance, $user, $role);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(MaintenanceTicket::STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $nextStatus = (string) $validated['status'];
        $fromStatus = (string) $maintenance->status;

        if (! in_array($role, ['administrador', 'tecnico'], true)) {
            abort(403);
        }
        if ($role !== 'administrador') {
            $allowed = ['revisado', 'programado', 'en_proceso', 'esperando_material', 'completado', 'cancelado', 'reabierto'];
            if (! in_array($nextStatus, $allowed, true)) {
                abort(403);
            }
        }

        if ($fromStatus === $nextStatus) {
            return redirect()->back();
        }

        DB::transaction(function () use ($maintenance, $nextStatus, $fromStatus, $validated, $user): void {
            $maintenance->status = $nextStatus;
            $this->applyOperationalTimestampsForStatus(
                $maintenance,
                $nextStatus,
                $fromStatus,
                filled($validated['notes'] ?? null) ? (string) $validated['notes'] : null
            );
            $maintenance->save();

            $maintenance->statusHistory()->create([
                'changed_by_user_id' => $user?->id,
                'from_status' => $fromStatus,
                'to_status' => $nextStatus,
                'notes' => $validated['notes'] ?? null,
                'changed_at' => now(),
            ]);
        });

        $event = $nextStatus === 'completado' ? 'cierre' : 'cambio_estado';
        $subject = $nextStatus === 'completado'
            ? 'Ticket de mantenimiento completado'
            : 'Cambio de estado en ticket de mantenimiento';
        $this->notifyTicketEvent($maintenance, $event, $subject);

        return redirect()->back()->with('success', 'Estado actualizado correctamente.');
    }

    public function updateMeta(Request $request, MaintenanceTicket $maintenance): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $this->ensureTicketVisible($maintenance, $user, $role);
        if (! in_array($role, ['administrador', 'tecnico'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'category' => ['nullable', Rule::in(array_keys(MaintenanceTicket::CATEGORY_LABELS))],
            'priority' => ['nullable', Rule::in(array_keys(MaintenanceTicket::PRIORITY_LABELS))],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'provider_id' => ['nullable', 'integer', 'exists:maintenance_providers,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'scheduled_visit_at' => ['nullable', 'date'],
            'force_conflict' => ['nullable', 'boolean'],
        ]);

        $updates = [];
        if (filled($validated['category'] ?? null)) {
            $updates['category'] = (string) $validated['category'];
        }
        if (filled($validated['priority'] ?? null)) {
            $updates['priority'] = (string) $validated['priority'];
        }
        if (filled($validated['property_id'] ?? null)) {
            $property = $role === 'tecnico'
                ? Property::query()->where('id', (int) $validated['property_id'])->firstOrFail()
                : $this->accessiblePropertiesQuery($user, $role)
                ->where('id', (int) $validated['property_id'])
                ->firstOrFail();
            $updates['property_id'] = $property->id;
        }
        if (array_key_exists('scheduled_visit_at', $validated)) {
            $updates['scheduled_visit_at'] = filled($validated['scheduled_visit_at'] ?? null)
                ? Carbon::parse((string) $validated['scheduled_visit_at'])
                : null;
        }
        $providerProvided = array_key_exists('provider_id', $validated);
        $nextProviderId = filled($validated['provider_id'] ?? null) ? (int) $validated['provider_id'] : null;
        $providerChanged = $providerProvided && $nextProviderId !== ($maintenance->current_provider_id ? (int) $maintenance->current_provider_id : null);
        $providerIdForConflict = $providerChanged
            ? (int) ($nextProviderId ?? 0)
            : (int) ($maintenance->current_provider_id ?? 0);
        $scheduledAtForConflict = $updates['scheduled_visit_at'] ?? $maintenance->scheduled_visit_at;
        if ($providerIdForConflict > 0 && $scheduledAtForConflict) {
            $conflicts = $this->findTechnicianScheduleConflicts(
                $providerIdForConflict,
                Carbon::parse((string) $scheduledAtForConflict),
                $maintenance->id
            );
            if ($conflicts !== [] && ! $request->boolean('force_conflict')) {
                $message = $this->buildTechnicianConflictMessage($conflicts);
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'requires_confirmation' => true,
                        'message' => $message,
                        'conflicts' => $conflicts,
                    ], 422);
                }

                return redirect()->back()->with('error', $message);
            }
        }

        if ($updates === [] && ! $providerChanged) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sin cambios por guardar.',
                ]);
            }

            return redirect()->back();
        }

        DB::transaction(function () use ($maintenance, $updates, $providerChanged, $nextProviderId, $validated, $user): void {
            if ($updates !== []) {
                $maintenance->update($updates);
            }
            if ($providerChanged) {
                if ($nextProviderId) {
                    $this->applyProviderAssignment(
                        $maintenance,
                        $nextProviderId,
                        $user?->id,
                        $validated['notes'] ?? 'Asignación rápida desde ticket',
                        $validated['scheduled_visit_at'] ?? null
                    );
                } else {
                    $maintenance->assignments()
                        ->where('is_current', true)
                        ->update([
                            'is_current' => false,
                            'unassigned_at' => now(),
                        ]);
                    $maintenance->current_provider_id = null;
                    $maintenance->save();
                }
            }
        });

        $maintenance->refresh()->load('currentProvider:id,name');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados correctamente.',
                'data' => [
                    'category' => $maintenance->category,
                    'priority' => $maintenance->priority,
                    'property_id' => $maintenance->property_id,
                    'provider_id' => $maintenance->current_provider_id,
                    'provider_name' => $maintenance->currentProvider?->name,
                    'scheduled_visit_at' => $maintenance->scheduled_visit_at?->format('Y-m-d H:i:s'),
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Cambios guardados correctamente.');
    }

    public function scheduleVisit(Request $request, MaintenanceTicket $maintenance): RedirectResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $this->ensureTicketVisible($maintenance, $user, $role);
        if (! in_array($role, ['administrador', 'tecnico'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'scheduled_visit_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'force_conflict' => ['nullable', 'boolean'],
        ]);
        $scheduledVisitAt = Carbon::parse((string) $validated['scheduled_visit_at']);
        if ($maintenance->current_provider_id) {
            $conflicts = $this->findTechnicianScheduleConflicts(
                (int) $maintenance->current_provider_id,
                $scheduledVisitAt,
                $maintenance->id
            );
            if ($conflicts !== [] && ! $request->boolean('force_conflict')) {
                return redirect()->back()->with('error', $this->buildTechnicianConflictMessage($conflicts));
            }
        }

        $fromStatus = (string) $maintenance->status;
        $nextStatus = in_array($fromStatus, ['pendiente', 'revisado', 'asignado', 'reabierto'], true)
            ? 'programado'
            : $fromStatus;
        $notes = filled($validated['notes'] ?? null)
            ? trim((string) $validated['notes'])
            : 'Visita programada';

        DB::transaction(function () use ($maintenance, $validated, $fromStatus, $nextStatus, $notes, $user): void {
            $maintenance->scheduled_visit_at = Carbon::parse((string) $validated['scheduled_visit_at']);
            $maintenance->status = $nextStatus;
            $this->applyOperationalTimestampsForStatus($maintenance, $nextStatus, $fromStatus, $notes);
            $maintenance->save();

            if ($fromStatus !== $nextStatus) {
                $maintenance->statusHistory()->create([
                    'changed_by_user_id' => $user?->id,
                    'from_status' => $fromStatus,
                    'to_status' => $nextStatus,
                    'notes' => $notes,
                    'changed_at' => now(),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Visita programada correctamente.');
    }

    public function assign(Request $request, MaintenanceTicket $maintenance): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $this->ensureTicketVisible($maintenance, $user, $role);
        if (! in_array($role, ['administrador', 'tecnico'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:maintenance_providers,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'scheduled_visit_at' => ['nullable', 'date'],
            'force_conflict' => ['nullable', 'boolean'],
        ]);
        $scheduledVisitAt = filled($validated['scheduled_visit_at'] ?? null)
            ? Carbon::parse((string) $validated['scheduled_visit_at'])
            : ($maintenance->scheduled_visit_at ? Carbon::parse((string) $maintenance->scheduled_visit_at) : null);
        if ($scheduledVisitAt) {
            $conflicts = $this->findTechnicianScheduleConflicts(
                (int) $validated['provider_id'],
                $scheduledVisitAt,
                $maintenance->id
            );
            if ($conflicts !== [] && ! $request->boolean('force_conflict')) {
                $message = $this->buildTechnicianConflictMessage($conflicts);
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'requires_confirmation' => true,
                        'message' => $message,
                        'conflicts' => $conflicts,
                    ], 422);
                }

                return redirect()->back()->with('error', $message);
            }
        }

        DB::transaction(function () use ($maintenance, $validated, $user): void {
            $this->applyProviderAssignment(
                $maintenance,
                (int) $validated['provider_id'],
                $user?->id,
                $validated['notes'] ?? null,
                $validated['scheduled_visit_at'] ?? null
            );
        });

        $this->notifyTicketEvent($maintenance, 'asignacion', 'Ticket de mantenimiento asignado');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Asignación actualizada correctamente.',
                'data' => [
                    'provider_id' => $maintenance->current_provider_id,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Asignación actualizada correctamente.');
    }

    public function technicianConflicts(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        if (! in_array($role, ['administrador', 'tecnico'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:maintenance_providers,id'],
            'scheduled_visit_at' => ['required', 'date'],
            'exclude_ticket_uuid' => ['nullable', 'uuid'],
        ]);

        $excludeTicketId = null;
        if (filled($validated['exclude_ticket_uuid'] ?? null)) {
            $excludeTicketId = (int) (MaintenanceTicket::query()
                ->where('uuid', (string) $validated['exclude_ticket_uuid'])
                ->value('id') ?? 0);
        }

        $conflicts = $this->findTechnicianScheduleConflicts(
            (int) $validated['provider_id'],
            Carbon::parse((string) $validated['scheduled_visit_at']),
            $excludeTicketId ?: null
        );

        return response()->json([
            'success' => true,
            'has_conflicts' => $conflicts !== [],
            'message' => $conflicts !== [] ? $this->buildTechnicianConflictMessage($conflicts) : null,
            'conflicts' => $conflicts,
        ]);
    }

    public function updateCosts(Request $request, MaintenanceTicket $maintenance): RedirectResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $this->ensureTicketVisible($maintenance, $user, $role);
        if ($role !== 'administrador') {
            abort(403);
        }

        $validated = $request->validate([
            'labor_cost' => ['required', 'numeric', 'min:0'],
            'material_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'payer' => ['required', Rule::in(array_keys(MaintenanceTicket::COST_PAYER_LABELS))],
            'payment_rule' => ['nullable', Rule::in(array_keys(MaintenanceTicket::PAYMENT_RULE_LABELS))],
            'is_paid' => ['nullable', 'boolean'],
            'invoice_files' => ['nullable', 'array', 'max:20'],
            'invoice_files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,txt', 'max:51200'],
        ]);

        $totalCost = round((float) $validated['labor_cost'] + (float) $validated['material_cost'], 2);

        DB::transaction(function () use ($maintenance, $validated, $request, $user, $totalCost): void {
            $payer = (string) $validated['payer'];
            $paymentRule = $validated['payment_rule'] ?? null;
            $cost = $maintenance->costs()->create([
                'labor_cost' => (float) $validated['labor_cost'],
                'material_cost' => (float) $validated['material_cost'],
                'advance_cost' => 0,
                'final_cost' => $totalCost,
                'currency' => 'MXN',
                'payer' => $payer,
                'payment_rule' => $paymentRule,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $expense = Expense::create([
                'property_id' => $maintenance->property_id,
                'concept' => Str::limit('Mantenimiento ' . $maintenance->display_reference . ': ' . $maintenance->title, 190, ''),
                'amount' => $totalCost,
                'excluded_from_totals' => $payer === 'inquilino',
                'due_date' => now()->toDateString(),
                'paid_at' => (bool) ($validated['is_paid'] ?? false) ? now() : null,
                'description' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
                'created_by' => $user?->id,
            ]);

            $cost->update(['expense_id' => $expense->id]);
            $this->storeMaintenanceExpenseFiles($expense, (array) $request->file('invoice_files', []));
        });

        return redirect()->back()->with('success', 'Costo registrado y agregado a los gastos de la propiedad.');
    }

    /**
     * @param  array<int, UploadedFile|null>  $files
     */
    private function storeMaintenanceExpenseFiles(Expense $expense, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $mimeType = (string) ($file->getClientMimeType() ?: 'application/octet-stream');
            $path = $file->store("expenses/{$expense->id}", 'public');
            $expense->files()->create([
                'path' => $path,
                'type' => str_starts_with($mimeType, 'image/') ? ExpenseFile::TYPE_IMAGE : ExpenseFile::TYPE_PDF,
                'mime_type' => $mimeType,
                'original_name' => $file->getClientOriginalName(),
                'size' => (int) ($file->getSize() ?: 0),
            ]);
        }
    }

    public function uploadFiles(Request $request, MaintenanceTicket $maintenance): RedirectResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $this->ensureTicketVisible($maintenance, $user, $role);

        $validated = $request->validate([
            'kind' => ['required', Rule::in(array_keys(MaintenanceTicketFile::KIND_LABELS))],
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,mp4,mov,avi,doc,docx,xls,xlsx,txt', 'max:51200'],
        ]);
        $this->storeTicketFiles($maintenance, (array) $request->file('files', []), (string) $validated['kind'], $user?->id);

        return redirect()->back()->with('success', 'Archivos agregados correctamente.');
    }

    public function destroyFile(Request $request, MaintenanceTicket $maintenance, MaintenanceTicketFile $file): RedirectResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $this->ensureTicketVisible($maintenance, $user, $role);

        if ((int) $file->ticket_id !== (int) $maintenance->id) {
            abort(404);
        }

        if (filled($file->path)) {
            Storage::disk('public')->delete((string) $file->path);
        }

        $file->delete();

        return redirect()->back()->with('success', 'Archivo eliminado correctamente.');
    }

    public function storeMessage(Request $request, MaintenanceTicket $maintenance): RedirectResponse
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $this->ensureTicketVisible($maintenance, $user, $role);

        $validated = $request->validate([
            'channel' => ['required', Rule::in(array_keys(MaintenanceTicketMessage::CHANNEL_LABELS))],
            'message' => ['required', 'string', 'max:5000'],
            'recipient_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        if ($validated['channel'] === 'interno' && $role !== 'administrador') {
            abort(403);
        }
        if ($validated['channel'] === 'admin_tecnico' && ! in_array($role, ['administrador', 'tecnico'], true)) {
            abort(403);
        }
        if ($validated['channel'] === 'inquilino_admin' && ! in_array($role, ['administrador', 'inquilino'], true)) {
            abort(403);
        }

        $message = $maintenance->messages()->create([
            'sender_user_id' => $user?->id,
            'recipient_user_id' => $validated['recipient_user_id'] ?? null,
            'channel' => (string) $validated['channel'],
            'message' => trim((string) $validated['message']),
        ]);

        $recipient = null;
        if (! empty($validated['recipient_user_id'])) {
            $recipient = User::query()->find((int) $validated['recipient_user_id']);
        }
        $recipientRole = NotificationSettings::roleForUser($recipient);
        if (filled($recipient?->email) && (! $recipientRole || NotificationSettings::allows($recipientRole, NotificationSettings::EVENT_MAINTENANCE_MESSAGE))) {
            try {
                Mail::raw(
                    "Nuevo mensaje en ticket {$maintenance->uuid}: " . $message->message,
                    fn($mail) => $mail->to($recipient->email)->subject('Nuevo mensaje de mantenimiento')
                );
            } catch (\Throwable) {
            }
        }

        return redirect()->back()->with('success', 'Mensaje enviado correctamente.');
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        $this->ensureCanManageTechnicians($request);

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(MaintenanceProvider::TYPE_LABELS))],
            'name' => ['required', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'specialty' => ['nullable', 'string', 'max:190'],
            'average_cost' => ['nullable', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'availability' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'create_user_account' => ['nullable', 'boolean'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'account_password' => ['nullable', 'string', 'min:8', 'max:120'],
            'send_credentials_email' => ['nullable', 'boolean'],
        ]);
        $wantsCreateAccount = (bool) ($validated['create_user_account'] ?? false);
        $selectedUserId = $validated['user_id'] ?? null;
        if ($wantsCreateAccount && $selectedUserId) {
            return redirect()->back()->with('error', 'Selecciona un usuario existente o crea una cuenta nueva, no ambos.');
        }
        if (! $wantsCreateAccount && ! $selectedUserId) {
            return redirect()->back()->with('error', 'Debes vincular un usuario o crear una cuenta para el técnico/proveedor.');
        }

        [$linkedUser, $generatedPassword] = $this->resolveOrCreateTechnicianUser(
            $selectedUserId ? (int) $selectedUserId : null,
            $wantsCreateAccount,
            $validated['account_name'] ?? null,
            $validated['account_email'] ?? null,
            $validated['account_password'] ?? null,
        );

        $provider = MaintenanceProvider::create([
            'type' => (string) $validated['type'],
            'name' => trim((string) $validated['name']),
            'email' => filled($validated['email'] ?? null)
                ? trim((string) $validated['email'])
                : (filled($linkedUser?->email) ? trim((string) $linkedUser->email) : null),
            'phone' => filled($validated['phone'] ?? null) ? trim((string) $validated['phone']) : null,
            'specialty' => filled($validated['specialty'] ?? null) ? trim((string) $validated['specialty']) : null,
            'average_cost' => $validated['average_cost'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'availability' => filled($validated['availability'] ?? null) ? trim((string) $validated['availability']) : null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'user_id' => $linkedUser?->id,
        ]);

        if (
            (bool) ($validated['send_credentials_email'] ?? false)
            && $linkedUser
            && filled($generatedPassword)
            && filled($linkedUser->email)
            && NotificationSettings::allows(NotificationSettings::ROLE_TECHNICIAN, NotificationSettings::EVENT_ACCOUNT_CREATED)
        ) {
            try {
                Mail::raw(
                    "Tu cuenta de técnico fue creada.\n\nAcceso:\nCorreo: {$linkedUser->email}\nContraseña: {$generatedPassword}\n\nPortal: " . url('/login'),
                    fn($mail) => $mail->to($linkedUser->email)->subject('Acceso al sistema de mantenimiento')
                );
            } catch (\Throwable) {
            }
        }
        if ($provider->user_id && ! $provider->email && $linkedUser?->email) {
            $provider->update(['email' => $linkedUser->email]);
        }

        return redirect()->back()->with('success', 'Técnico/proveedor creado correctamente.');
    }

    public function updateProvider(Request $request, MaintenanceProvider $provider): RedirectResponse
    {
        $this->ensureCanManageTechnicians($request);

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(MaintenanceProvider::TYPE_LABELS))],
            'name' => ['required', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'specialty' => ['nullable', 'string', 'max:190'],
            'average_cost' => ['nullable', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'availability' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'create_user_account' => ['nullable', 'boolean'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'account_password' => ['nullable', 'string', 'min:8', 'max:120'],
            'send_credentials_email' => ['nullable', 'boolean'],
        ]);
        $wantsCreateAccount = (bool) ($validated['create_user_account'] ?? false);
        $selectedUserId = $validated['user_id'] ?? null;
        if ($wantsCreateAccount && $selectedUserId) {
            return redirect()->back()->with('error', 'Selecciona un usuario existente o crea una cuenta nueva, no ambos.');
        }
        if (! $wantsCreateAccount && ! $selectedUserId && ! $provider->user_id) {
            return redirect()->back()->with('error', 'Debes vincular un usuario o crear una cuenta para el técnico/proveedor.');
        }

        [$linkedUser, $generatedPassword] = $this->resolveOrCreateTechnicianUser(
            $selectedUserId ? (int) $selectedUserId : ($provider->user_id ? (int) $provider->user_id : null),
            $wantsCreateAccount,
            $validated['account_name'] ?? null,
            $validated['account_email'] ?? null,
            $validated['account_password'] ?? null,
        );

        $provider->update([
            'type' => (string) $validated['type'],
            'name' => trim((string) $validated['name']),
            'email' => filled($validated['email'] ?? null)
                ? trim((string) $validated['email'])
                : (filled($linkedUser?->email) ? trim((string) $linkedUser->email) : null),
            'phone' => filled($validated['phone'] ?? null) ? trim((string) $validated['phone']) : null,
            'specialty' => filled($validated['specialty'] ?? null) ? trim((string) $validated['specialty']) : null,
            'average_cost' => $validated['average_cost'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'availability' => filled($validated['availability'] ?? null) ? trim((string) $validated['availability']) : null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'user_id' => $linkedUser?->id,
        ]);
        if (
            (bool) ($validated['send_credentials_email'] ?? false)
            && $linkedUser
            && filled($generatedPassword)
            && filled($linkedUser->email)
            && NotificationSettings::allows(NotificationSettings::ROLE_TECHNICIAN, NotificationSettings::EVENT_ACCOUNT_CREATED)
        ) {
            try {
                Mail::raw(
                    "Tu cuenta de técnico fue creada.\n\nAcceso:\nCorreo: {$linkedUser->email}\nContraseña: {$generatedPassword}\n\nPortal: " . url('/login'),
                    fn($mail) => $mail->to($linkedUser->email)->subject('Acceso al sistema de mantenimiento')
                );
            } catch (\Throwable) {
            }
        }

        return redirect()->back()->with('success', 'Técnico/proveedor actualizado correctamente.');
    }

    private function resolveOrCreateTechnicianUser(
        ?int $userId,
        bool $createAccount,
        ?string $accountName,
        ?string $accountEmail,
        ?string $accountPassword,
    ): array {
        if ($userId) {
            $user = User::query()->find($userId);
            if (! $user) {
                throw ValidationException::withMessages([
                    'user_id' => 'El usuario seleccionado no existe.',
                ]);
            }
            $this->ensureTechnicianRole($user);

            return [$user, null];
        }

        if (! $createAccount) {
            return [null, null];
        }

        $email = trim((string) $accountEmail);
        if ($email === '') {
            throw ValidationException::withMessages([
                'account_email' => 'Debes proporcionar el correo para crear la cuenta del técnico.',
            ]);
        }
        $password = filled($accountPassword) ? (string) $accountPassword : Str::random(12);
        $name = trim((string) ($accountName ?? ''));
        if ($name === '') {
            $name = Str::before($email, '@');
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $this->ensureTechnicianRole($user);

        return [$user, $password];
    }

    private function ensureTechnicianRole(User $user): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'tecnico',
            'guard_name' => 'web',
        ]);
        if (! $user->hasRole($role->name)) {
            $user->assignRole($role);
        }
    }

    private function ensureCanManageTechnicians(Request $request): void
    {
        if (! $this->canManageTechnicians($request->user())) {
            abort(403);
        }
    }

    private function canManageTechnicians(?User $user): bool
    {
        return (bool) $user
            && (
                $user->hasRole('administrador')
                || $user->hasRole('admin')
                || $user->can(self::MANAGE_TECHNICIANS_PERMISSION)
            );
    }

    private function resolveRole(?User $user): string
    {
        if (! $user) {
            return 'inquilino';
        }
        if ($user->hasRole('administrador') || $user->hasRole('admin')) {
            return 'administrador';
        }
        if ($user->hasRole('propietario') || $user->hasRole('owner')) {
            return 'propietario';
        }
        if ($user->hasRole('inquilino') || $user->hasRole('tenant')) {
            return 'inquilino';
        }
        if ($user->hasRole('tecnico') || $user->hasRole('technician')) {
            return 'tecnico';
        }

        return 'administrador';
    }

    private function accessiblePropertiesQuery(User $user, string $role): Builder
    {
        $query = Property::query();
        if ($role === 'administrador') {
            return $query;
        }
        if ($role === 'propietario') {
            return $query->whereHas('owners', fn(Builder $ownerQuery) => $ownerQuery->where('email', $user->email));
        }
        if ($role === 'inquilino') {
            return $query->whereHas('tenant', fn(Builder $tenantQuery) => $tenantQuery->where('email', $user->email));
        }

        return $query->whereHas('maintenanceTickets.assignments.provider', function (Builder $providerQuery) use ($user): void {
            $providerQuery
                ->where('maintenance_providers.user_id', $user->id)
                ->orWhere('maintenance_providers.email', $user->email);
        });
    }

    private function visibleTicketsQuery(User $user, string $role): Builder
    {
        $query = MaintenanceTicket::query();
        if ($role === 'administrador') {
            return $query;
        }
        if ($role === 'propietario') {
            return $query->whereHas('property.owners', fn(Builder $ownerQuery) => $ownerQuery->where('email', $user->email));
        }
        if ($role === 'inquilino') {
            return $query->whereHas('property.tenant', fn(Builder $tenantQuery) => $tenantQuery->where('email', $user->email));
        }

        return $query->whereHas('assignments', function (Builder $assignmentQuery) use ($user): void {
            $assignmentQuery
                ->where('is_current', true)
                ->whereHas('provider', function (Builder $providerQuery) use ($user): void {
                    $providerQuery
                        ->where('maintenance_providers.user_id', $user->id)
                        ->orWhere('maintenance_providers.email', $user->email);
                });
        });
    }

    private function ensureTicketVisible(MaintenanceTicket $ticket, User $user, string $role): void
    {
        $exists = $this->visibleTicketsQuery($user, $role)
            ->where('maintenance_tickets.id', $ticket->id)
            ->exists();
        if (! $exists) {
            abort(403);
        }
    }

    private function findTechnicianScheduleConflicts(int $providerId, Carbon $scheduledVisitAt, ?int $excludeTicketId = null): array
    {
        $query = MaintenanceTicket::query()
            ->with(['property:id,internal_name,internal_reference'])
            ->where('current_provider_id', $providerId)
            ->whereDate('scheduled_visit_at', $scheduledVisitAt->toDateString())
            ->whereNotNull('scheduled_visit_at')
            ->whereNotIn('status', ['cancelado'])
            ->orderBy('scheduled_visit_at');
        if ($excludeTicketId) {
            $query->where('id', '!=', $excludeTicketId);
        }

        return $query->get()->map(function (MaintenanceTicket $ticket): array {
            return [
                'ticket_uuid' => (string) $ticket->uuid,
                'reference' => (string) $ticket->display_reference,
                'title' => (string) $ticket->title,
                'property_name' => (string) ($ticket->property?->internal_name ?? '-'),
                'property_reference' => (string) ($ticket->property?->internal_reference ?? ''),
                'scheduled_at' => $ticket->scheduled_visit_at?->format('d/m/Y H:i') ?? '-',
            ];
        })->values()->all();
    }

    private function buildTechnicianConflictMessage(array $conflicts): string
    {
        $items = collect($conflicts)->map(function (array $conflict): string {
            $property = trim((string) ($conflict['property_name'] ?? '-'));
            $reference = trim((string) ($conflict['property_reference'] ?? ''));
            $propertyLabel = $reference !== '' ? "{$property} ({$reference})" : $property;
            $hour = trim((string) ($conflict['scheduled_at'] ?? '-'));
            $folio = trim((string) ($conflict['reference'] ?? '-'));

            return "• {$propertyLabel} · {$hour} · Folio {$folio}";
        })->implode("\n");

        return "El técnico ya tiene asignaciones el mismo día:\n{$items}";
    }

    private function applyProviderAssignment(
        MaintenanceTicket $maintenance,
        int $providerId,
        ?int $assignedByUserId,
        ?string $notes,
        mixed $scheduledVisitAt = null,
    ): void {
        $provider = MaintenanceProvider::query()->findOrFail($providerId);
        $maintenance->assignments()
            ->where('is_current', true)
            ->update([
                'is_current' => false,
                'unassigned_at' => now(),
            ]);

        $maintenance->assignments()->create([
            'provider_id' => $provider->id,
            'assigned_by_user_id' => $assignedByUserId,
            'notes' => $notes,
            'assigned_at' => now(),
            'is_current' => true,
        ]);

        $fromStatus = $maintenance->status;
        $maintenance->current_provider_id = $provider->id;
        $maintenance->assigned_at = $maintenance->assigned_at ?: now();
        if (filled($scheduledVisitAt)) {
            $maintenance->scheduled_visit_at = Carbon::parse((string) $scheduledVisitAt);
        }
        if (in_array($maintenance->status, ['pendiente', 'revisado', 'reabierto'], true)) {
            $maintenance->status = 'asignado';
        }
        $maintenance->save();

        if ($fromStatus !== $maintenance->status) {
            $maintenance->statusHistory()->create([
                'changed_by_user_id' => $assignedByUserId,
                'from_status' => $fromStatus,
                'to_status' => $maintenance->status,
                'notes' => 'Asignación de proveedor/técnico',
                'changed_at' => now(),
            ]);
        }
    }

    private function applyOperationalTimestampsForStatus(
        MaintenanceTicket $ticket,
        string $status,
        ?string $fromStatus,
        ?string $notes,
    ): void {
        if (in_array($status, ['asignado', 'programado'], true) && ! $ticket->assigned_at) {
            $ticket->assigned_at = now();
        }
        if ($status === 'en_proceso' && ! $ticket->started_at) {
            $ticket->started_at = now();
        }
        if ($status === 'completado') {
            $ticket->completed_at = now();
            $ticket->canceled_at = null;
            $ticket->cancel_reason = null;
        }
        if ($status === 'cancelado') {
            $ticket->canceled_at = now();
            $ticket->cancel_reason = $notes;
        }
        if ($status === 'reabierto') {
            $ticket->completed_at = null;
            $ticket->canceled_at = null;
            $ticket->cancel_reason = null;
        }
        if (in_array($fromStatus, ['asignado', 'programado'], true) && $status === 'pendiente') {
            $ticket->assigned_at = null;
        }
    }

    private function storeTicketFiles(MaintenanceTicket $ticket, array $files, string $kind, ?int $userId): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $stored = $this->storeCompressedFile($file, "maintenance/{$ticket->id}/{$kind}");
            $ticket->files()->create([
                'uploaded_by_user_id' => $userId,
                'kind' => $kind,
                'path' => $stored['path'],
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $stored['mime_type'],
                'size' => $stored['size'],
                'is_compressed' => $stored['is_compressed'],
            ]);
        }
    }

    private function storeCompressedFile(UploadedFile $file, string $directory): array
    {
        $mimeType = (string) ($file->getClientMimeType() ?: 'application/octet-stream');
        if (! str_starts_with($mimeType, 'image/')) {
            $path = $file->store($directory, 'public');

            return [
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => (int) ($file->getSize() ?: 0),
                'is_compressed' => false,
            ];
        }

        $encoded = $this->encodeCompressedImage($file);
        if ($encoded === null) {
            $path = $file->store($directory, 'public');

            return [
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => (int) ($file->getSize() ?: 0),
                'is_compressed' => false,
            ];
        }

        $path = trim($directory, '/') . '/' . Str::uuid() . '.' . $encoded['extension'];
        Storage::disk('public')->put($path, $encoded['binary']);

        return [
            'path' => $path,
            'mime_type' => $encoded['mime_type'],
            'size' => strlen($encoded['binary']),
            'is_compressed' => true,
        ];
    }

    private function encodeCompressedImage(UploadedFile $file): ?array
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $sourcePath = $file->getRealPath();
        if (! $sourcePath) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return null;
        }

        $sourceWidth = (int) ($info[0] ?? 0);
        $sourceHeight = (int) ($info[1] ?? 0);
        $sourceType = (int) ($info[2] ?? 0);
        if ($sourceWidth < 1 || $sourceHeight < 1) {
            return null;
        }

        $sourceImage = match ($sourceType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            default => null,
        };
        if (! $sourceImage) {
            return null;
        }

        $maxBytes = 512000;
        $maxDimension = 2200;
        $largestSide = max($sourceWidth, $sourceHeight);
        $baseScale = $largestSide > $maxDimension ? $maxDimension / $largestSide : 1;
        $baseWidth = max(1, (int) round($sourceWidth * $baseScale));
        $baseHeight = max(1, (int) round($sourceHeight * $baseScale));
        $best = null;

        try {
            foreach ([1, 0.9, 0.8, 0.7, 0.6, 0.5] as $scale) {
                $targetWidth = max(1, (int) round($baseWidth * $scale));
                $targetHeight = max(1, (int) round($baseHeight * $scale));
                $resized = imagecreatetruecolor($targetWidth, $targetHeight);
                if (! $resized) {
                    continue;
                }
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $transparent);
                imagecopyresampled(
                    $resized,
                    $sourceImage,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $sourceWidth,
                    $sourceHeight,
                );

                foreach ([84, 78, 72, 66, 60, 54, 48, 42] as $quality) {
                    if (function_exists('imagewebp')) {
                        ob_start();
                        $ok = @imagewebp($resized, null, $quality);
                        $binary = (string) ob_get_clean();
                        if ($ok && $binary !== '') {
                            $candidate = ['binary' => $binary, 'extension' => 'webp', 'mime_type' => 'image/webp'];
                            if ($best === null || strlen($candidate['binary']) < strlen($best['binary'])) {
                                $best = $candidate;
                            }
                            if (strlen($binary) <= $maxBytes) {
                                imagedestroy($resized);

                                return $candidate;
                            }
                        }
                    }

                    ob_start();
                    $ok = @imagejpeg($resized, null, $quality);
                    $binary = (string) ob_get_clean();
                    if ($ok && $binary !== '') {
                        $candidate = ['binary' => $binary, 'extension' => 'jpg', 'mime_type' => 'image/jpeg'];
                        if ($best === null || strlen($candidate['binary']) < strlen($best['binary'])) {
                            $best = $candidate;
                        }
                        if (strlen($binary) <= $maxBytes) {
                            imagedestroy($resized);

                            return $candidate;
                        }
                    }
                }

                imagedestroy($resized);
            }
        } finally {
            imagedestroy($sourceImage);
        }

        return $best;
    }

    private function notifyTicketEvent(MaintenanceTicket $ticket, string $event, string $subject): void
    {
        $ticket->loadMissing([
            'currentProvider:id,user_id,email,name',
            'currentProvider.user:id,email,name',
            'property.tenant:id,email,full_name',
            'property.advisors:id,email,name',
        ]);

        $notificationEvent = $event === 'nuevo_reporte'
            ? NotificationSettings::EVENT_MAINTENANCE_CREATED
            : NotificationSettings::EVENT_MAINTENANCE_UPDATED;

        $recipients = collect([
            [
                'email' => $ticket->currentProvider?->email,
                'role' => NotificationSettings::ROLE_TECHNICIAN,
            ],
            [
                'email' => $ticket->currentProvider?->user?->email,
                'role' => NotificationSettings::ROLE_TECHNICIAN,
            ],
            [
                'email' => $ticket->property?->tenant?->email,
                'role' => NotificationSettings::ROLE_TENANT,
            ],
            ...($ticket->property?->advisors?->map(fn(User $advisor): array => [
                'email' => $advisor->email,
                'role' => NotificationSettings::ROLE_ADVISOR,
            ])->all() ?? []),
        ])
            ->filter(fn(array $recipient): bool => filled($recipient['email']))
            ->filter(function (array $recipient) use ($notificationEvent): bool {
                return NotificationSettings::allows($recipient['role'], $notificationEvent);
            })
            ->map(fn(array $recipient): string => trim((string) $recipient['email']))
            ->unique()
            ->values();

        foreach ($recipients as $email) {
            $sent = false;
            try {
                Mail::to($email)->send(new MaintenanceTicketEventMail($ticket, $event, $subject));
                $sent = true;
            } catch (\Throwable) {
                $sent = false;
            }

            MaintenanceTicketNotification::create([
                'ticket_id' => $ticket->id,
                'event' => $event,
                'channel' => 'email',
                'recipient' => $email,
                'was_sent' => $sent,
                'notified_at' => now(),
                'meta' => [
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'property' => $ticket->property?->internal_name,
                ],
            ]);
        }
    }
}
