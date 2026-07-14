<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CentralSsoController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $baseUrl = rtrim((string) config('services.central_sso.url'), '/');
        $configuredWorkspace = (string) config('services.central_sso.workspace');
        $clientId = (string) config('services.central_sso.client_id');
        $clientSecret = (string) config('services.central_sso.client_secret');
        $code = (string) $request->query('code', '');
        $requestedWorkspace = (string) $request->query('workspace', '');

        if (
            ! $this->validBrokerUrl($baseUrl)
            || $configuredWorkspace === ''
            || $clientId === ''
            || $clientSecret === ''
        ) {
            return $this->reject('El acceso central no está configurado en este sistema.');
        }

        if (
            strlen($code) !== 80
            || $requestedWorkspace === ''
            || ! hash_equals($configuredWorkspace, $requestedWorkspace)
        ) {
            return $this->reject('La solicitud de acceso central no es válida.');
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(10)
                ->post($baseUrl.'/api/sso/exchange', ['code' => $code]);
        } catch (ConnectionException) {
            return $this->reject('No fue posible conectar con el acceso central. Intenta nuevamente.');
        }

        if (! $response->ok()) {
            return $this->reject('El código de acceso expiró o ya fue utilizado. Inicia sesión nuevamente.');
        }

        $identity = $response->json('user');

        if (! is_array($identity) || ! $this->validIdentity($identity, $configuredWorkspace)) {
            return $this->reject('La identidad recibida desde el acceso central no es válida.');
        }

        $subject = (string) $identity['sub'];
        $email = Str::lower(trim((string) $identity['email']));

        $user = DB::transaction(function () use ($identity, $subject, $email): ?User {
            $userBySubject = User::query()
                ->where('sso_subject', $subject)
                ->lockForUpdate()
                ->first();
            $userByEmail = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if ($userBySubject && $userByEmail && ! $userBySubject->is($userByEmail)) {
                return null;
            }

            $user = $userBySubject ?? $userByEmail;

            if (! $user || ($user->sso_subject && ! hash_equals($user->sso_subject, $subject))) {
                return null;
            }

            $updates = [
                'sso_subject' => $subject,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ];

            $avatarUrl = (string) ($identity['avatar_url'] ?? '');

            if (! $user->profile_photo && $this->validAvatarUrl($avatarUrl)) {
                $updates['profile_photo'] = $avatarUrl;
            }

            $user->forceFill($updates)->save();

            return $user;
        });

        if (! $user || ! $user->is_active || ! $user->hasSystemAccess()) {
            return $this->reject('Tu usuario no existe o todavía no tiene acceso habilitado en este sistema.');
        }

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        return redirect()->route($this->defaultRoute($user));
    }

    /**
     * @param  array<string, mixed>  $identity
     */
    private function validIdentity(array $identity, string $workspace): bool
    {
        $subject = (string) ($identity['sub'] ?? '');
        $email = Str::lower(trim((string) ($identity['email'] ?? '')));

        return $subject !== ''
            && strlen($subject) <= 255
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && ($identity['email_verified'] ?? false) === true
            && hash_equals($workspace, (string) ($identity['workspace'] ?? ''));
    }

    private function defaultRoute(User $user): string
    {
        $isTenant = $user->hasRole('inquilino') || $user->hasRole('tenant');
        $isTechnician = $user->hasRole('tecnico') || $user->hasRole('technician');
        $isAdmin = $user->hasRole('administrador') || $user->hasRole('admin');
        $isAdvisor = ! $isAdmin && ($user->hasRole('asesores') || $user->hasRole('asesor') || $user->can('propiedades.ver_propias'));

        return match (true) {
            $isTenant || $isTechnician => 'maintenance.index',
            $isAdvisor => 'advisor.tasks.index',
            default => 'dashboard',
        };
    }

    private function validBrokerUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && filled(parse_url($url, PHP_URL_HOST));
    }

    private function validAvatarUrl(string $url): bool
    {
        return $url !== ''
            && strlen($url) <= 255
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_SCHEME) === 'https';
    }

    private function reject(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['sso' => $message]);
    }
}
