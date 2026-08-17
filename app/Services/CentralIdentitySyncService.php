<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CentralIdentitySyncService
{
    public function configured(): bool
    {
        $url = rtrim((string) config('services.central_sso.url'), '/');

        return (bool) config('services.central_sso.sync_users', true)
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && filled(config('services.central_sso.workspace'))
            && filled(config('services.central_sso.client_id'))
            && filled(config('services.central_sso.client_secret'));
    }

    /**
     * @throws ConnectionException
     * @throws RuntimeException
     */
    public function sync(User $user): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $passwordHash = (string) ($user->getAttributes()['password'] ?? '');
        if ($passwordHash === '' || ($this->passwordAlgorithm($passwordHash)) === 'unknown') {
            throw new RuntimeException('El usuario local no tiene un hash de contraseña compatible.');
        }

        $baseUrl = rtrim((string) config('services.central_sso.url'), '/');
        $response = Http::asJson()
            ->acceptJson()
            ->withBasicAuth(
                (string) config('services.central_sso.client_id'),
                (string) config('services.central_sso.client_secret'),
            )
            ->timeout(10)
            ->retry([200, 500], throw: false)
            ->post($baseUrl.'/api/sso/provision', [
                'subject' => filled($user->sso_subject) ? (string) $user->sso_subject : null,
                'name' => trim((string) $user->name),
                'email' => Str::lower(trim((string) $user->email)),
                'password_hash' => $passwordHash,
                'is_active' => (bool) $user->is_active,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('El portal central rechazó la sincronización (HTTP '.$response->status().').');
        }

        $subject = (string) $response->json('user.sub', '');
        $workspace = (string) $response->json('user.workspace', '');

        if (
            $subject === ''
            || $workspace === ''
            || ! hash_equals((string) config('services.central_sso.workspace'), $workspace)
            || (filled($user->sso_subject) && ! hash_equals((string) $user->sso_subject, $subject))
        ) {
            throw new RuntimeException('El portal central devolvió una identidad incompatible.');
        }

        $user->forceFill([
            'sso_subject' => $subject,
            'sso_synced_at' => now(),
            'sso_sync_pending' => false,
            'sso_sync_error' => null,
        ])->saveQuietly();

        return true;
    }

    public function markPending(User $user): void
    {
        $user->forceFill([
            'sso_sync_pending' => true,
            'sso_sync_error' => null,
        ])->saveQuietly();
    }

    public function markFailed(User $user, Throwable $exception): void
    {
        $user->forceFill([
            'sso_sync_pending' => true,
            'sso_sync_error' => Str::limit($exception->getMessage(), 1000, ''),
        ])->saveQuietly();
    }

    private function passwordAlgorithm(string $hash): string
    {
        return (string) (password_get_info($hash)['algoName'] ?? 'unknown');
    }
}
