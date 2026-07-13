<?php

namespace App\Console\Commands;

use App\Mail\ExpenseNotificationMail;
use App\Models\Expense;
use App\Models\ExpenseNotificationSetting;
use App\Models\User;
use App\Support\NotificationSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendExpenseNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expenses:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía notificaciones de gastos próximos a vencer y vencidos.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $globalSetup = ExpenseNotificationSetting::current();
        $sent = 0;
        $skipped = 0;

        Expense::query()
            ->with('property:id,uuid,internal_name,use_global_expense_notifications,expense_notification_days_before,expense_notification_emails,expense_notification_phones')
            ->whereNull('paid_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('upcoming_notified_at')
                    ->orWhereNull('overdue_notified_at');
            })
            ->orderBy('id')
            ->chunkById(100, function ($expenses) use ($globalSetup, &$sent, &$skipped): void {
                foreach ($expenses as $expense) {
                    $property = $expense->property;
                    if (!$property) {
                        $skipped++;

                        continue;
                    }

                    $config = $property->resolvedExpenseNotificationSetup($globalSetup);
                    $daysBefore = max(0, (int) ($config['days_before'] ?? 0));
                    $emails = collect($config['emails'] ?? [])->map(fn($email) => trim((string) $email))->filter()->unique()->values();
                    $phones = collect($config['phones'] ?? [])->map(fn($phone) => trim((string) $phone))->filter()->unique()->values();

                    $trigger = $this->resolveTrigger($expense, $daysBefore);
                    if ($trigger === null) {
                        continue;
                    }

                    if ($trigger === ExpenseNotificationMail::TRIGGER_UPCOMING && $expense->upcoming_notified_at !== null) {
                        continue;
                    }

                    if ($trigger === ExpenseNotificationMail::TRIGGER_OVERDUE && $expense->overdue_notified_at !== null) {
                        continue;
                    }

                    $hasHandledChannel = false;
                    $notificationEvent = $this->notificationEventForTrigger($trigger);

                    foreach ($emails as $email) {
                        if (!$this->canNotifyEmail($email, $notificationEvent)) {
                            continue;
                        }

                        Mail::to($email)->send(new ExpenseNotificationMail($expense, $trigger, $daysBefore));
                        $hasHandledChannel = true;
                        $sent++;
                    }

                    foreach ($phones as $phone) {
                        $this->line("Telefono configurado para gasto {$expense->uuid}: {$phone} (pendiente integración)");
                        $hasHandledChannel = true;
                    }

                    if (!$hasHandledChannel) {
                        $skipped++;

                        continue;
                    }

                    if ($trigger === ExpenseNotificationMail::TRIGGER_UPCOMING) {
                        $expense->update(['upcoming_notified_at' => now()]);
                    }

                    if ($trigger === ExpenseNotificationMail::TRIGGER_OVERDUE) {
                        $expense->update(['overdue_notified_at' => now()]);
                    }
                }
            });

        $this->info("Notificaciones de gastos enviadas: {$sent}. Omitidas: {$skipped}.");

        return self::SUCCESS;
    }

    private function resolveTrigger(Expense $expense, int $daysBefore): ?string
    {
        $today = now()->startOfDay();
        $dueDate = $expense->due_date?->copy()->startOfDay();
        if (!$dueDate) {
            return null;
        }

        if ($dueDate->lt($today)) {
            return ExpenseNotificationMail::TRIGGER_OVERDUE;
        }

        $limitDate = $today->copy()->addDays($daysBefore);
        if ($dueDate->between($today, $limitDate, true)) {
            return ExpenseNotificationMail::TRIGGER_UPCOMING;
        }

        return null;
    }

    private function notificationEventForTrigger(string $trigger): string
    {
        return $trigger === ExpenseNotificationMail::TRIGGER_OVERDUE
            ? NotificationSettings::EVENT_EXPENSE_OVERDUE
            : NotificationSettings::EVENT_EXPENSE_UPCOMING;
    }

    private function canNotifyEmail(string $email, string $event): bool
    {
        $user = User::query()->where('email', $email)->first();
        $role = NotificationSettings::roleForUser($user);

        return !$role || NotificationSettings::allows($role, $event);
    }
}
