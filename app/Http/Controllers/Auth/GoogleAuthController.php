<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = (string) config('services.google.client_id');
        $callback = $this->resolveCallbackUrl();

        if ($clientId === '' || $callback === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'Google login no esta configurado en este entorno.',
            ]);
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $callback,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'select_account consent',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_oauth_state', '');
        $returnedState = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $returnedState) || $code === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'No se pudo validar la autenticacion con Google.',
            ]);
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => $this->resolveCallbackUrl(),
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResponse->ok()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google rechazo el intercambio de token.',
            ]);
        }

        $accessToken = (string) $tokenResponse->json('access_token');

        if ($accessToken === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'No se obtuvo token de acceso desde Google.',
            ]);
        }

        $profileResponse = Http::withToken($accessToken)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (! $profileResponse->ok()) {
            return redirect()->route('login')->withErrors([
                'email' => 'No se pudo obtener el perfil de Google.',
            ]);
        }

        $email = Str::lower((string) $profileResponse->json('email'));
        $name = (string) $profileResponse->json('name', '');
        $googleId = (string) $profileResponse->json('sub', '');
        $refreshToken = (string) $tokenResponse->json('refresh_token', '');
        $expiresIn = (int) $tokenResponse->json('expires_in', 0);
        $expiresAt = $expiresIn > 0 ? now()->addSeconds($expiresIn) : null;

        if ($email === '' || $googleId === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'La cuenta de Google no devolvio identificadores validos.',
            ]);
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $name !== '' ? $name : 'Usuario',
                'email' => $email,
                'password' => Hash::make(Str::password(32)),
                'email_verified_at' => now(),
            ]);
        }

        $user->forceFill([
            'google_id' => $googleId,
            'google_access_token' => $accessToken,
            'google_token_expires_at' => $expiresAt,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        if ($refreshToken !== '') {
            $user->forceFill(['google_refresh_token' => $refreshToken]);
        }

        if ($user->name === 'Usuario' && $name !== '') {
            $user->forceFill(['name' => $name]);
        }

        $user->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        $isTenant = $user->hasRole('inquilino') || $user->hasRole('tenant');
        $isTechnician = $user->hasRole('tecnico') || $user->hasRole('technician');
        $isAdmin = $user->hasRole('administrador') || $user->hasRole('admin');
        $isAdvisor = ! $isAdmin && ($user->hasRole('asesores') || $user->hasRole('asesor') || $user->can('propiedades.ver_propias'));

        $defaultRoute = match (true) {
            $isTenant || $isTechnician => 'maintenance.index',
            $isAdvisor => 'advisor.tasks.index',
            default => 'dashboard',
        };

        return redirect()->intended(route($defaultRoute, absolute: false));
    }

    private function resolveCallbackUrl(): string
    {
        $configured = (string) config('services.google.redirect');

        if ($configured !== '') {
            return $configured;
        }

        return route('auth.google.callback');
    }
}
