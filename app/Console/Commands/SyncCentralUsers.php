<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CentralIdentitySyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncCentralUsers extends Command
{
    protected $signature = 'sso:sync-users {--all : Vuelve a sincronizar también las identidades al día}';

    protected $description = 'Sincroniza usuarios locales y su acceso con el portal central de Naboo';

    public function handle(CentralIdentitySyncService $centralIdentity): int
    {
        if (! $centralIdentity->configured()) {
            $this->warn('La sincronización central no está configurada para esta instancia.');

            return self::SUCCESS;
        }

        $query = User::query()->orderBy('id');
        if (! $this->option('all')) {
            $query->where(function ($query): void {
                $query->where('sso_sync_pending', true)->orWhereNull('sso_subject');
            });
        }

        $synchronized = 0;
        $failed = 0;

        $query->chunkById(100, function ($users) use ($centralIdentity, &$synchronized, &$failed): void {
            foreach ($users as $user) {
                try {
                    if ($centralIdentity->sync($user)) {
                        $synchronized++;
                    }
                } catch (Throwable $exception) {
                    $centralIdentity->markFailed($user, $exception);
                    $failed++;
                    $this->error("No se pudo sincronizar {$user->email}: {$exception->getMessage()}");
                }
            }
        });

        $this->info("Usuarios sincronizados: {$synchronized}. Errores: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
