<?php

namespace App\Services\Copilot;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiResponsesClient
{
    /**
     * @throws RequestException
     */
    public function create(array $payload): array
    {
        $apiKey = trim((string) config('services.openai.key'));

        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY no esta configurada en el archivo .env.');
        }

        return Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.openai.timeout', 45))
            ->post('https://api.openai.com/v1/responses', $this->withoutNulls($payload))
            ->throw()
            ->json();
    }

    public function model(): string
    {
        return (string) config('services.openai.model', 'gpt-4.1-mini');
    }

    private function withoutNulls(array $payload): array
    {
        return collect($payload)
            ->reject(fn ($value): bool => $value === null)
            ->all();
    }
}
