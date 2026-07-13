<?php

namespace App\Console\Commands;

use App\Services\RecurringExpenseGenerator;
use Illuminate\Console\Command;

class GenerateRecurringExpensesCommand extends Command
{
    protected $signature = 'expenses:generate-recurring';

    protected $description = 'Genera los gastos mensuales y anuales configurados por propiedad.';

    public function handle(RecurringExpenseGenerator $generator): int
    {
        $created = $generator->generateAll();

        $this->info("Gastos recurrentes generados: {$created}.");

        return self::SUCCESS;
    }
}
