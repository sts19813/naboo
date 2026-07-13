<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseFile;
use App\Models\RecurringExpenseItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecurringExpenseGenerator
{
    public function generateForItem(RecurringExpenseItem $item): int
    {
        if (! $item->is_active) {
            return 0;
        }

        $item->loadMissing('files');

        $created = 0;
        $occurrencesCount = $item->frequency === RecurringExpenseItem::FREQUENCY_ONCE
            ? 1
            : max(1, $item->occurrences_count);
        $desiredDates = collect(range(0, $occurrencesCount - 1))
            ->map(fn (int $index): Carbon => $this->occurrenceDate($item, $index));

        foreach ($desiredDates as $occurrence) {
            $expense = Expense::query()
                ->where('recurring_expense_item_id', $item->id)
                ->whereDate('recurrence_date', $occurrence->toDateString())
                ->first();

            if (! $expense) {
                $expense = Expense::create([
                    'recurring_expense_item_id' => $item->id,
                    'recurrence_date' => $occurrence,
                    'property_id' => $item->property_id,
                    'concept' => $item->concept,
                    'amount' => $item->amount,
                    'due_date' => $occurrence,
                    'description' => $item->description,
                    'created_by' => $item->created_by,
                ]);
                $this->copyTemplateFilesToExpense($item, $expense);
                $created++;
            } elseif (! $expense->is_paid) {
                $expense->update([
                    'property_id' => $item->property_id,
                    'concept' => $item->concept,
                    'amount' => $item->amount,
                    'due_date' => $occurrence,
                    'description' => $item->description,
                ]);
            }
        }

        $this->removeUnusedOccurrences($item, $desiredDates->map->toDateString()->all());

        return $created;
    }

    private function copyTemplateFilesToExpense(RecurringExpenseItem $item, Expense $expense): void
    {
        if ($item->files->isEmpty()) {
            return;
        }

        $disk = Storage::disk('public');

        foreach ($item->files as $templateFile) {
            if (! filled($templateFile->path) || ! $disk->exists($templateFile->path)) {
                continue;
            }

            $extension = pathinfo($templateFile->path, PATHINFO_EXTENSION);
            if ($extension === '' && filled($templateFile->original_name)) {
                $extension = pathinfo((string) $templateFile->original_name, PATHINFO_EXTENSION);
            }

            $targetPath = 'expenses/' . $expense->id . '/' . (string) Str::uuid() . ($extension !== '' ? ".{$extension}" : '');
            if (! $disk->copy($templateFile->path, $targetPath)) {
                continue;
            }

            $expense->files()->create([
                'path' => $targetPath,
                'type' => $templateFile->type === ExpenseFile::TYPE_IMAGE
                    ? ExpenseFile::TYPE_IMAGE
                    : ExpenseFile::TYPE_PDF,
                'mime_type' => $templateFile->mime_type,
                'original_name' => $templateFile->original_name,
                'size' => $disk->size($targetPath),
            ]);
        }
    }

    public function generateAll(): int
    {
        $created = 0;

        RecurringExpenseItem::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($items) use (&$created): void {
                foreach ($items as $item) {
                    $created += $this->generateForItem($item);
                }
            });

        return $created;
    }

    private function occurrenceDate(RecurringExpenseItem $item, int $index): Carbon
    {
        $startsOn = $item->starts_on->copy()->startOfDay();

        if ($item->frequency === RecurringExpenseItem::FREQUENCY_ANNUAL) {
            $period = Carbon::create($startsOn->year + $index, $startsOn->month, 1)->startOfDay();
        } else {
            $period = $startsOn->copy()->startOfMonth()->addMonths($index);
        }

        $lastAvailableDay = $period->month === 2 ? 28 : $period->daysInMonth;

        return $period->day(min($startsOn->day, $lastAvailableDay));
    }

    /**
     * @param  array<int, string>  $desiredDates
     */
    private function removeUnusedOccurrences(RecurringExpenseItem $item, array $desiredDates): void
    {
        $item->expenses()
            ->whereNull('paid_at')
            ->whereNotNull('recurrence_date')
            ->get()
            ->reject(fn (Expense $expense): bool => in_array($expense->recurrence_date?->toDateString(), $desiredDates, true))
            ->each(function (Expense $expense): void {
                foreach ($expense->files as $file) {
                    Storage::disk('public')->delete($file->path);
                }

                $expense->delete();
            });
    }
}
