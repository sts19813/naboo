<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantSuggestionRequest;
use App\Models\Tenant;
use App\Models\TenantSuggestion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantSuggestionController extends Controller
{
    public function create(Request $request): View
    {
        $this->tenantFor($request->user());

        return view('tenant-suggestions.create');
    }

    public function store(StoreTenantSuggestionRequest $request): RedirectResponse
    {
        $tenant = $this->tenantFor($request->user());

        TenantSuggestion::create([
            'tenant_id' => $tenant->id,
            'sender_user_id' => $request->user()->id,
            'title' => $request->validated('title'),
            'message' => $request->validated('message'),
        ]);

        return redirect()
            ->route('tenant-suggestions.create')
            ->with('success', 'Tu sugerencia fue enviada correctamente. Gracias por compartirla.');
    }

    public function index(Request $request): View
    {
        $this->ensureAdministrator($request->user());

        $suggestions = TenantSuggestion::query()
            ->with([
                'tenant:id,full_name,email',
                'tenant.properties:id,tenant_id,internal_name,internal_reference',
                'sender:id,name,email',
            ])
            ->latest()
            ->paginate(20);

        return view('mailbox.index', compact('suggestions'));
    }

    private function tenantFor(?User $user): Tenant
    {
        if (! $user || ! ($user->hasRole('inquilino') || $user->hasRole('tenant'))) {
            abort(403);
        }

        $tenant = Tenant::query()
            ->where('email', $user->email)
            ->where('is_active', true)
            ->first();

        abort_unless($tenant, 403, 'No encontramos un perfil de inquilino activo asociado a tu usuario.');

        return $tenant;
    }

    private function ensureAdministrator(?User $user): void
    {
        abort_unless(
            $user && ($user->hasRole('administrador') || $user->hasRole('admin')),
            403,
        );
    }
}
