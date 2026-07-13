<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateChargesRequest;
use App\Http\Requests\SendChargeReminderRequest;
use App\Http\Requests\StoreChargePaymentRequest;
use App\Http\Requests\StoreChargeRequest;
use App\Http\Requests\UpdateChargeRequest;
use App\Mail\ChargeCompletedMail;
use App\Mail\ChargeReminderMail;
use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\Property;
use App\Models\PropertyChangeLog;
use App\Models\Tenant;
use App\Models\TenantDocument;
use App\Models\User;
use App\Services\DossierDocumentRequirementService;
use App\Support\NotificationSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ChargeController extends Controller
{
    public function __construct(private readonly DossierDocumentRequirementService $requirements)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $isTenant = $this->isTenantUser($user);
        $tenantPropertyIds = $isTenant
            ? Property::query()
                ->whereHas('tenant', fn ($query) => $query->where('email', $user->email))
                ->pluck('id')
            : collect();
        $filters = $request->validate([
            'property' => ['nullable', 'string', 'exists:properties,uuid'],
        ]);

        $selectedPropertyUuid = (string) ($filters['property'] ?? '');
        $selectedProperty = filled($selectedPropertyUuid)
            ? Property::query()
                ->with('tenant:id,full_name')
                ->when($isTenant, fn ($query) => $query->whereIn('id', $tenantPropertyIds))
                ->where('uuid', $selectedPropertyUuid)
                ->first()
            : null;
        if ($isTenant && filled($selectedPropertyUuid) && !$selectedProperty) {
            abort(403);
        }
        $selectedPropertyId = $selectedProperty?->id;
        $selectedPropertyHasRentCharges = $selectedPropertyId
            ? Charge::query()
                ->where('property_id', $selectedPropertyId)
                ->where('type', Charge::TYPE_RENT)
                ->where('status', '!=', Charge::STATUS_CANCELED)
                ->exists()
            : false;
        $showPropertySetupCard = !$isTenant && (bool) ($selectedPropertyId && !$selectedPropertyHasRentCharges);

        $charges = Charge::query()
            ->with(['tenant:id,full_name', 'property:id,internal_name,internal_reference'])
            ->when($isTenant, fn ($query) => $query->whereIn('property_id', $tenantPropertyIds))
            ->when($selectedPropertyId, fn ($query) => $query->where('property_id', $selectedPropertyId))
            ->latest('id')
            ->get();

        $payments = ChargePayment::query()
            ->with(['charge.tenant:id,full_name', 'charge.property:id,internal_name,internal_reference'])
            ->where('status', ChargePayment::STATUS_SUCCEEDED)
            ->when(
                $isTenant,
                fn ($query) => $query->whereHas('charge', fn ($chargeQuery) => $chargeQuery->whereIn('property_id', $tenantPropertyIds)),
            )
            ->when(
                $selectedPropertyId,
                fn ($query) => $query->whereHas('charge', fn ($chargeQuery) => $chargeQuery->where('property_id', $selectedPropertyId)),
            )
            ->latest('id')
            ->get();

        $now = now();
        $chargeBaseQuery = fn () => Charge::query()
            ->when($isTenant, fn ($query) => $query->whereIn('property_id', $tenantPropertyIds))
            ->when($selectedPropertyId, fn ($query) => $query->where('property_id', $selectedPropertyId));
        $paymentBaseQuery = fn () => ChargePayment::query()
            ->when(
                $isTenant,
                fn ($query) => $query->whereHas('charge', fn ($chargeQuery) => $chargeQuery->whereIn('property_id', $tenantPropertyIds)),
            )
            ->when(
                $selectedPropertyId,
                fn ($query) => $query->whereHas('charge', fn ($chargeQuery) => $chargeQuery->where('property_id', $selectedPropertyId)),
            );

        $stats = [
            'pending_amount' => (float) $chargeBaseQuery()
                ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
                ->sum('amount') - (float) $chargeBaseQuery()
                    ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION])
                    ->sum('paid_amount'),
            'overdue_amount' => (float) $chargeBaseQuery()
                ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
                ->whereDate('due_date', '<', $now->toDateString())
                ->sum('amount') - (float) $chargeBaseQuery()
                    ->whereIn('status', [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL])
                    ->whereDate('due_date', '<', $now->toDateString())
                    ->sum('paid_amount'),
            'collected_month' => (float) $paymentBaseQuery()
                ->where('status', ChargePayment::STATUS_SUCCEEDED)
                ->whereYear('paid_at', $now->year)
                ->whereMonth('paid_at', $now->month)
                ->sum('amount'),
            'pending_validation' => $paymentBaseQuery()
                ->where('status', ChargePayment::STATUS_PENDING_VALIDATION)
                ->count(),
            'charges_count' => $chargeBaseQuery()->count(),
            'payments_count' => $paymentBaseQuery()
                ->where('status', ChargePayment::STATUS_SUCCEEDED)
                ->count(),
        ];

        $propertiesQuery = Property::query()
            ->with('tenant:id,full_name')
            ->when($isTenant, fn ($query) => $query->whereIn('id', $tenantPropertyIds))
            ->orderBy('internal_name');
        if ($selectedPropertyId) {
            $propertiesQuery->where('id', $selectedPropertyId);
        }

        $chargeablePropertiesQuery = Property::query()
            ->with('tenant:id,full_name')
            ->whereNotNull('tenant_id')
            ->when($isTenant, fn ($query) => $query->whereIn('id', $tenantPropertyIds))
            ->orderBy('internal_name');
        if ($selectedPropertyId) {
            $chargeablePropertiesQuery->where('id', $selectedPropertyId);
        }

        $propertySetupTenants = collect();
        $tenantAssignmentChecks = [];
        if ($showPropertySetupCard) {
            $tenantRequiredDocumentTypes = array_keys($this->requirements->labelsForEntity('tenant'));
            $propertySetupTenants = Tenant::query()
                ->with(['documents' => fn ($query) => $query->whereIn('document_type', $tenantRequiredDocumentTypes)])
                ->orderBy('full_name')
                ->get([
                    'id',
                    'full_name',
                    'phone_primary',
                    'email',
                    'personal_reference_name',
                    'personal_reference_phone',
                ]);

            $tenantAssignmentChecks = $propertySetupTenants
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
        }

        return view('charges.index', [
            'charges' => $charges,
            'payments' => $payments,
            'properties' => $propertiesQuery->get(['id', 'internal_name', 'internal_reference', 'tenant_id']),
            'chargeableProperties' => $chargeablePropertiesQuery->get([
                'id',
                'internal_name',
                'internal_reference',
                'tenant_id',
                'contract_starts_at',
                'contract_expires_at',
                'monthly_rent_price',
                'charge_day',
                'charge_tolerance_days',
            ]),
            'tenants' => $isTenant
                ? collect()
                : Tenant::query()
                    ->orderBy('full_name')
                    ->get(['id', 'full_name', 'email']),
            'propertySetupTenants' => $propertySetupTenants,
            'tenantAssignmentChecks' => $tenantAssignmentChecks,
            'typeOptions' => Charge::TYPE_LABELS,
            'paymentMethods' => ChargePayment::METHOD_LABELS,
            'stats' => $stats,
            'currentMonthLabel' => Carbon::create($now->year, $now->month, 1)->translatedFormat('M Y'),
            'selectedProperty' => $selectedProperty,
            'showPropertySetupCard' => $showPropertySetupCard,
            'canManageCharges' => !$isTenant,
            'canDeletePaidCharges' => !$isTenant && (bool) $user?->can('cobranza.eliminar_pagados'),
        ]);
    }

    public function updatePropertySetup(Request $request, Property $property): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'contract_starts_at' => ['nullable', 'date'],
            'contract_expires_at' => ['nullable', 'date'],
            'monthly_rent_price' => ['required', 'numeric', 'min:0'],
            'charge_day' => ['nullable', 'integer', 'between:1,31'],
            'charge_tolerance_days' => ['nullable', 'integer', 'min:0', 'max:31'],
            'rent_charge_plan' => ['nullable', 'array'],
            'rent_charge_plan.*.period_month' => ['required_with:rent_charge_plan', 'integer', 'between:1,12'],
            'rent_charge_plan.*.period_year' => ['required_with:rent_charge_plan', 'integer', 'between:2000,2200'],
            'rent_charge_plan.*.due_date' => ['required_with:rent_charge_plan', 'date'],
            'rent_charge_plan.*.amount' => ['required_with:rent_charge_plan', 'numeric', 'min:0.01'],
            'rent_charge_plan.*.concept' => ['nullable', 'string', 'max:190'],
            'rent_charge_plan.*.notes' => ['nullable', 'string', 'max:4000'],
            'rent_charge_plan.*.is_custom_amount' => ['nullable', 'boolean'],
            'force_assignment' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $contractStartsAt = $request->input('contract_starts_at');
            $contractExpiresAt = $request->input('contract_expires_at');

            if (filled($contractStartsAt) xor filled($contractExpiresAt)) {
                $validator->errors()->add(
                    'contract_starts_at',
                    'Debes capturar fecha de inicio y fecha de vencimiento del contrato.',
                );
            }

            if (filled($contractStartsAt) && filled($contractExpiresAt) && $contractStartsAt > $contractExpiresAt) {
                $validator->errors()->add(
                    'contract_starts_at',
                    'La fecha de inicio del contrato debe ser anterior o igual al vencimiento.',
                );
            }

            $chargePlanRows = collect((array) $request->input('rent_charge_plan', []))
                ->filter(fn ($row) => is_array($row));
            $duplicatePeriods = $chargePlanRows
                ->map(fn ($row) => sprintf('%s-%s', (string) ($row['period_year'] ?? ''), (string) ($row['period_month'] ?? '')))
                ->filter(fn ($period) => $period !== '-')
                ->duplicates();
            if ($duplicatePeriods->isNotEmpty()) {
                $validator->errors()->add(
                    'rent_charge_plan',
                    'La lista de pagos tiene periodos repetidos. Verifica la tabla de cargos.',
                );
            }

            $tenantId = $request->input('tenant_id');
            $monthlyRentPrice = (float) $request->input('monthly_rent_price', 0);
            if (filled($tenantId)) {
                if ($monthlyRentPrice <= 0) {
                    $validator->errors()->add(
                        'monthly_rent_price',
                        'El precio de renta mensual debe ser mayor a 0 para generar pagos.',
                    );
                }

                if (!filled($contractStartsAt) || !filled($contractExpiresAt)) {
                    $validator->errors()->add(
                        'contract_starts_at',
                        'Debes capturar el inicio y vencimiento del contrato para generar pagos.',
                    );
                }
            }
        });

        $validated = $validator->validateWithBag('propertySetup');
        $tenant = null;
        if (filled($validated['tenant_id'] ?? null)) {
            $tenant = Tenant::query()
                ->with(['documents' => fn ($query) => $query->whereIn('document_type', array_keys($this->requirements->labelsForEntity('tenant')))])
                ->find((int) $validated['tenant_id']);
        }

        if ($tenant && !$request->boolean('force_assignment')) {
            $missingRequirements = $this->getTenantAssignmentMissingRequirements($tenant);
            if (!empty($missingRequirements)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('warning', $this->formatTenantAssignmentWarning($tenant, $missingRequirements));
            }
        }

        $planRows = $this->normalizeBulkRows($validated['rent_charge_plan'] ?? null)->all();
        $resolvedChargeDay = $this->resolveChargeDay(
            $validated['contract_starts_at'] ?? null,
            array_key_exists('charge_day', $validated) ? (int) $validated['charge_day'] : null,
        );
        $resolvedToleranceDays = max(0, (int) ($validated['charge_tolerance_days'] ?? 0));
        if (empty($planRows)) {
            $planRows = $this->buildRentChargePlanFromContractData(
                $validated['contract_starts_at'] ?? null,
                $validated['contract_expires_at'] ?? null,
                (float) ($validated['monthly_rent_price'] ?? 0),
                $resolvedChargeDay,
            );
        }

        $created = 0;
        DB::transaction(function () use (&$created, $property, $tenant, $validated, $planRows, $request, $resolvedChargeDay, $resolvedToleranceDays): void {
            $property->forceFill([
                'tenant_id' => $tenant?->id,
                'current_tenant_name' => $tenant?->full_name,
                'contract_starts_at' => $validated['contract_starts_at'] ?? null,
                'contract_expires_at' => $validated['contract_expires_at'] ?? null,
                'monthly_rent_price' => (float) ($validated['monthly_rent_price'] ?? 0),
                'charge_day' => $resolvedChargeDay,
                'charge_tolerance_days' => $resolvedToleranceDays,
                'rent_charge_plan' => $planRows,
            ])->save();

            if (!$tenant) {
                return;
            }

            $preview = $this->buildBulkPreview($property, $planRows);
            foreach ($preview['rows'] as $row) {
                if ($row['already_exists']) {
                    continue;
                }

                Charge::create([
                    'property_id' => $row['property_id'],
                    'tenant_id' => $row['tenant_id'],
                    'type' => $row['type'],
                    'due_date' => $row['due_date'],
                    'amount' => $row['amount'],
                    'paid_amount' => 0,
                    'period_month' => $row['period_month'],
                    'period_year' => $row['period_year'],
                    'concept' => $row['concept'],
                    'notes' => $row['notes'] ?? 'Generado automaticamente por contrato.',
                    'status' => Charge::STATUS_PENDING,
                    'created_by' => $request->user()?->id,
                ]);
                $created++;
            }

            $this->syncPropertyPlanFromBulk($property, $preview['rows']);
        });

        $message = $tenant
            ? (
                $created > 0
                    ? "Configuracion guardada. Se generaron {$created} cargos de renta."
                    : 'Configuracion guardada. No se crearon cargos nuevos porque todos ya existen.'
            )
            : 'Configuracion guardada. Asigna un inquilino para poder generar cargos.';

        return redirect()->back()->with('success', $message);
    }

    public function store(StoreChargeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $charge = Charge::create([
            'property_id' => $validated['property_id'],
            'tenant_id' => $validated['tenant_id'],
            'type' => $validated['type'],
            'due_date' => $validated['due_date'],
            'amount' => $validated['amount'],
            'paid_amount' => 0,
            'period_month' => $validated['period_month'],
            'period_year' => $validated['period_year'],
            'concept' => $validated['concept'],
            'notes' => $validated['notes'] ?? null,
            'status' => Charge::STATUS_PENDING,
            'created_by' => $request->user()?->id,
        ]);

        $this->syncPropertyPlanWithCharge($charge);

        return redirect()
            ->route('charges.index', $this->chargesIndexRouteParamsFromRequest($request))
            ->with('success', 'Cargo creado correctamente.');
    }

    public function update(UpdateChargeRequest $request, Charge $charge): RedirectResponse
    {
        $validated = $request->validated();

        if (in_array($charge->status, [Charge::STATUS_PAID, Charge::STATUS_CANCELED], true)) {
            return redirect()
                ->route('charges.index', $this->chargesIndexRouteParamsFromRequest($request))
                ->with('error', 'Este cargo no se puede editar por su estado actual.');
        }

        $newAmount = (float) $validated['amount'];
        if ($newAmount < (float) $charge->paid_amount) {
            return redirect()->route('charges.index', $this->chargesIndexRouteParamsFromRequest($request))->with(
                'error',
                'El monto no puede ser menor al total ya pagado de este cargo.',
            );
        }

        DB::transaction(function () use ($charge, $validated): void {
            $charge->update([
                'type' => $validated['type'],
                'due_date' => $validated['due_date'],
                'amount' => $validated['amount'],
                'period_month' => $validated['period_month'],
                'period_year' => $validated['period_year'],
                'concept' => $validated['concept'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $charge->refreshPaymentStatus();
            $this->syncPropertyPlanWithCharge($charge);
        });

        return redirect()
            ->route('charges.index', $this->chargesIndexRouteParamsFromRequest($request))
            ->with('success', 'Cargo actualizado correctamente.');
    }

    public function show(Request $request, Charge $charge): View
    {
        $this->ensureChargeVisible($charge, $request->user());
        $charge->load([
            'tenant:id,full_name,email,phone_primary',
            'property.owners:id,name,phone,email,bank_name,clabe,account_holder',
            'payments' => fn ($query) => $query->latest('id'),
        ]);

        $canManageCharges = !$this->isTenantUser($request->user());
        $fallbackUrl = route('charges.index', $request->filled('property')
            ? ['property' => $request->string('property')->toString()]
            : []);
        $returnUrl = $this->resolveSafeReturnUrl(
            $request->query('return_to') ?: $request->headers->get('referer'),
            $fallbackUrl,
        );

        return view('charges.show', [
            'charge' => $charge,
            'paymentMethods' => ChargePayment::METHOD_LABELS,
            'canManageCharges' => $canManageCharges,
            'canDeleteCharge' => $canManageCharges
                && $charge->status !== Charge::STATUS_CANCELED
                && ($charge->status !== Charge::STATUS_PAID || (bool) $request->user()?->can('cobranza.eliminar_pagados')),
            'returnUrl' => $returnUrl,
        ]);
    }

    public function destroy(Request $request, Charge $charge): RedirectResponse
    {
        if ($this->isTenantUser($request->user())) {
            abort(403);
        }

        if ($charge->status === Charge::STATUS_CANCELED) {
            return redirect()->back()->with('error', 'Este cargo no se puede eliminar por su estado actual.');
        }

        if ($charge->status === Charge::STATUS_PAID && !$request->user()?->can('cobranza.eliminar_pagados')) {
            abort(403, 'No tienes permiso para eliminar cargos pagados.');
        }

        $validated = $request->validate([
            'deletion_note' => ['required', 'string', 'max:4000'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $fallbackUrl = route('charges.index', $this->chargesIndexRouteParamsFromRequest($request));
        $returnUrl = $this->resolveSafeReturnUrl($validated['return_to'] ?? null, $fallbackUrl);

        DB::transaction(function () use ($charge, $validated, $request): void {
            $this->logChargeDeletion($charge, $validated['deletion_note'], $request->user()?->id);
            $this->removeChargeFromPropertyPlan($charge);
            $charge->delete();
        });

        return redirect()->to($returnUrl)->with('success', 'Cargo eliminado correctamente.');
    }

    public function storePayment(StoreChargePaymentRequest $request, Charge $charge): RedirectResponse
    {
        if ($this->isTenantUser($request->user())) {
            abort(403);
        }
        if (!in_array($charge->status, [Charge::STATUS_PENDING, Charge::STATUS_PARTIAL, Charge::STATUS_IN_VALIDATION], true)) {
            return redirect()->back()->with('error', 'Este cargo ya no admite pagos.');
        }

        $validated = $request->validated();
        $amount = (float) $validated['amount'];
        if ($amount > $charge->outstanding_amount) {
            return redirect()->back()->with('error', 'El monto no puede ser mayor al saldo pendiente.');
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store("charges/{$charge->id}/payments", 'public');
        }

        $becamePaid = false;
        DB::transaction(function () use ($charge, $validated, $amount, $receiptPath, $request, &$becamePaid): void {
            $charge->payments()->create([
                'amount' => $amount,
                'currency' => strtolower((string) config('services.stripe.currency', 'mxn')),
                'status' => ChargePayment::STATUS_SUCCEEDED,
                'source' => ChargePayment::SOURCE_ADMIN,
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'receipt_path' => $receiptPath,
                'notes' => $validated['notes'] ?? null,
                'payment_date' => $validated['payment_date'],
                'paid_at' => Carbon::parse($validated['payment_date'])->endOfDay(),
                'registered_by' => $request->user()?->id,
            ]);

            $becamePaid = $charge->refreshPaymentStatus();
        });

        if ($becamePaid) {
            $this->sendCompletedMail($charge);
        }

        return redirect()->back()->with('success', 'Pago registrado correctamente.');
    }

    public function validatePayment(Request $request, Charge $charge, ChargePayment $payment): RedirectResponse
    {
        if ($this->isTenantUser($request->user())) {
            abort(403);
        }
        if ($payment->charge_id !== $charge->id) {
            abort(404);
        }

        if ($payment->status !== ChargePayment::STATUS_PENDING_VALIDATION) {
            return redirect()->back()->with('error', 'Este pago ya fue validado o rechazado.');
        }

        $validated = $request->validate([
            'validation_notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $becamePaid = false;
        DB::transaction(function () use ($payment, $charge, $validated, $request, &$becamePaid): void {
            $payment->update([
                'status' => ChargePayment::STATUS_SUCCEEDED,
                'validated_by' => $request->user()?->id,
                'validation_notes' => $validated['validation_notes'] ?? null,
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            $becamePaid = $charge->refreshPaymentStatus();
        });

        if ($becamePaid) {
            $this->sendCompletedMail($charge);
        }

        return redirect()->back()->with('success', 'Comprobante validado correctamente.');
    }

    public function sendReminder(SendChargeReminderRequest $request, Charge $charge): RedirectResponse
    {
        if ($this->isTenantUser($request->user())) {
            abort(403);
        }
        $validated = $request->validated();
        $channel = (string) $validated['channel'];
        $daysBefore = (int) $validated['days_before'];
        $message = $validated['message'] ?? null;

        if ($channel === 'whatsapp') {
            return redirect()->back()->with('warning', 'WhatsApp aun no esta integrado. Usa correo por ahora.');
        }

        $charge->loadMissing(['tenant:id,full_name,email']);
        if (!filled($charge->tenant?->email)) {
            return redirect()->back()->with('error', 'El inquilino no tiene correo configurado.');
        }
        if (!NotificationSettings::allows(NotificationSettings::ROLE_TENANT, NotificationSettings::EVENT_PAYMENT_REMINDER)) {
            return redirect()->back()->with('warning', 'Las notificaciones de recordatorio de pago para inquilinos están desactivadas.');
        }

        Mail::to($charge->tenant->email)->send(new ChargeReminderMail($charge, $daysBefore, $message));

        return redirect()->back()->with('success', 'Recordatorio enviado por correo.');
    }

    public function previewBulk(GenerateChargesRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $property = Property::query()
            ->with('tenant:id,full_name')
            ->findOrFail((int) $validated['property_id']);
        $property = $this->applyBulkPropertyConfiguration($property, $validated, false);

        if ((float) $property->monthly_rent_price <= 0) {
            return response()->json([
                'message' => 'El precio de renta mensual debe ser mayor a 0 para generar pagos.',
                'preview' => [
                    'rows' => [],
                    'summary' => [
                        'total' => 0,
                        'already_exists' => 0,
                        'to_create' => 0,
                    ],
                ],
            ], 422);
        }

        $rowsSource = $this->resolveBulkRowsSource($property, $validated);

        return response()->json([
            'preview' => $this->buildBulkPreview($property, $rowsSource),
        ]);
    }

    public function storeBulk(GenerateChargesRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $property = Property::query()
            ->with('tenant:id,full_name')
            ->findOrFail((int) $validated['property_id']);

        if (!$property->tenant_id) {
            return redirect()->route('charges.index', $this->chargesIndexRouteParamsFromRequest($request))->with(
                'warning',
                'La propiedad no tiene inquilino activo. Asigna un inquilino antes de generar cargos.',
            );
        }

        $property = $this->applyBulkPropertyConfiguration($property, $validated, false);
        if ((float) $property->monthly_rent_price <= 0) {
            return redirect()->route('charges.index', $this->chargesIndexRouteParamsFromRequest($request))->with(
                'warning',
                'El precio de renta mensual debe ser mayor a 0 para generar pagos.',
            );
        }

        $rowsSource = $this->resolveBulkRowsSource($property, $validated);
        $preview = $this->buildBulkPreview($property, $rowsSource);

        if (empty($preview['rows'])) {
            return redirect()->route('charges.index', $this->chargesIndexRouteParamsFromRequest($request))->with(
                'warning',
                'No hay cargos por generar para esta propiedad.',
            );
        }

        $created = 0;
        DB::transaction(function () use ($preview, &$created, $request, $property, $validated): void {
            $this->applyBulkPropertyConfiguration($property, $validated, true);

            foreach ($preview['rows'] as $row) {
                if ($row['already_exists']) {
                    continue;
                }

                Charge::create([
                    'property_id' => $row['property_id'],
                    'tenant_id' => $row['tenant_id'],
                    'type' => Charge::TYPE_RENT,
                    'due_date' => $row['due_date'],
                    'amount' => $row['amount'],
                    'paid_amount' => 0,
                    'period_month' => $row['period_month'],
                    'period_year' => $row['period_year'],
                    'concept' => $row['concept'],
                    'notes' => $row['notes'] ?? 'Generado automaticamente por contrato.',
                    'status' => Charge::STATUS_PENDING,
                    'created_by' => $request->user()?->id,
                ]);
                $created++;
            }

            $this->syncPropertyPlanFromBulk($property, $preview['rows']);
        });

        $message = $created > 0
            ? "Se generaron {$created} cargos."
            : 'No se crearon cargos nuevos porque todos ya existen.';

        return redirect()
            ->route('charges.index', $this->chargesIndexRouteParamsFromRequest($request))
            ->with('success', $message);
    }

    private function buildBulkPreview(Property $property, ?array $requestRows = null): array
    {
        if (!$property->tenant_id) {
            return [
                'rows' => [],
                'summary' => [
                    'total' => 0,
                    'already_exists' => 0,
                    'to_create' => 0,
                ],
            ];
        }

        $rowsSource = $this->normalizeBulkRows($requestRows);
        if ($rowsSource->isEmpty()) {
            $rowsSource = $this->getPropertyPlanRows($property);
        }

        $rows = [];
        foreach ($rowsSource as $row) {
            $rowType = (string) ($row['type'] ?? Charge::TYPE_RENT);
            $periodMonth = (int) $row['period_month'];
            $periodYear = (int) $row['period_year'];
            $amount = (float) $row['amount'];
            if ($amount <= 0) {
                continue;
            }

            $alreadyExistsQuery = Charge::query()
                ->where('property_id', $property->id)
                ->where('tenant_id', $property->tenant_id)
                ->where('type', $rowType);
            if ($rowType === Charge::TYPE_RENT) {
                $alreadyExistsQuery
                    ->where('period_month', $periodMonth)
                    ->where('period_year', $periodYear);
            } else {
                $alreadyExistsQuery
                    ->whereDate('due_date', (string) $row['due_date'])
                    ->where('concept', (string) $row['concept']);
            }
            $alreadyExists = $alreadyExistsQuery->exists();

            $rows[] = [
                'property_id' => $property->id,
                'property_name' => $property->internal_name,
                'tenant_id' => (int) $property->tenant_id,
                'tenant_name' => $property->tenant?->full_name ?? '-',
                'type' => $rowType,
                'type_label' => Charge::TYPE_LABELS[$rowType] ?? ucfirst(str_replace('_', ' ', $rowType)),
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'due_date' => (string) $row['due_date'],
                'amount' => $amount,
                'concept' => (string) $row['concept'],
                'notes' => $row['notes'] ?? null,
                'already_exists' => $alreadyExists,
            ];
        }

        return [
            'rows' => $rows,
            'summary' => [
                'total' => count($rows),
                'already_exists' => collect($rows)->where('already_exists', true)->count(),
                'to_create' => collect($rows)->where('already_exists', false)->count(),
            ],
        ];
    }

    private function resolveBulkRowsSource(Property $property, array $validated): ?array
    {
        $providedRows = $this->normalizeBulkRows($validated['rows'] ?? null)->all();
        if (!empty($providedRows)) {
            return $providedRows;
        }

        $contractStartsAt = $validated['contract_starts_at'] ?? null;
        $contractExpiresAt = $validated['contract_expires_at'] ?? null;
        if (!filled($contractStartsAt) || !filled($contractExpiresAt)) {
            return null;
        }

        $monthlyRentPrice = (float) ($validated['monthly_rent_price'] ?? $property->monthly_rent_price ?? 0);
        $resolvedChargeDay = $this->resolveChargeDay(
            $contractStartsAt,
            array_key_exists('charge_day', $validated)
                ? (int) $validated['charge_day']
                : (filled($property->charge_day) ? (int) $property->charge_day : null),
        );

        return $this->buildRentChargePlanFromContractData(
            (string) $contractStartsAt,
            (string) $contractExpiresAt,
            $monthlyRentPrice,
            $resolvedChargeDay,
        );
    }

    private function getPropertyPlanRows(Property $property): Collection
    {
        $storedRows = $this->normalizeBulkRows($property->rent_charge_plan);
        if ($storedRows->isNotEmpty()) {
            return $storedRows;
        }

        return $this->generateFallbackRowsFromContract($property);
    }

    private function normalizeBulkRows(?array $rows): Collection
    {
        return collect($rows ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): ?array {
                $periodMonth = (int) ($row['period_month'] ?? 0);
                $periodYear = (int) ($row['period_year'] ?? 0);
                if ($periodMonth < 1 || $periodMonth > 12 || $periodYear < 2000) {
                    return null;
                }

                try {
                    $dueDate = Carbon::parse((string) ($row['due_date'] ?? now()->toDateString()))
                        ->toDateString();
                } catch (\Throwable) {
                    $dueDate = now()->toDateString();
                }
                $amount = (float) ($row['amount'] ?? 0);
                $concept = trim((string) ($row['concept'] ?? ''));
                $type = (string) ($row['type'] ?? Charge::TYPE_RENT);
                if (!array_key_exists($type, Charge::TYPE_LABELS)) {
                    $type = Charge::TYPE_RENT;
                }

                return [
                    'type' => $type,
                    'period_month' => $periodMonth,
                    'period_year' => $periodYear,
                    'due_date' => $dueDate,
                    'amount' => $amount,
                    'concept' => $concept !== ''
                        ? $concept
                        : ($type === Charge::TYPE_RENT
                            ? $this->buildRentConcept($periodMonth, $periodYear)
                            : (Charge::TYPE_LABELS[$type] ?? 'Cargo')),
                    'notes' => filled($row['notes'] ?? null) ? (string) $row['notes'] : null,
                    'is_custom_amount' => (bool) ($row['is_custom_amount'] ?? false),
                ];
            })
            ->filter()
            ->values();
    }

    private function generateFallbackRowsFromContract(Property $property): Collection
    {
        if (
            !$property->contract_starts_at ||
            !$property->contract_expires_at ||
            (float) $property->monthly_rent_price <= 0
        ) {
            return collect();
        }

        $startsAt = $property->contract_starts_at->copy();
        $contractDay = $this->normalizeChargeDay($property->charge_day ? (int) $property->charge_day : null)
            ?? (int) $startsAt->day;
        $startsAt = $startsAt->startOfMonth();
        $expiresAt = $property->contract_expires_at->copy()->startOfMonth();
        if ($startsAt->gt($expiresAt)) {
            return collect();
        }

        $rows = [];
        $cursor = $startsAt->copy();
        while ($cursor->lte($expiresAt)) {
            $periodMonth = (int) $cursor->month;
            $periodYear = (int) $cursor->year;
            $rows[] = [
                'type' => Charge::TYPE_RENT,
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'due_date' => $cursor->copy()->day(min($contractDay, $cursor->daysInMonth))->toDateString(),
                'amount' => (float) $property->monthly_rent_price,
                'concept' => $this->buildRentConcept($periodMonth, $periodYear),
                'notes' => null,
                'is_custom_amount' => false,
            ];
            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        return collect($rows);
    }

    private function buildRentChargePlanFromContractData(
        ?string $contractStartsAt,
        ?string $contractExpiresAt,
        float $monthlyRentPrice,
        ?int $chargeDay = null,
    ): array {
        if (!filled($contractStartsAt) || !filled($contractExpiresAt) || $monthlyRentPrice <= 0) {
            return [];
        }

        try {
            $startsAt = Carbon::parse($contractStartsAt)->startOfDay();
            $expiresAt = Carbon::parse($contractExpiresAt)->startOfDay();
        } catch (\Throwable) {
            return [];
        }

        if ($startsAt->gt($expiresAt)) {
            return [];
        }

        $contractDay = $this->normalizeChargeDay($chargeDay) ?? (int) $startsAt->day;
        $cursor = $startsAt->copy()->startOfMonth();
        $end = $expiresAt->copy()->startOfMonth();
        $rows = [];

        while ($cursor->lte($end)) {
            $periodMonth = (int) $cursor->month;
            $periodYear = (int) $cursor->year;
            $rows[] = [
                'type' => Charge::TYPE_RENT,
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'due_date' => $cursor->copy()->day(min($contractDay, $cursor->daysInMonth))->toDateString(),
                'amount' => round($monthlyRentPrice, 2),
                'concept' => $this->buildRentConcept($periodMonth, $periodYear),
                'notes' => null,
                'is_custom_amount' => false,
            ];

            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        return $rows;
    }

    private function resolveChargeDay(?string $contractStartsAt, ?int $requestedChargeDay): ?int
    {
        $normalizedRequestedDay = $this->normalizeChargeDay($requestedChargeDay);
        if ($normalizedRequestedDay !== null) {
            return $normalizedRequestedDay;
        }

        if (!filled($contractStartsAt)) {
            return null;
        }

        try {
            $contractStartDay = (int) Carbon::parse($contractStartsAt)->day;
        } catch (\Throwable) {
            return null;
        }

        return $this->normalizeChargeDay($contractStartDay);
    }

    private function normalizeChargeDay(?int $chargeDay): ?int
    {
        if ($chargeDay === null || $chargeDay < 1 || $chargeDay > 31) {
            return null;
        }

        return $chargeDay;
    }

    private function applyBulkPropertyConfiguration(Property $property, array $validated, bool $persist): Property
    {
        $payload = [];

        if (array_key_exists('contract_starts_at', $validated)) {
            $payload['contract_starts_at'] = $validated['contract_starts_at'] ?? null;
        }

        if (array_key_exists('contract_expires_at', $validated)) {
            $payload['contract_expires_at'] = $validated['contract_expires_at'] ?? null;
        }

        if (array_key_exists('monthly_rent_price', $validated)) {
            $payload['monthly_rent_price'] = (float) ($validated['monthly_rent_price'] ?? 0);
        }

        if (array_key_exists('charge_day', $validated) || array_key_exists('contract_starts_at', $validated)) {
            $contractStartsAt = array_key_exists('contract_starts_at', $validated)
                ? $validated['contract_starts_at']
                : $property->contract_starts_at?->toDateString();
            $requestedChargeDay = array_key_exists('charge_day', $validated)
                ? (filled($validated['charge_day']) ? (int) $validated['charge_day'] : null)
                : (filled($property->charge_day) ? (int) $property->charge_day : null);

            $payload['charge_day'] = $this->resolveChargeDay($contractStartsAt, $requestedChargeDay);
        }

        if (array_key_exists('charge_tolerance_days', $validated)) {
            $payload['charge_tolerance_days'] = max(0, (int) ($validated['charge_tolerance_days'] ?? 0));
        }

        if (!empty($payload)) {
            $property->forceFill($payload);
            if ($persist) {
                $property->save();
            }
        }

        return $property;
    }

    private function syncPropertyPlanFromBulk(Property $property, array $rows): void
    {
        $planRows = $this->normalizeBulkRows($property->rent_charge_plan)
            ->keyBy(fn (array $row) => $this->periodKey((int) $row['period_year'], (int) $row['period_month']));

        foreach ($rows as $row) {
            if (($row['type'] ?? Charge::TYPE_RENT) !== Charge::TYPE_RENT) {
                continue;
            }

            $periodMonth = (int) ($row['period_month'] ?? 0);
            $periodYear = (int) ($row['period_year'] ?? 0);
            if ($periodMonth < 1 || $periodMonth > 12 || $periodYear < 2000) {
                continue;
            }

            $planRows->put($this->periodKey($periodYear, $periodMonth), [
                'type' => Charge::TYPE_RENT,
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'due_date' => (string) ($row['due_date'] ?? ''),
                'amount' => (float) ($row['amount'] ?? 0),
                'concept' => (string) ($row['concept'] ?? $this->buildRentConcept($periodMonth, $periodYear)),
                'notes' => filled($row['notes'] ?? null) ? (string) $row['notes'] : null,
                'is_custom_amount' => true,
            ]);
        }

        $property->forceFill([
            'rent_charge_plan' => $planRows
                ->values()
                ->sortBy(fn (array $row) => ((int) $row['period_year'] * 100) + (int) $row['period_month'])
                ->values()
                ->all(),
        ])->save();
    }

    private function syncPropertyPlanWithCharge(Charge $charge): void
    {
        if ($charge->type !== Charge::TYPE_RENT || !$charge->property_id) {
            return;
        }

        $property = Property::query()->find($charge->property_id);
        if (!$property) {
            return;
        }

        $planRows = $this->normalizeBulkRows($property->rent_charge_plan)
            ->keyBy(fn (array $row) => $this->periodKey((int) $row['period_year'], (int) $row['period_month']));
        $periodKey = $this->periodKey((int) $charge->period_year, (int) $charge->period_month);

        $planRows->put($periodKey, [
            'type' => Charge::TYPE_RENT,
            'period_month' => (int) $charge->period_month,
            'period_year' => (int) $charge->period_year,
            'due_date' => $charge->due_date?->toDateString(),
            'amount' => (float) $charge->amount,
            'concept' => (string) $charge->concept,
            'notes' => $charge->notes,
            'is_custom_amount' => true,
        ]);

        $property->forceFill([
            'rent_charge_plan' => $planRows
                ->values()
                ->sortBy(fn (array $row) => ((int) $row['period_year'] * 100) + (int) $row['period_month'])
                ->values()
                ->all(),
        ])->save();
    }

    private function removeChargeFromPropertyPlan(Charge $charge): void
    {
        if ($charge->type !== Charge::TYPE_RENT || !$charge->property_id) {
            return;
        }

        $property = Property::query()->find($charge->property_id);
        if (!$property) {
            return;
        }

        $periodKey = $this->periodKey((int) $charge->period_year, (int) $charge->period_month);
        $planRows = $this->normalizeBulkRows($property->rent_charge_plan)
            ->keyBy(fn (array $row) => $this->periodKey((int) $row['period_year'], (int) $row['period_month']));

        if (!$planRows->has($periodKey)) {
            return;
        }

        $planRows->forget($periodKey);

        $property->forceFill([
            'rent_charge_plan' => $planRows
                ->values()
                ->sortBy(fn (array $row) => ((int) $row['period_year'] * 100) + (int) $row['period_month'])
                ->values()
                ->all(),
        ])->save();
    }

    private function logChargeDeletion(Charge $charge, string $note, ?int $userId): void
    {
        if (!$charge->property_id) {
            return;
        }

        PropertyChangeLog::create([
            'property_id' => $charge->property_id,
            'user_id' => $userId,
            'change_set' => [
                'charge_deleted' => [
                    'old' => [
                        'charge_uuid' => $charge->uuid,
                        'type' => $charge->type,
                        'concept' => $charge->concept,
                        'amount' => (float) $charge->amount,
                        'paid_amount' => (float) $charge->paid_amount,
                        'due_date' => $charge->due_date?->toDateString(),
                        'period_month' => (int) $charge->period_month,
                        'period_year' => (int) $charge->period_year,
                        'status' => $charge->status,
                        'notes' => $charge->notes,
                    ],
                    'new' => [
                        'deleted' => true,
                        'deletion_note' => trim($note),
                        'deleted_at' => now()->toDateTimeString(),
                    ],
                ],
            ],
            'changed_at' => now(),
        ]);
    }

    private function periodKey(int $periodYear, int $periodMonth): string
    {
        return sprintf('%04d-%02d', $periodYear, $periodMonth);
    }

    private function buildRentConcept(int $periodMonth, int $periodYear): string
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

        return 'Renta ' . ($monthNames[$periodMonth] ?? (string) $periodMonth) . ' ' . $periodYear;
    }

    private function chargesIndexRouteParamsFromRequest(Request $request): array
    {
        $propertyContext = trim((string) $request->input('property_context', ''));
        if ($propertyContext === '') {
            return [];
        }

        $exists = Property::query()->where('uuid', $propertyContext)->exists();
        if (!$exists) {
            return [];
        }

        return ['property' => $propertyContext];
    }

    private function resolveSafeReturnUrl(?string $candidate, string $fallback): string
    {
        $candidate = trim((string) $candidate);
        if ($candidate === '') {
            return $fallback;
        }

        if (str_starts_with($candidate, '/') && !str_starts_with($candidate, '//')) {
            return url($candidate);
        }

        $candidateParts = parse_url($candidate);
        $applicationParts = parse_url(url('/'));
        if (!is_array($candidateParts) || !is_array($applicationParts)) {
            return $fallback;
        }

        $sameOrigin = ($candidateParts['scheme'] ?? null) === ($applicationParts['scheme'] ?? null)
            && ($candidateParts['host'] ?? null) === ($applicationParts['host'] ?? null)
            && ($candidateParts['port'] ?? null) === ($applicationParts['port'] ?? null);

        return $sameOrigin ? $candidate : $fallback;
    }

    private function sendCompletedMail(Charge $charge): void
    {
        $charge->loadMissing(['tenant:id,email,full_name']);
        if (!filled($charge->tenant?->email)) {
            return;
        }
        if (!NotificationSettings::allows(NotificationSettings::ROLE_TENANT, NotificationSettings::EVENT_PAYMENT_CONFIRMED)) {
            return;
        }

        Mail::to($charge->tenant->email)->send(new ChargeCompletedMail($charge));
    }

    private function isTenantUser(?User $user): bool
    {
        return (bool) $user && ($user->hasRole('inquilino') || $user->hasRole('tenant'));
    }

    private function ensureChargeVisible(Charge $charge, ?User $user): void
    {
        if (!$this->isTenantUser($user)) {
            return;
        }

        $visible = Charge::query()
            ->where('id', $charge->id)
            ->whereHas('property.tenant', fn ($query) => $query->where('email', $user->email))
            ->exists();

        if (!$visible) {
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

            if (!$hasUploadedFile || $isRejectedOrExpired) {
                $missing[] = 'Documento: ' . $label;
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
}
