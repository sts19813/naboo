<?php

namespace App\Observers;

use App\Models\User;
use App\Services\CentralIdentitySyncService;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserObserver
{
    public function __construct(private readonly CentralIdentitySyncService $centralIdentity) {}

    public function created(User $user): void
    {
        $this->synchronize($user);
    }

    public function updated(User $user): void
    {
        if (! $user->wasChanged(['name', 'email', 'password', 'is_active'])) {
            return;
        }

        $this->centralIdentity->markPending($user);
        $this->synchronize($user);
    }

    private function synchronize(User $user): void
    {
        if (! $this->centralIdentity->configured()) {
            return;
        }

        try {
            $this->centralIdentity->sync($user);
        } catch (Throwable $exception) {
            $this->centralIdentity->markFailed($user, $exception);

            Log::error('No fue posible sincronizar el usuario con el acceso central.', [
                'user_id' => $user->getKey(),
                'workspace' => config('services.central_sso.workspace'),
                'exception' => $exception,
            ]);
        }
    }
}
