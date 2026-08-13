<?php

namespace App\Console\Commands;

use App\Services\CentralBillingService;
use Illuminate\Console\Command;
use Throwable;

class ReportBillingUsage extends Command
{
    protected $signature = 'billing:report-usage';

    protected $description = 'Reporta al portal central las propiedades facturables de esta instancia';

    public function handle(CentralBillingService $billing): int
    {
        try {
            $response = $billing->report();
        } catch (Throwable $exception) {
            report($exception);
            $this->error('No fue posible reportar el consumo: '.$exception->getMessage());

            return self::FAILURE;
        }

        $usage = $response['usage'] ?? [];
        $amount = (int) ($response['billing']['calculated_amount'] ?? 0);
        $this->info(sprintf(
            'Reporte enviado: %d propiedades, %d rentadas, $%s MXN al mes.',
            (int) ($usage['properties'] ?? 0),
            (int) ($usage['rented_properties'] ?? 0),
            number_format($amount / 100, 2),
        ));

        return self::SUCCESS;
    }
}
