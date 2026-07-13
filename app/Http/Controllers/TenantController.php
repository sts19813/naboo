<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRequest;
use App\Models\Charge;
use App\Models\Tenant;
use App\Models\TenantDocument;
use App\Models\User;
use App\Services\DossierDocumentRequirementService;
use App\Support\NotificationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class TenantController extends Controller
{
    public function __construct(private readonly DossierDocumentRequirementService $requirements)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $tenants = Tenant::query()
            ->withCount([
                'charges as total_rent_charges_count' => fn ($query) => $query
                    ->where('type', Charge::TYPE_RENT)
                    ->where('status', '!=', Charge::STATUS_CANCELED),
                'charges as paid_rent_charges_count' => fn ($query) => $query
                    ->where('type', Charge::TYPE_RENT)
                    ->where('status', Charge::STATUS_PAID),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_primary', 'like', "%{$search}%")
                        ->orWhere('phone_secondary', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return view('tenants.index', [
            'tenants' => $tenants,
            'search' => $search,
            'dossierStatuses' => Tenant::DOSSIER_STATUS_LABELS,
        ]);
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tenant = Tenant::create($this->tenantPayload($validated));

        $this->ensureDossierDocuments($tenant);
        [, $password] = $this->syncTenantAccess($tenant, $validated['access_password'] ?? null);
        $message = 'El inquilino se creo correctamente.';
        if ($password !== null) {
            $message .= ' Acceso: ' . $tenant->email . ' / ' . $password;
        }

        return redirect()
            ->route('tenants.index')
            ->with('success', $message);
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load([
            'properties' => fn ($query) => $query
                ->with(['type:id,name', 'zone:id,name'])
                ->latest('properties.created_at'),
        ])->loadCount([
            'properties',
            'documents',
            'charges as total_rent_charges_count' => fn ($query) => $query
                ->where('type', Charge::TYPE_RENT)
                ->where('status', '!=', Charge::STATUS_CANCELED),
            'charges as paid_rent_charges_count' => fn ($query) => $query
                ->where('type', Charge::TYPE_RENT)
                ->where('status', Charge::STATUS_PAID),
        ]);

        return view('tenants.show', [
            'tenant' => $tenant,
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        return view('tenants.edit', [
            'tenant' => $tenant,
            'dossierStatuses' => Tenant::DOSSIER_STATUS_LABELS,
        ]);
    }

    public function update(StoreTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validated();
        $previousEmail = $tenant->email;
        $tenant->update($this->tenantPayload($validated));

        $this->ensureDossierDocuments($tenant);
        [, $password] = $this->syncTenantAccess($tenant, $validated['access_password'] ?? null, $previousEmail);
        $message = 'El inquilino se actualizo correctamente.';
        if ($password !== null) {
            $message .= ' Acceso: ' . $tenant->email . ' / ' . $password;
        }

        return redirect()
            ->route('tenants.index')
            ->with('success', $message);
    }

    private function tenantPayload(array $validated): array
    {
        return [
            'full_name' => $validated['full_name'],
            'phone_primary' => $validated['phone_primary'],
            'phone_secondary' => $validated['phone_secondary'] ?? null,
            'email' => $validated['email'],
            'rfc' => $validated['rfc'] ?? null,
            'curp' => $validated['curp'] ?? null,
            'employer' => $validated['employer'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'monthly_income' => $validated['monthly_income'] ?? null,
            'employment_years' => $validated['employment_years'] ?? null,
            'personal_reference_name' => $validated['personal_reference_name'] ?? null,
            'personal_reference_phone' => $validated['personal_reference_phone'] ?? null,
            'work_reference_name' => $validated['work_reference_name'] ?? null,
            'work_reference_phone' => $validated['work_reference_phone'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'previous_address' => $validated['previous_address'] ?? null,
            'current_address' => $validated['current_address'] ?? null,
            'dossier_status' => $validated['dossier_status'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];
    }

    private function syncTenantAccess(Tenant $tenant, ?string $password, ?string $fallbackEmail = null): array
    {
        $email = trim((string) $tenant->email);
        $plainPassword = filled($password) ? (string) $password : null;
        $user = User::query()->where('email', $email)->first();

        if (!$user && filled($fallbackEmail)) {
            $user = User::query()->where('email', trim((string) $fallbackEmail))->first();
        }

        if (!$user) {
            $plainPassword = $plainPassword ?: Str::random(12);
            $user = User::create([
                'name' => $tenant->full_name,
                'email' => $email,
                'password' => Hash::make($plainPassword),
            ]);
        } else {
            $data = [
                'name' => $tenant->full_name,
                'email' => $email,
            ];
            if ($plainPassword !== null) {
                $data['password'] = Hash::make($plainPassword);
            }
            $user->update($data);
        }

        $this->ensureTenantRole($user);
        if ($plainPassword !== null) {
            $this->sendTenantAccessEmail($user->email, $plainPassword);
        }

        return [$user, $plainPassword];
    }

    private function ensureTenantRole(User $user): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'inquilino',
            'guard_name' => 'web',
        ]);
        if (!$user->hasRole($role->name)) {
            $user->assignRole($role);
        }
    }

    private function sendTenantAccessEmail(string $email, string $password): void
    {
        if (!NotificationSettings::allows(NotificationSettings::ROLE_TENANT, NotificationSettings::EVENT_ACCOUNT_CREATED)) {
            return;
        }

        try {
            Mail::raw(
                "Tu cuenta de inquilino fue creada.\n\nAcceso:\nCorreo: {$email}\nContraseña: {$password}\n\nPortal: " . url('/login'),
                fn($mail) => $mail->to($email)->subject('Acceso al sistema')
            );
        } catch (\Throwable) {
        }
    }

    private function ensureDossierDocuments(Tenant $tenant): void
    {
        foreach ($this->requirements->labelsForEntity('tenant') as $documentType => $label) {
            $existingDocument = $tenant->documents()
                ->where('document_type', $documentType)
                ->first();

            if ($existingDocument) {
                $existingDocument->update(['label' => $label]);
                continue;
            }

            $tenant->documents()->create([
                'document_type' => $documentType,
                'label' => $label,
                'status' => TenantDocument::STATUS_PENDING,
                'file_path' => null,
                'uploaded_at' => null,
                'expires_at' => null,
            ]);
        }
    }
}
