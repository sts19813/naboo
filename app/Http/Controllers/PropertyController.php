<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Expense;
use App\Models\ExpenseNotificationSetting;
use App\Models\MaintenanceTicket;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyInventoryItemPhoto;
use App\Models\PropertyInventoryPhoto;
use App\Models\PropertyType;
use App\Models\RecurringExpenseItem;
use App\Models\Tenant;
use App\Models\TenantDocument;
use App\Models\User;
use App\Models\Zone;
use App\Services\DossierDocumentRequirementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PropertyController extends Controller
{
    private const TENANT_CHANGE_BLOCKED_MESSAGE = 'No es posible cambiar o quitar el inquilino mientras existan cargos pendientes, en validación o vencidos.';

    private const DEFAULT_PROPERTY_TYPES = [
        'Casa',
        'Departamento',
        'Local',
        'Townhouse',
        'Oficina',
        'Terreno',
    ];

    private const DEFAULT_ZONES = [
        'Montebello',
        'Francisco Montejo',
        'Temozon',
        'Playa',
    ];

    public function __construct(private readonly DossierDocumentRequirementService $requirements) {}

    public function index(Request $request): View
    {
        $availableAdvisors = $this->availableAdvisors();

        $properties = Property::query()
            ->with(['type', 'zone', 'tenant', 'advisor', 'advisors:id,name,email'])
            ->withCount([
                'documents as incidents_count' => fn ($query) => $query->where('status', PropertyDocument::STATUS_PENDING),
            ])
            ->latest()
            ->get();

        return view('properties.index', [
            'properties' => $properties,
            'availableAdvisors' => $availableAdvisors,
            'canManagePropertyAdvisors' => $this->canManagePropertyAssignments($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('properties.create', $this->formViewData());
    }

    public function store(StorePropertyRequest $request): RedirectResponse|JsonResponse
    {
        $property = $this->saveProperty($request);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'La propiedad se registro correctamente.',
                'redirect' => route('properties.show', $property),
            ]);
        }

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'La propiedad se registró correctamente.');
    }

    public function edit(Request $request, Property $property): View
    {
        $property->load([
            'owners',
            'documents.versions',
            'tenant',
            'advisor',
            'advisors',
        ]);

        return view('properties.create', $this->formViewData($property, true));
    }

    public function update(StorePropertyRequest $request, Property $property): RedirectResponse|JsonResponse
    {
        $property = $this->saveProperty($request, $property);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'La propiedad se actualizo correctamente.',
                'redirect' => route('properties.show', $property),
            ]);
        }

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'La propiedad se actualizó correctamente.');
    }

    public function editInventory(Request $request, Property $property): View
    {
        $this->ensureAdvisorIsReadOnly($request);

        $property->load([
            'inventoryAreas.photos',
            'inventoryAreas.items.photos.latestVersion',
        ]);

        return view('properties.inventory-edit', [
            'property' => $property,
        ]);
    }

    public function updateAdvisors(Request $request, Property $property): RedirectResponse|JsonResponse
    {
        $this->ensureCanManagePropertyAssignments($request);

        $validated = $request->validate([
            'advisor_user_ids' => ['nullable', 'array'],
            'advisor_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $advisorIds = collect($validated['advisor_user_ids'] ?? [])
            ->map(fn ($advisorId) => (int) $advisorId)
            ->filter()
            ->unique()
            ->values();
        $availableAdvisorIds = $this->availableAdvisors()->pluck('id');

        if ($advisorIds->diff($availableAdvisorIds)->isNotEmpty()) {
            return $request->expectsJson() || $request->ajax()
                ? response()->json([
                    'success' => false,
                    'message' => 'Solo puedes asignar usuarios con rol de asesor o administrador.',
                    'errors' => [
                        'advisor_user_ids' => ['Solo puedes asignar usuarios con rol de asesor o administrador.'],
                    ],
                ], 422)
                : redirect()
                    ->back()
                    ->withErrors(['advisor_user_ids' => 'Solo puedes asignar usuarios con rol de asesor o administrador.']);
        }

        $property->advisors()->sync($advisorIds->all());
        $property->update([
            'advisor_user_id' => $advisorIds->first(),
        ]);

        $message = 'Asesores responsables actualizados correctamente.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'reload' => true,
            ]);
        }

        return redirect()
            ->route('properties.index')
            ->with('success', $message);
    }

    public function updateTenant(Request $request, Property $property): RedirectResponse
    {
        $this->ensureAdvisorIsReadOnly($request);

        $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'force_assignment' => ['nullable', 'boolean'],
        ]);

        $tenantId = $request->input('tenant_id');
        $tenant = $tenantId
            ? Tenant::query()
                ->with(['documents' => fn ($query) => $query->whereIn('document_type', array_keys($this->requirements->labelsForEntity('tenant')))])
                ->find($tenantId)
            : null;
        $requestedTenantId = $tenant?->id;
        $isChangingTenant = (int) ($property->tenant_id ?? 0) !== (int) ($requestedTenantId ?? 0);

        if ($isChangingTenant && $property->tenant_id) {
            $hasOpenCharges = Charge::query()
                ->where('property_id', $property->id)
                ->whereIn('status', [
                    Charge::STATUS_PENDING,
                    Charge::STATUS_PARTIAL,
                    Charge::STATUS_IN_VALIDATION,
                ])
                ->exists();
            $hasOverdueCharges = Charge::query()
                ->where('property_id', $property->id)
                ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
                ->whereDate('due_date', '<', now()->toDateString())
                ->exists();

            if ($hasOpenCharges || $hasOverdueCharges) {
                return redirect()
                    ->back()
                    ->with('warning', self::TENANT_CHANGE_BLOCKED_MESSAGE);
            }
        }

        if ($tenant && ! $request->boolean('force_assignment')) {
            $missingRequirements = $this->getTenantAssignmentMissingRequirements($tenant);
            if (! empty($missingRequirements)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('warning', $this->formatTenantAssignmentWarning($tenant, $missingRequirements));
            }
        }

        $property->update([
            'tenant_id' => $tenant?->id,
            'current_tenant_name' => $tenant?->full_name,
        ]);

        return redirect()
            ->back()
            ->with('success', $tenant ? 'Inquilino actualizado correctamente.' : 'Inquilino quitado correctamente.');
    }

    public function show(Property $property): View
    {
        $property->load([
            'type',
            'zone',
            'owners.documents.versions',
            'documents.versions',
            'inventoryAreas.items.photos',
            'inventoryAreas.photos',
            'tenant.documents.versions',
            'recurringExpenseItems',
            'advisor',
            'advisors:id,name,email',
        ]);
        $propertyChangeLogs = $property->changeLogs()
            ->with('user:id,name')
            ->limit(250)
            ->get();

        $propertyRequiredDocuments = $this->requirements->labelsForEntity('property');
        $tenantRequiredDocuments = $this->requirements->labelsForEntity('tenant');

        $documents = collect($propertyRequiredDocuments)
            ->map(function (string $label, string $type) use ($property) {
                return $property->documents->firstWhere('document_type', $type)
                    ?? new PropertyDocument([
                        'document_type' => $type,
                        'label' => $label,
                        'status' => PropertyDocument::STATUS_PENDING,
                    ]);
            });

        $customDocuments = $property->documents
            ->whereNotIn('document_type', array_keys($propertyRequiredDocuments))
            ->values();

        $tenantDocuments = collect();
        $tenantCustomDocuments = collect();

        if ($property->tenant) {
            $tenantDocuments = collect($tenantRequiredDocuments)
                ->map(function (string $label, string $type) use ($property) {
                    return $property->tenant->documents->firstWhere('document_type', $type)
                        ?? new TenantDocument([
                            'document_type' => $type,
                            'label' => $label,
                            'status' => TenantDocument::STATUS_PENDING,
                        ]);
                });

            $tenantCustomDocuments = $property->tenant->documents
                ->whereNotIn('document_type', array_keys($tenantRequiredDocuments))
                ->values();
        }

        $tenants = Tenant::query()
            ->with(['documents' => fn ($query) => $query->whereIn('document_type', array_keys($tenantRequiredDocuments))])
            ->orderBy('full_name')
            ->get();

        $tenantAssignmentChecks = $tenants
            ->mapWithKeys(function (Tenant $tenant): array {
                $missing = $this->getTenantAssignmentMissingRequirements($tenant);

                return [
                    (string) $tenant->id => [
                        'missing' => $missing,
                        'is_complete' => empty($missing),
                    ],
                ];
            })
            ->all();

        $propertyCharges = Charge::query()
            ->with('tenant:id,full_name')
            ->where('property_id', $property->id)
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        $propertyExpenses = Expense::query()
            ->with('files')
            ->withCount('files')
            ->where('property_id', $property->id)
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        $propertyRecurringExpenseItems = $property->recurringExpenseItems()
            ->with('files')
            ->withCount('files')
            ->get();
        $propertyMaintenanceTickets = MaintenanceTicket::query()
            ->with([
                'currentProvider:id,uuid,name,type',
                'reporter:id,name,email',
            ])
            ->withCount(['files', 'messages'])
            ->where('property_id', $property->id)
            ->orderByDesc('reported_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $rentChargesTotal = Charge::query()
            ->where('property_id', $property->id)
            ->where('type', Charge::TYPE_RENT)
            ->where('status', '!=', Charge::STATUS_CANCELED)
            ->count();

        $rentChargesPaid = Charge::query()
            ->where('property_id', $property->id)
            ->where('type', Charge::TYPE_RENT)
            ->where('status', Charge::STATUS_PAID)
            ->count();

        $chargesPorCobrar = Charge::query()
            ->where('property_id', $property->id)
            ->whereIn('status', [
                Charge::STATUS_PENDING,
                Charge::STATUS_PARTIAL,
                Charge::STATUS_IN_VALIDATION,
            ])
            ->count();

        $chargesVencidos = Charge::query()
            ->where('property_id', $property->id)
            ->whereIn('status', [
                Charge::STATUS_PENDING,
                Charge::STATUS_PARTIAL,
            ])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        $chargesPendingValidation = Charge::query()
            ->where('property_id', $property->id)
            ->where('status', Charge::STATUS_IN_VALIDATION)
            ->count();

        $paidThroughDate = Charge::query()
            ->where('property_id', $property->id)
            ->where('status', Charge::STATUS_PAID)
            ->orderByDesc('due_date')
            ->value('due_date');
        $propertyPendingAmount = (float) Charge::query()
            ->where('property_id', $property->id)
            ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
            ->sum('amount')
            - (float) Charge::query()
                ->where('property_id', $property->id)
                ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
                ->sum('paid_amount');
        $propertyOverdueAmount = (float) Charge::query()
            ->where('property_id', $property->id)
            ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
            ->whereDate('due_date', '<', now()->toDateString())
            ->sum('amount')
            - (float) Charge::query()
                ->where('property_id', $property->id)
                ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
                ->whereDate('due_date', '<', now()->toDateString())
                ->sum('paid_amount');
        $propertyCollectedMonthAmount = (float) ChargePayment::query()
            ->whereHas('charge', fn ($query) => $query->where('property_id', $property->id))
            ->where('status', ChargePayment::STATUS_SUCCEEDED)
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('amount');
        $propertyPendingValidationCount = ChargePayment::query()
            ->whereHas('charge', fn ($query) => $query->where('property_id', $property->id))
            ->where('status', ChargePayment::STATUS_PENDING_VALIDATION)
            ->count();
        $propertyOpenChargesCount = Charge::query()
            ->where('property_id', $property->id)
            ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
            ->count();
        $canRemoveTenant = (bool) $property->tenant_id && $propertyOpenChargesCount === 0 && (int) $chargesVencidos === 0;
        $canReassignTenant = ! $property->tenant_id || $canRemoveTenant;
        $expenseBaseQuery = fn () => Expense::query()->includedInTotals()->where('property_id', $property->id);
        $propertyExpenseSummary = [
            'pending_total' => (float) $expenseBaseQuery()->pending()->sum('amount'),
            'paid_total' => (float) $expenseBaseQuery()->paid()->sum('amount'),
            'overdue_total' => (float) $expenseBaseQuery()->overdue()->sum('amount'),
        ];
        $globalExpenseNotificationSetup = ExpenseNotificationSetting::current();
        $resolvedPropertyExpenseNotificationSetup = $property->resolvedExpenseNotificationSetup($globalExpenseNotificationSetup);
        $isTenantMaintenanceReporter = (bool) (
            auth()->user()?->hasRole('inquilino')
            || auth()->user()?->hasRole('tenant')
        );
        $canManageCharges = ! $isTenantMaintenanceReporter;

        return view('properties.show', [
            'property' => $property,
            'documents' => $documents,
            'customDocuments' => $customDocuments,
            'tenantDocuments' => $tenantDocuments,
            'tenantCustomDocuments' => $tenantCustomDocuments,
            'tenants' => $tenants,
            'tenantAssignmentChecks' => $tenantAssignmentChecks,
            'propertyCharges' => $propertyCharges,
            'propertyExpenses' => $propertyExpenses,
            'propertyRecurringExpenseItems' => $propertyRecurringExpenseItems,
            'rentChargesTotal' => $rentChargesTotal,
            'rentChargesPaid' => $rentChargesPaid,
            'chargesPorCobrar' => $chargesPorCobrar,
            'chargesVencidos' => $chargesVencidos,
            'chargesPendingValidation' => $chargesPendingValidation,
            'paidThroughDate' => $paidThroughDate ? Carbon::parse($paidThroughDate) : null,
            'propertyPendingAmount' => max(0, $propertyPendingAmount),
            'propertyOverdueAmount' => max(0, $propertyOverdueAmount),
            'propertyCollectedMonthAmount' => max(0, $propertyCollectedMonthAmount),
            'propertyPendingValidationCount' => (int) $propertyPendingValidationCount,
            'propertyCurrentMonthLabel' => Carbon::create(now()->year, now()->month, 1)->translatedFormat('M Y'),
            'canReassignTenant' => $canReassignTenant,
            'canRemoveTenant' => $canRemoveTenant,
            'tenantChangeBlockedMessage' => self::TENANT_CHANGE_BLOCKED_MESSAGE,
            'propertyExpenseSummary' => $propertyExpenseSummary,
            'globalExpenseNotificationSetup' => $globalExpenseNotificationSetup,
            'resolvedPropertyExpenseNotificationSetup' => $resolvedPropertyExpenseNotificationSetup,
            'recurringExpenseFrequencyOptions' => RecurringExpenseItem::FREQUENCY_LABELS,
            'propertyMaintenanceTickets' => $propertyMaintenanceTickets,
            'maintenanceCategoryOptions' => MaintenanceTicket::CATEGORY_LABELS,
            'maintenancePriorityOptions' => MaintenanceTicket::PRIORITY_LABELS,
            'canCreatePropertyMaintenanceTicket' => (bool) (
                auth()->user()?->hasRole('administrador')
                || auth()->user()?->hasRole('admin')
                || auth()->user()?->hasRole('inquilino')
                || auth()->user()?->hasRole('tenant')
                || auth()->user()?->hasRole('tecnico')
                || auth()->user()?->hasRole('technician')
            ),
            'isTenantMaintenanceReporter' => $isTenantMaintenanceReporter,
            'propertyChangeLogs' => $propertyChangeLogs,
            'propertyChangeFieldLabels' => $this->propertyChangeFieldLabels(),
            'canManageCharges' => $canManageCharges,
            'canDeletePaidCharges' => $canManageCharges && (bool) auth()->user()?->can('cobranza.eliminar_pagados'),
        ]);
    }

    private function propertyChangeFieldLabels(): array
    {
        return [
            'internal_name' => 'Nombre interno',
            'internal_reference' => 'Referencia interna',
            'property_type_id' => 'Tipo de propiedad',
            'zone_id' => 'Zona',
            'zone_text' => 'Zona (texto)',
            'full_address' => 'Direccion completa',
            'map_url' => 'URL de mapa',
            'complex_name' => 'Complejo o privada',
            'official_number' => 'Numero oficial',
            'unit_number' => 'Numero de unidad',
            'monthly_rent_price' => 'Renta mensual',
            'charge_day' => 'Dia de cobro',
            'charge_tolerance_days' => 'Tolerancia de cobro',
            'use_global_expense_notifications' => 'Usar config global de gastos',
            'expense_notification_days_before' => 'Dias aviso de gastos',
            'expense_notification_emails' => 'Correos de gastos',
            'expense_notification_phones' => 'Telefonos de gastos',
            'rent_charge_plan' => 'Plan de cobro de renta',
            'facade_photo_path' => 'Foto de fachada',
            'details' => 'Detalles',
            'description' => 'Descripcion',
            'rental_requirements' => 'Requisitos de renta',
            'amenities' => 'Amenidades',
            'status' => 'Estatus',
            'tenant_id' => 'Inquilino',
            'current_tenant_name' => 'Nombre inquilino actual',
            'charge_deleted' => 'Cargo eliminado',
            'contract_starts_at' => 'Contrato inicia',
            'contract_expires_at' => 'Contrato vence',
            'onboarding_step' => 'Paso onboarding',
            'advisor_user_id' => 'Asesor responsable',
            'property_advisors' => 'Asesores responsables',
        ];
    }

    private function formViewData(?Property $property = null, bool $isEdit = false): array
    {
        return [
            'zones' => $this->getZonesCatalog(),
            'propertyTypes' => $this->getPropertyTypesCatalog(),
            'statusOptions' => [
                Property::STATUS_AVAILABLE => Property::STATUS_LABELS[Property::STATUS_AVAILABLE],
                Property::STATUS_IN_PROCESS => Property::STATUS_LABELS[Property::STATUS_IN_PROCESS],
                Property::STATUS_BLOCKED => Property::STATUS_LABELS[Property::STATUS_BLOCKED],
                Property::STATUS_OCCUPIED => Property::STATUS_LABELS[Property::STATUS_OCCUPIED],
            ],
            'ownerTypes' => Owner::OWNER_TYPE_LABELS,
            'paymentMethods' => Owner::PAYMENT_METHOD_LABELS,
            'requiredDocuments' => $this->requirements->labelsForEntity('property'),
            'defaultAreas' => [
                'Area 1',
            ],
            'availableOwners' => Owner::query()->where('is_active', true)->orderBy('name')->get(),
            'availableTenants' => Tenant::query()->where('is_active', true)->orderBy('full_name')->get(),
            'availableAdvisors' => $this->availableAdvisors(),
            'customPropertyDocuments' => $property
                ? $property->documents
                    ->whereNotIn('document_type', array_keys($this->requirements->labelsForEntity('property')))
                    ->values()
                : collect(),
            'existingFacadePhoto' => $property ? $property->facade_photo_path : null,
            'property' => $property,
            'isEdit' => $isEdit,
        ];
    }

    private function saveProperty(StorePropertyRequest $request, ?Property $property = null): Property
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($request, $validated, $property) {
            $property = $property ?? new Property;
            $data = [
                'internal_name' => $validated['internal_name'],
                'internal_reference' => $validated['internal_reference'] ?? null,
                'property_type_id' => $validated['property_type_id'],
                'zone_id' => $validated['zone_id'] ?? null,
                'zone_text' => $validated['zone_text'] ?? null,
                'full_address' => $validated['full_address'],
                'map_url' => $validated['map_url'] ?? null,
                'complex_name' => $validated['complex_name'] ?? null,
                'official_number' => $validated['official_number'] ?? null,
                'unit_number' => $validated['unit_number'] ?? null,
                'details' => $validated['details'] ?? null,
                'description' => $validated['description'] ?? null,
                'rental_requirements' => $validated['rental_requirements'] ?? null,
                'amenities' => $validated['amenities'] ?? null,
                'status' => $validated['status'],
                'onboarding_step' => 5,
                'monthly_rent_price' => $validated['monthly_rent_price'] ?? null,
                'advisor_user_id' => $validated['advisor_user_id'] ?? $request->user()?->id,
            ];

            if (array_key_exists('tenant_id', $validated)) {
                $tenantId = $validated['tenant_id'] ?? null;
                $tenant = $tenantId ? Tenant::query()->find($tenantId) : null;
                $data['tenant_id'] = $tenant?->id;
                $data['current_tenant_name'] = $tenant?->full_name ?? null;
            }

            if (
                array_key_exists('contract_starts_at', $validated) ||
                array_key_exists('contract_expires_at', $validated) ||
                array_key_exists('rent_charge_plan', $validated)
            ) {
                $data['contract_starts_at'] = $validated['contract_starts_at'] ?? null;
                $data['contract_expires_at'] = $validated['contract_expires_at'] ?? null;
                $data['rent_charge_plan'] = $this->buildRentChargePlan($validated, $property);
            }

            $property->fill($data);

            if (! $property->exists) {
                $property->created_by = $request->user()->id;
            }

            $property->save();

            if ($request->hasFile('facade_photo')) {
                $path = $request->file('facade_photo')->store("properties/{$property->id}/facade", 'public');
                $property->update(['facade_photo_path' => $path]);
            }

            $this->syncOwners(
                $property,
                $validated['owner_ids'] ?? [],
                $validated['new_owners'] ?? [],
            );
            if (filled($property->advisor_user_id)) {
                $property->advisors()->syncWithoutDetaching([(int) $property->advisor_user_id]);
            }
            $this->syncDocuments($property, $request);
            $this->syncCustomDocuments($property, $request);
            $this->syncRentChargesFromPlan($property, $request->user()?->id);

            return $property->fresh();
        });
    }

    private function availableAdvisors(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                'asesores',
                'asesor',
                'advisor',
                'administrador',
                'admin',
            ]))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function isAdvisorUser(?User $user): bool
    {
        return (bool) $user
            && ! $this->isAdminUser($user)
            && ($this->hasAdvisorRole($user) || $user->can('propiedades.ver_propias'));
    }

    private function hasAdvisorRole(?User $user): bool
    {
        return (bool) $user && $user->hasAnyRole(['asesores', 'asesor', 'advisor']);
    }

    private function isAdminUser(?User $user): bool
    {
        return (bool) $user && ($user->hasRole('administrador') || $user->hasRole('admin'));
    }

    private function ensureCanManagePropertyAssignments(Request $request): void
    {
        if (! $this->canManagePropertyAssignments($request->user())) {
            abort(403);
        }
    }

    private function canManagePropertyAssignments(?User $user): bool
    {
        return $this->isAdminUser($user)
            || $this->hasAdvisorRole($user)
            || (bool) $user?->can('propiedades.asignar_asesores');
    }

    private function ensureAdvisorIsReadOnly(Request $request): void
    {
        if ($this->isAdvisorUser($request->user())) {
            abort(403);
        }
    }

    private function getTenantAssignmentMissingRequirements(Tenant $tenant): array
    {
        $missing = [];
        $checks = [
            'full_name' => 'Nombre completo',
            'email' => 'Email',
            'phone_primary' => 'Telefono principal',
            'personal_reference_name' => 'Referencia personal - nombre',
            'personal_reference_phone' => 'Referencia personal - tel',
        ];

        foreach ($checks as $field => $label) {
            if (blank($tenant->{$field})) {
                $missing[] = $label;
            }
        }

        $documentsByType = $tenant->relationLoaded('documents')
            ? $tenant->documents->keyBy('document_type')
            : $tenant->documents()
                ->whereIn('document_type', array_keys($this->requirements->labelsForEntity('tenant')))
                ->get()
                ->keyBy('document_type');

        foreach ($this->requirements->labelsForEntity('tenant') as $documentType => $label) {
            $document = $documentsByType->get($documentType);
            $hasUploadedFile = filled($document?->file_path);
            $isRejectedOrExpired = in_array(
                $document?->status,
                [TenantDocument::STATUS_REJECTED, TenantDocument::STATUS_EXPIRED],
                true,
            );

            if (! $hasUploadedFile || $isRejectedOrExpired) {
                $missing[] = 'Documento: '.$label;
            }
        }

        return array_values(array_unique($missing));
    }

    private function formatTenantAssignmentWarning(Tenant $tenant, array $missingRequirements): string
    {
        return sprintf(
            'El inquilino %s tiene informacion/documentacion incompleta: %s. Si deseas continuar, confirma de nuevo la asignacion.',
            $tenant->full_name,
            implode(', ', $missingRequirements),
        );
    }

    private function syncRentChargesFromPlan(Property $property, ?int $userId): void
    {
        if (! $property->tenant_id) {
            return;
        }

        $planRows = collect($property->rent_charge_plan ?? [])
            ->filter(fn ($row) => is_array($row))
            ->values();

        foreach ($planRows as $row) {
            $periodMonth = (int) ($row['period_month'] ?? 0);
            $periodYear = (int) ($row['period_year'] ?? 0);
            $amount = (float) ($row['amount'] ?? 0);

            if ($periodMonth < 1 || $periodMonth > 12 || $periodYear < 2000 || $amount <= 0) {
                continue;
            }

            $alreadyExists = Charge::query()
                ->where('property_id', $property->id)
                ->where('tenant_id', $property->tenant_id)
                ->where('type', Charge::TYPE_RENT)
                ->where('period_month', $periodMonth)
                ->where('period_year', $periodYear)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            Charge::create([
                'property_id' => $property->id,
                'tenant_id' => $property->tenant_id,
                'type' => Charge::TYPE_RENT,
                'due_date' => (string) ($row['due_date'] ?? now()->toDateString()),
                'amount' => $amount,
                'paid_amount' => 0,
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'concept' => (string) ($row['concept'] ?? $this->buildRentChargeConcept($periodMonth, $periodYear)),
                'notes' => filled($row['notes'] ?? null) ? (string) $row['notes'] : 'Generado automaticamente por contrato.',
                'status' => Charge::STATUS_PENDING,
                'created_by' => $userId,
            ]);
        }
    }

    private function buildRentChargePlan(array $validated, ?Property $property = null): array
    {
        $startsAt = $this->parseDate($validated['contract_starts_at'] ?? null);
        $expiresAt = $this->parseDate($validated['contract_expires_at'] ?? null);

        if (! $startsAt || ! $expiresAt) {
            return [];
        }

        $contractDay = (int) $startsAt->day;
        $startsAt = $startsAt->startOfMonth();
        $expiresAt = $expiresAt->startOfMonth();
        if ($startsAt->gt($expiresAt)) {
            return [];
        }

        $rentPrice = (float) ($validated['monthly_rent_price'] ?? 0);

        $submittedRows = collect($validated['rent_charge_plan'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => $this->normalizeRentChargePlanRow($row))
            ->filter()
            ->keyBy(fn (array $row) => $this->chargePlanPeriodKey((int) $row['period_year'], (int) $row['period_month']));

        $existingRows = collect($property?->rent_charge_plan ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => $this->normalizeRentChargePlanRow($row))
            ->filter()
            ->keyBy(fn (array $row) => $this->chargePlanPeriodKey((int) $row['period_year'], (int) $row['period_month']));

        $rows = [];
        $cursor = $startsAt->copy();

        while ($cursor->lte($expiresAt)) {
            $periodMonth = (int) $cursor->month;
            $periodYear = (int) $cursor->year;
            $periodKey = $this->chargePlanPeriodKey($periodYear, $periodMonth);

            /** @var array|null $submittedRow */
            $submittedRow = $submittedRows->get($periodKey);
            /** @var array|null $existingRow */
            $existingRow = $existingRows->get($periodKey);
            $sourceRow = $submittedRow ?? $existingRow ?? [];

            $isCustomAmount = (bool) ($sourceRow['is_custom_amount'] ?? false);
            $amount = $isCustomAmount
                ? (float) ($sourceRow['amount'] ?? 0)
                : $rentPrice;
            if ($amount <= 0 && isset($sourceRow['amount']) && is_numeric($sourceRow['amount'])) {
                $amount = (float) $sourceRow['amount'];
            }
            if ($amount <= 0) {
                $cursor->addMonthNoOverflow()->startOfMonth();

                continue;
            }

            $defaultDueDate = $cursor->copy()->day(min($contractDay, $cursor->daysInMonth))->toDateString();
            $dueDate = $this->resolveRentChargeDueDate(
                $sourceRow['due_date'] ?? null,
                $cursor,
                $defaultDueDate,
            );

            $concept = trim((string) ($sourceRow['concept'] ?? ''));
            if ($concept === '') {
                $concept = $this->buildRentChargeConcept($periodMonth, $periodYear);
            }

            $notes = trim((string) ($sourceRow['notes'] ?? ''));

            $rows[] = [
                'type' => 'rent',
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'due_date' => $dueDate,
                'amount' => round($amount, 2),
                'concept' => $concept,
                'notes' => $notes !== '' ? $notes : null,
                'is_custom_amount' => $isCustomAmount,
            ];

            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        return $rows;
    }

    private function normalizeRentChargePlanRow(array $row): ?array
    {
        $periodMonth = (int) ($row['period_month'] ?? 0);
        $periodYear = (int) ($row['period_year'] ?? 0);
        if ($periodMonth < 1 || $periodMonth > 12 || $periodYear < 1900) {
            return null;
        }

        $amount = isset($row['amount']) && is_numeric($row['amount'])
            ? (float) $row['amount']
            : null;

        return [
            'period_month' => $periodMonth,
            'period_year' => $periodYear,
            'due_date' => filled($row['due_date'] ?? null) ? (string) $row['due_date'] : null,
            'amount' => $amount,
            'concept' => filled($row['concept'] ?? null) ? trim((string) $row['concept']) : null,
            'notes' => filled($row['notes'] ?? null) ? trim((string) $row['notes']) : null,
            'is_custom_amount' => (bool) ($row['is_custom_amount'] ?? false),
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveRentChargeDueDate(?string $dueDate, Carbon $periodDate, string $defaultDueDate): string
    {
        $parsedDueDate = $this->parseDate($dueDate);
        if (
            ! $parsedDueDate ||
            (int) $parsedDueDate->month !== (int) $periodDate->month ||
            (int) $parsedDueDate->year !== (int) $periodDate->year
        ) {
            return $defaultDueDate;
        }

        return $parsedDueDate->toDateString();
    }

    private function chargePlanPeriodKey(int $periodYear, int $periodMonth): string
    {
        return sprintf('%04d-%02d', $periodYear, $periodMonth);
    }

    private function buildRentChargeConcept(int $periodMonth, int $periodYear): string
    {
        $monthNames = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return 'Renta '.($monthNames[$periodMonth] ?? (string) $periodMonth).' '.$periodYear;
    }

    private function syncOwners(Property $property, array $ownerIds, array $newOwners): void
    {
        $selectedOwnerIds = collect($ownerIds)
            ->filter(fn ($ownerId) => filled($ownerId))
            ->map(fn ($ownerId) => (int) $ownerId)
            ->values();

        foreach ($newOwners as $ownerData) {
            $hasAnyData = collect($ownerData)->contains(fn ($value) => filled($value));
            if (! $hasAnyData) {
                continue;
            }

            $owner = Owner::create([
                'name' => $ownerData['name'],
                'phone' => $ownerData['phone'],
                'email' => $ownerData['email'] ?? null,
                'rfc' => $ownerData['rfc'] ?? null,
                'curp' => $ownerData['curp'] ?? null,
                'owner_type' => $ownerData['owner_type'] ?? Owner::OWNER_INDIVIDUAL,
                'bank_name' => $ownerData['bank_name'] ?? null,
                'clabe' => $ownerData['clabe'] ?? null,
                'account_holder' => $ownerData['account_holder'] ?? null,
                'payment_method' => $ownerData['payment_method'] ?? Owner::PAYMENT_METHOD_TRANSFER,
                'address' => $ownerData['address'] ?? null,
                'notes' => $ownerData['notes'] ?? null,
                'is_active' => true,
            ]);

            $selectedOwnerIds->push($owner->id);
        }

        $property->owners()->sync($selectedOwnerIds->unique()->all());
    }

    private function syncDocuments(Property $property, StorePropertyRequest $request): void
    {
        $existingDocuments = $property->documents()->with('versions')->get()->keyBy('document_type');

        foreach ($this->requirements->labelsForEntity('property') as $documentType => $documentLabel) {
            $document = $existingDocuments->get($documentType)
                ?? $property->documents()->create([
                    'document_type' => $documentType,
                    'label' => $documentLabel,
                    'status' => PropertyDocument::STATUS_PENDING,
                    'uploaded_at' => null,
                    'file_path' => null,
                    'expires_at' => null,
                ]);

            $document->update([
                'label' => $documentLabel,
            ]);

            if (! $request->hasFile("documents.{$documentType}")) {
                continue;
            }

            $file = $request->file("documents.{$documentType}");
            $storedPath = $file->store("properties/{$property->id}/documents", 'public');
            $nextVersion = ((int) $document->versions()->max('version_number')) + 1;

            $document->versions()->create([
                'version_number' => $nextVersion,
                'file_path' => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $request->user()?->id,
                'uploaded_at' => now(),
            ]);

            $document->update([
                'file_path' => $storedPath,
                'status' => PropertyDocument::STATUS_UPLOADED,
                'uploaded_at' => now(),
            ]);
        }
    }

    private function syncCustomDocuments(Property $property, StorePropertyRequest $request): void
    {
        $requiredDocumentTypes = array_keys($this->requirements->labelsForEntity('property'));
        $existingCustomDocuments = $property->documents()
            ->whereNotIn('document_type', $requiredDocumentTypes)
            ->with('versions')
            ->get()
            ->keyBy('document_type');

        foreach ((array) $request->input('existing_custom_documents', []) as $documentType => $documentData) {
            if (! is_array($documentData)) {
                continue;
            }

            $document = $existingCustomDocuments->get($documentType);
            if (! $document) {
                continue;
            }

            $label = trim((string) ($documentData['label'] ?? ''));
            $expiresAt = $documentData['expires_at'] ?? null;

            $updates = [];
            if ($label !== '') {
                $updates['label'] = $label;
            }
            if (filled($expiresAt)) {
                $updates['expires_at'] = $expiresAt;
            }
            if (! empty($updates)) {
                $document->update($updates);
            }

            if (! $request->hasFile("existing_custom_documents.$documentType.file")) {
                continue;
            }

            $file = $request->file("existing_custom_documents.$documentType.file");
            $storedPath = $file->store("properties/{$property->id}/documents", 'public');
            $nextVersion = ((int) $document->versions()->max('version_number')) + 1;

            $document->versions()->create([
                'version_number' => $nextVersion,
                'file_path' => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $request->user()?->id,
                'uploaded_at' => now(),
            ]);

            $document->update([
                'file_path' => $storedPath,
                'status' => PropertyDocument::STATUS_UPLOADED,
                'uploaded_at' => now(),
                'expires_at' => filled($expiresAt) ? $expiresAt : $document->expires_at,
                'label' => $label !== '' ? $label : $document->label,
            ]);
        }

        foreach ((array) $request->input('new_custom_documents', []) as $index => $documentData) {
            if (! is_array($documentData)) {
                continue;
            }

            $label = trim((string) ($documentData['label'] ?? ''));
            if ($label === '' || ! $request->hasFile("new_custom_documents.$index.file")) {
                continue;
            }

            $documentType = $this->buildCustomDocumentType($property, $label);
            $file = $request->file("new_custom_documents.$index.file");
            $storedPath = $file->store("properties/{$property->id}/documents", 'public');

            $document = $property->documents()->create([
                'document_type' => $documentType,
                'label' => $label,
                'file_path' => $storedPath,
                'status' => PropertyDocument::STATUS_UPLOADED,
                'uploaded_at' => now(),
                'expires_at' => $documentData['expires_at'] ?? null,
            ]);

            $document->versions()->create([
                'version_number' => 1,
                'file_path' => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $request->user()?->id,
                'uploaded_at' => now(),
            ]);
        }
    }

    private function buildCustomDocumentType(Property $property, string $label): string
    {
        $base = 'custom_'.Str::slug($label, '_');
        if ($base === 'custom_') {
            $base = 'custom_documento';
        }

        $candidate = $base;
        $suffix = 2;

        while ($property->documents()->where('document_type', $candidate)->exists()) {
            $candidate = $base.'_'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function syncInventory(Property $property, array $inventoryAreas, StorePropertyRequest $request): void
    {
        $removedAreaPhotoIds = collect((array) $request->input('removed_area_photo_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
        $removedItemPhotoIds = collect((array) $request->input('removed_item_photo_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $this->deleteRequestedAreaPhotos($property, $removedAreaPhotoIds);
        $this->deleteRequestedItemPhotos($property, $removedItemPhotoIds);

        $processedAreaIds = [];

        foreach ($inventoryAreas as $areaIndex => $areaData) {

            $area = $property->inventoryAreas()->updateOrCreate(
                ['id' => $areaData['id'] ?? null],
                [
                    'name' => $areaData['name'] ?? 'Area '.($areaIndex + 1),
                    'notes' => $areaData['notes'] ?? null,
                ]
            );

            $processedAreaIds[] = $area->id;

            $processedItemIds = [];

            foreach ($areaData['items'] ?? [] as $itemIndex => $itemData) {

                if (empty($itemData['id']) && blank($itemData['name'] ?? null)) {
                    continue;
                }

                $item = $area->items()->updateOrCreate(
                    ['id' => $itemData['id'] ?? null],
                    [
                        'name' => $itemData['name'],
                        'condition' => $itemData['condition'] ?? null,
                        'notes' => $itemData['notes'] ?? null,
                        'entry_checklist' => $itemData['entry_checklist'] ?? null,
                        'exit_checklist' => $itemData['exit_checklist'] ?? null,
                    ]
                );

                $processedItemIds[] = $item->id;

                // NUEVAS FOTOS (NO BORRA LAS EXISTENTES)
                foreach ($request->file("inventory_areas.{$areaIndex}.items.{$itemIndex}.photos", []) as $photo) {

                    $photoRecord = $item->photos()->create([
                        'name' => 'Photo for '.$item->name,
                        'status' => PropertyInventoryItemPhoto::STATUS_ACTIVE,
                    ]);

                    $storedPhoto = $this->storeCompressedInventoryImage(
                        $photo,
                        "properties/{$property->id}/inventory/items/{$item->id}",
                    );

                    $photoRecord->versions()->create([
                        'file_path' => $storedPhoto['path'],
                        'file_name' => $photo->getClientOriginalName(),
                        'mime_type' => $storedPhoto['mime_type'],
                        'file_size' => $storedPhoto['file_size'],
                        'uploaded_by' => $request->user()->id,
                    ]);
                }
            }

            if (! empty($areaData['items'])) {
                $itemsToDelete = $area->items()
                    ->whereNotIn('id', $processedItemIds)
                    ->with('photos.versions')
                    ->get();

                foreach ($itemsToDelete as $itemToDelete) {
                    $this->deleteItemPhotoFiles($itemToDelete->photos);
                }

                if ($itemsToDelete->isNotEmpty()) {
                    $area->items()
                        ->whereIn('id', $itemsToDelete->pluck('id'))
                        ->delete();
                }
            }
            // FOTOS DEL ÁREA
            foreach ($request->file("inventory_areas.{$areaIndex}.photos", []) as $photoIndex => $photo) {
                $storedPhoto = $this->storeCompressedInventoryImage(
                    $photo,
                    "properties/{$property->id}/inventory/{$area->id}",
                );

                $area->photos()->create([
                    'file_path' => $storedPhoto['path'],
                    'display_order' => $photoIndex,
                ]);
            }
        }

        $areasToDeleteQuery = $property->inventoryAreas();
        if (! empty($processedAreaIds)) {
            $areasToDeleteQuery->whereNotIn('id', $processedAreaIds);
        }

        $areasToDelete = $areasToDeleteQuery
            ->with(['photos', 'items.photos.versions'])
            ->get();

        foreach ($areasToDelete as $areaToDelete) {
            $this->deleteAreaPhotoFiles($areaToDelete->photos);

            foreach ($areaToDelete->items as $itemToDelete) {
                $this->deleteItemPhotoFiles($itemToDelete->photos);
            }
        }

        if ($areasToDelete->isNotEmpty()) {
            $property->inventoryAreas()->whereIn('id', $areasToDelete->pluck('id'))->delete();
        }
    }

    /**
     * Guarda una imagen de inventario aplicando compresion proporcional.
     * Si no es posible procesarla, guarda el archivo original sin transformacion.
     *
     * @return array{path: string, mime_type: string, file_size: int}
     */
    private function storeCompressedInventoryImage(UploadedFile $photo, string $directory): array
    {
        $encoded = $this->encodeCompressedInventoryImage($photo);

        if ($encoded === null) {
            $path = $photo->store($directory, 'public');

            return [
                'path' => $path,
                'mime_type' => $photo->getClientMimeType() ?: 'application/octet-stream',
                'file_size' => (int) ($photo->getSize() ?: 0),
            ];
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.'.$encoded['extension'];
        Storage::disk('public')->put($path, $encoded['binary']);

        return [
            'path' => $path,
            'mime_type' => $encoded['mime_type'],
            'file_size' => strlen($encoded['binary']),
        ];
    }

    /**
     * Convierte una imagen a WEBP/JPG variando escala y calidad para acercarse
     * al peso objetivo configurado.
     *
     * @return array{binary: string, extension: string, mime_type: string}|null
     */
    private function encodeCompressedInventoryImage(UploadedFile $photo): ?array
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $sourcePath = $photo->getRealPath();
        if (! $sourcePath) {
            return null;
        }

        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            return null;
        }

        $sourceWidth = (int) ($imageInfo[0] ?? 0);
        $sourceHeight = (int) ($imageInfo[1] ?? 0);
        $imageType = (int) ($imageInfo[2] ?? 0);
        if ($sourceWidth < 1 || $sourceHeight < 1) {
            return null;
        }

        $sourceImage = $this->createImageResource($sourcePath, $imageType);
        if (! $sourceImage) {
            return null;
        }

        $largestSide = max($sourceWidth, $sourceHeight);
        $maxDimension = $this->getInventoryImageMaxDimension();
        $baseScale = $largestSide > $maxDimension
            ? $maxDimension / $largestSide
            : 1;

        $baseWidth = max(1, (int) round($sourceWidth * $baseScale));
        $baseHeight = max(1, (int) round($sourceHeight * $baseScale));

        $scaleSteps = [1, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4, 0.3];
        $qualitySteps = [82, 76, 70, 64, 58, 52, 46, 40];
        $bestMatch = null;

        try {
            foreach ($scaleSteps as $scale) {
                $targetWidth = max(1, (int) round($baseWidth * $scale));
                $targetHeight = max(1, (int) round($baseHeight * $scale));

                $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);
                if (! $resizedImage) {
                    continue;
                }

                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $targetWidth, $targetHeight, $transparent);

                imagecopyresampled(
                    $resizedImage,
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

                foreach ($qualitySteps as $quality) {
                    $encoded = $this->encodeImageBinary($resizedImage, $quality);
                    if ($encoded === null || $encoded['binary'] === '') {
                        continue;
                    }

                    if ($bestMatch === null || strlen($encoded['binary']) < strlen($bestMatch['binary'])) {
                        $bestMatch = $encoded;
                    }

                    if (strlen($encoded['binary']) <= $this->getInventoryImageMaxBytes()) {
                        imagedestroy($resizedImage);

                        return $encoded;
                    }
                }

                imagedestroy($resizedImage);
            }
        } finally {
            imagedestroy($sourceImage);
        }

        return $bestMatch;
    }

    /**
     * Crea el recurso GD en memoria segun el tipo de imagen recibido.
     *
     * @return \GdImage|resource|null
     */
    private function createImageResource(string $sourcePath, int $imageType)
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            default => null,
        };
    }

    /**
     * Codifica la imagen en WEBP (si esta disponible) o JPEG como respaldo.
     *
     * @return array{binary: string, extension: string, mime_type: string}|null
     */
    private function encodeImageBinary($image, int $quality): ?array
    {
        if (function_exists('imagewebp')) {
            ob_start();
            $encoded = @imagewebp($image, null, $quality);
            $binary = (string) ob_get_clean();

            if ($encoded && $binary !== '') {
                return [
                    'binary' => $binary,
                    'extension' => 'webp',
                    'mime_type' => 'image/webp',
                ];
            }
        }

        ob_start();
        $encoded = @imagejpeg($image, null, $quality);
        $binary = (string) ob_get_clean();

        if (! $encoded || $binary === '') {
            return null;
        }

        return [
            'binary' => $binary,
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
        ];
    }

    /**
     * Obtiene de configuracion el peso maximo permitido por imagen (bytes).
     */
    private function getInventoryImageMaxBytes(): int
    {
        return max(10240, (int) config('inventory.image_max_bytes', 512000));
    }

    /**
     * Obtiene de configuracion la dimension maxima del lado mayor en pixeles.
     */
    private function getInventoryImageMaxDimension(): int
    {
        return max(400, (int) config('inventory.image_max_dimension', 2200));
    }

    /**
     * Elimina fotos de area solicitadas por el usuario y su archivo en disco.
     *
     * @param  array<int>  $photoIds
     */
    private function deleteRequestedAreaPhotos(Property $property, array $photoIds): void
    {
        if (empty($photoIds)) {
            return;
        }

        $photos = PropertyInventoryPhoto::query()
            ->whereIn('id', $photoIds)
            ->whereHas('area', fn ($query) => $query->where('property_id', $property->id))
            ->get();

        $this->deleteAreaPhotoFiles($photos);
        $photos->each->delete();
    }

    /**
     * Elimina fotos de item solicitadas por el usuario y sus archivos en disco.
     *
     * @param  array<int>  $photoIds
     */
    private function deleteRequestedItemPhotos(Property $property, array $photoIds): void
    {
        if (empty($photoIds)) {
            return;
        }

        $photos = PropertyInventoryItemPhoto::query()
            ->whereIn('id', $photoIds)
            ->whereHas('item.area', fn ($query) => $query->where('property_id', $property->id))
            ->with('versions')
            ->get();

        $this->deleteItemPhotoFiles($photos);
        $photos->each->delete();
    }

    /**
     * Borra del disco publico las fotos de area.
     */
    private function deleteAreaPhotoFiles(iterable $photos): void
    {
        foreach ($photos as $photo) {
            $this->deleteStoragePath($photo->file_path ?? null);
        }
    }

    /**
     * Borra del disco publico todas las versiones de fotos de item.
     */
    private function deleteItemPhotoFiles(iterable $photos): void
    {
        foreach ($photos as $photo) {
            foreach ($photo->versions ?? [] as $version) {
                $this->deleteStoragePath($version->file_path ?? null);
            }
        }
    }

    /**
     * Elimina una ruta del disco publico si existe.
     */
    private function deleteStoragePath(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    private function getPropertyTypesCatalog(): Collection
    {
        foreach (self::DEFAULT_PROPERTY_TYPES as $name) {
            PropertyType::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );
        }

        $order = collect(self::DEFAULT_PROPERTY_TYPES)
            ->mapWithKeys(fn (string $name, int $index) => [Str::slug($name) => $index]);

        return PropertyType::query()
            ->where('is_active', true)
            ->whereIn('slug', $order->keys()->all())
            ->get()
            ->sortBy(fn (PropertyType $type) => $order[$type->slug] ?? 999)
            ->values();
    }

    private function getZonesCatalog(): Collection
    {
        foreach (self::DEFAULT_ZONES as $name) {
            Zone::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );
        }

        $order = collect(self::DEFAULT_ZONES)
            ->mapWithKeys(fn (string $name, int $index) => [Str::slug($name) => $index]);

        return Zone::query()
            ->where('is_active', true)
            ->whereIn('slug', $order->keys()->all())
            ->get()
            ->sortBy(fn (Zone $zone) => $order[$zone->slug] ?? 999)
            ->values();
    }
}
