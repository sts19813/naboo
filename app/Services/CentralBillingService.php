<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\Property;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CentralBillingService
{
    public function snapshot(): array
    {
        $propertyCount = Property::query()->count();
        $rentedPropertyCount = Property::query()
            ->whereNotNull('tenant_id')
            ->whereHas('charges', function ($query): void {
                $query->whereIn('status', [
                    Charge::STATUS_PENDING,
                    Charge::STATUS_PARTIAL,
                    Charge::STATUS_IN_VALIDATION,
                ]);
            })
            ->count();

        return [
            'property_count' => $propertyCount,
            'rented_property_count' => $rentedPropertyCount,
            'vacant_property_count' => max(0, $propertyCount - $rentedPropertyCount),
            'measured_at' => now()->utc()->toIso8601String(),
        ];
    }

    public function report(): array
    {
        $this->ensureConfigured();
        $snapshot = $this->snapshot();
        $response = $this->request()->post($this->centralUrl('/api/billing/usage'), [
            'property_count' => $snapshot['property_count'],
            'rented_property_count' => $snapshot['rented_property_count'],
            'measured_at' => $snapshot['measured_at'],
        ]);

        $response->throw();

        return $response->json();
    }

    public function billingUrl(): string
    {
        $this->ensureConfigured();

        return $this->centralUrl('/espacios/'.rawurlencode((string) config('services.central_sso.workspace')).'/suscripcion');
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withBasicAuth(
                (string) config('services.central_sso.client_id'),
                (string) config('services.central_sso.client_secret'),
            )
            ->timeout(10)
            ->retry(2, 250);
    }

    private function centralUrl(string $path): string
    {
        return rtrim((string) config('services.central_sso.url'), '/').$path;
    }

    private function ensureConfigured(): void
    {
        if (
            blank(config('services.central_sso.url'))
            || blank(config('services.central_sso.workspace'))
            || blank(config('services.central_sso.client_id'))
            || blank(config('services.central_sso.client_secret'))
        ) {
            throw new RuntimeException('La facturación central no está configurada en esta instancia.');
        }
    }
}
