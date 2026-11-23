<?php

namespace App\Console\Commands;

use App\Models\IntegrationClient;
use Illuminate\Console\Command;

class IssueAirbnbIntegrationTokenCommand extends Command
{
    protected $signature = 'integrations:issue-airbnb-token {--name=Airbnb SU} {--key=airbnbsu} {--rotate}';

    protected $description = 'Emite un token Sanctum para la integración interna entre Naboo y Airbnb SU.';

    public function handle(): int
    {
        $client = IntegrationClient::query()->updateOrCreate([
            'key' => (string) $this->option('key'),
        ], [
            'name' => (string) $this->option('name'),
            'is_active' => true,
        ]);

        if ($this->option('rotate')) {
            $client->tokens()->where('name', 'airbnb-su')->delete();
        }

        $token = $client->createToken('airbnb-su', ['airbnb'])->plainTextToken;

        $this->line('Guarda este token como secreto en el cliente Airbnb SU:');
        $this->newLine();
        $this->line($token);
        $this->newLine();
        $this->warn('No se volverá a mostrar completo. Si se pierde, vuelve a correr con --rotate.');

        return self::SUCCESS;
    }
}
