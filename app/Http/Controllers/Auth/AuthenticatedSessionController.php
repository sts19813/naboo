<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        if (! $user || ! $user->hasSystemAccess()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('warning', 'Tu usuario aún no tiene acceso al sistema. Espera a que se te asigne un rol o permiso.');
        }

        $isTenant = $user->hasRole('inquilino') || $user->hasRole('tenant');
        $isTechnician = $user->hasRole('tecnico') || $user->hasRole('technician') || $user->hasRole('proveedor') || $user->hasRole('provider') || $user->hasRole('revisor de tickets');
        $isAdmin = $user->hasRole('administrador') || $user->hasRole('admin');
        $isAdvisor = ! $isAdmin && ($user->hasRole('asesores') || $user->hasRole('asesor') || $user->can('propiedades.ver_propias'));

        $defaultRoute = match (true) {
            $isTenant || $isTechnician => 'maintenance.index',
            $isAdvisor => 'advisor.tasks.index',
            default => 'dashboard',
        };

        if (($user->hasAnyRole(['proveedor', 'provider']) && ! $isAdmin) || $user->hasRole('revisor de tickets')) {
            $request->session()->forget('url.intended');
            return redirect()->route('maintenance.index');
        }

        return redirect()->intended(route($defaultRoute, absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
