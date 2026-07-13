<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveRecurringExpenseItemRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseGlobalSetupRequest;
use App\Http\Requests\UpdateExpensePropertySetupRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseFile;
use App\Models\ExpenseNotificationSetting;
use App\Models\Property;
use App\Models\RecurringExpenseItem;
use App\Models\RecurringExpenseItemFile;
use App\Services\RecurringExpenseGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'property' => ['nullable', 'string', 'exists:properties,uuid'],
        ]);

        $selectedPropertyUuid = trim((string) ($filters['property'] ?? ''));

        $selectedProperty = $selectedPropertyUuid !== ''
            ? Property::query()->where('uuid', $selectedPropertyUuid)->first()
            : null;

        $expensesQuery = Expense::query()
            ->with([
                'property:id,uuid,internal_name,internal_reference',
                'files:id,expense_id,path,type,mime_type,original_name',
            ])
            ->withCount('files')
            ->when($selectedProperty, fn (Builder $query) => $query->where('property_id', $selectedProperty->id));

        $expenses = $expensesQuery
            ->upcomingFirst()
            ->get();

        $summaryBaseQuery = Expense::query()
            ->includedInTotals()
            ->when($selectedProperty, fn (Builder $query) => $query->where('property_id', $selectedProperty->id));

        $summary = [
            'pending_total' => (float) (clone $summaryBaseQuery)->pending()->sum('amount'),
            'paid_total' => (float) (clone $summaryBaseQuery)->paid()->sum('amount'),
            'overdue_total' => (float) (clone $summaryBaseQuery)->overdue()->sum('amount'),
        ];

        $globalSetup = ExpenseNotificationSetting::current();

        return view('expenses.index', [
            'expenses' => $expenses,
            'properties' => Property::query()->orderBy('internal_name')->get(['id', 'uuid', 'internal_name', 'internal_reference']),
            'selectedProperty' => $selectedProperty,
            'summary' => $summary,
            'globalSetup' => $globalSetup,
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $expense = DB::transaction(function () use ($request, $validated): Expense {
            $expense = Expense::create([
                'property_id' => (int) $validated['property_id'],
                'concept' => trim((string) $validated['concept']),
                'amount' => (float) $validated['amount'],
                'due_date' => $validated['due_date'],
                'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
                'created_by' => $request->user()?->id,
            ]);

            $this->storeExpenseFiles($expense, (array) $request->file('files', []));

            return $expense;
        });

        return $this->redirectAfterMutation($request, 'Gasto registrado correctamente.');
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validated();
        $newDueDate = (string) $validated['due_date'];
        $dueDateChanged = $expense->due_date?->toDateString() !== $newDueDate;

        DB::transaction(function () use ($request, $validated, $expense, $dueDateChanged): void {
            $expense->update([
                'concept' => trim((string) $validated['concept']),
                'amount' => (float) $validated['amount'],
                'due_date' => $validated['due_date'],
                'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
                'upcoming_notified_at' => $dueDateChanged ? null : $expense->upcoming_notified_at,
                'overdue_notified_at' => $dueDateChanged ? null : $expense->overdue_notified_at,
            ]);

            $removeFileIds = collect((array) ($validated['remove_file_ids'] ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            if ($removeFileIds->isNotEmpty()) {
                $filesToDelete = $expense->files()->whereIn('id', $removeFileIds->all())->get();
                foreach ($filesToDelete as $file) {
                    $this->deleteStoragePath($file->path);
                }
                $expense->files()->whereIn('id', $filesToDelete->pluck('id')->all())->delete();
            }

            $this->storeExpenseFiles($expense, (array) $request->file('files', []));
        });

        return $this->redirectAfterMutation($request, 'Gasto actualizado correctamente.');
    }

    public function markAsPaid(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->paid_at === null) {
            $expense->update([
                'paid_at' => now(),
            ]);
        }

        return $this->redirectAfterMutation($request, 'Gasto marcado como pagado.');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        DB::transaction(function () use ($expense): void {
            $files = $expense->files()->get();
            foreach ($files as $file) {
                $this->deleteStoragePath($file->path);
            }

            $expense->delete();
        });

        return $this->redirectAfterMutation($request, 'Gasto eliminado correctamente.');
    }

    public function updateGlobalSetup(UpdateExpenseGlobalSetupRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $setup = ExpenseNotificationSetting::current();

        $setup->update([
            'days_before' => max(0, (int) ($validated['days_before'] ?? 0)),
            'emails' => $this->parseContactList($validated['emails'] ?? []),
            'phones' => $this->parseContactList($validated['phones'] ?? []),
        ]);

        return redirect()->back()->with('success', 'Configuración global de gastos actualizada.');
    }

    public function updatePropertySetup(UpdateExpensePropertySetupRequest $request, Property $property): RedirectResponse
    {
        $validated = $request->validated();
        $useGlobalSetup = (bool) ($validated['use_global_setup'] ?? true);

        if (! $useGlobalSetup && ! filled($validated['days_before'] ?? null)) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['days_before' => 'Debes definir los días de aviso para la configuración personalizada.'], 'expensePropertySetup');
        }

        $property->forceFill([
            'use_global_expense_notifications' => $useGlobalSetup,
            'expense_notification_days_before' => $useGlobalSetup
                ? null
                : max(0, (int) ($validated['days_before'] ?? 0)),
            'expense_notification_emails' => $useGlobalSetup
                ? null
                : $this->parseContactList($validated['emails'] ?? []),
            'expense_notification_phones' => $useGlobalSetup
                ? null
                : $this->parseContactList($validated['phones'] ?? []),
        ])->save();

        return redirect()
            ->to(route('properties.show', $property).'#tab-expenses')
            ->with('success', 'Configuración de notificaciones de gastos actualizada.');
    }

    public function storeRecurringItem(
        SaveRecurringExpenseItemRequest $request,
        Property $property,
        RecurringExpenseGenerator $generator,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $property, $generator): void {
            $item = $property->recurringExpenseItems()->create([
                'concept' => trim((string) $validated['concept']),
                'amount' => (float) $validated['amount'],
                'frequency' => $validated['frequency'],
                'starts_on' => $validated['starts_on'],
                'occurrences_count' => (int) $validated['occurrences_count'],
                'description' => filled($validated['description'] ?? null)
                    ? trim((string) $validated['description'])
                    : null,
                'is_active' => true,
                'created_by' => $request->user()?->id,
            ]);

            $this->storeRecurringExpenseItemFiles($item, (array) $request->file('files', []));

            $generator->generateForItem($item->fresh('files'));
        });

        return redirect()
            ->to(route('properties.show', $property).'#tab-expenses')
            ->with('success', 'Gasto recurrente configurado correctamente.');
    }

    public function updateRecurringItem(
        SaveRecurringExpenseItemRequest $request,
        RecurringExpenseItem $recurringExpenseItem,
        RecurringExpenseGenerator $generator,
    ): RedirectResponse {
        $validated = $request->validated();

        $recurringExpenseItem->update([
            'concept' => trim((string) $validated['concept']),
            'amount' => (float) $validated['amount'],
            'frequency' => $validated['frequency'],
            'starts_on' => $validated['starts_on'],
            'occurrences_count' => (int) $validated['occurrences_count'],
            'description' => filled($validated['description'] ?? null)
                ? trim((string) $validated['description'])
                : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $generator->generateForItem($recurringExpenseItem->fresh());

        return redirect()
            ->to(route('properties.show', $recurringExpenseItem->property).'#tab-expenses')
            ->with('success', 'Gasto recurrente actualizado correctamente.');
    }

    public function destroyRecurringItem(RecurringExpenseItem $recurringExpenseItem): RedirectResponse
    {
        $property = $recurringExpenseItem->property;

        DB::transaction(function () use ($recurringExpenseItem): void {
            foreach ($recurringExpenseItem->files as $file) {
                $this->deleteStoragePath($file->path);
            }

            $recurringExpenseItem->delete();
        });

        return redirect()
            ->to(route('properties.show', $property).'#tab-expenses')
            ->with('success', 'Gasto recurrente eliminado. Los gastos ya generados se conservaron.');
    }

    /**
     * @param  array<int, UploadedFile|null>  $files
     */
    private function storeExpenseFiles(Expense $expense, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $mimeType = (string) ($file->getClientMimeType() ?: 'application/octet-stream');
            $type = str_starts_with($mimeType, 'image/')
                ? ExpenseFile::TYPE_IMAGE
                : ExpenseFile::TYPE_PDF;
            $path = $file->store("expenses/{$expense->id}", 'public');

            $expense->files()->create([
                'path' => $path,
                'type' => $type,
                'mime_type' => $mimeType,
                'original_name' => $file->getClientOriginalName(),
                'size' => (int) ($file->getSize() ?: 0),
            ]);
        }
    }

    /**
     * @param  array<int, UploadedFile|null>  $files
     */
    private function storeRecurringExpenseItemFiles(RecurringExpenseItem $item, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $mimeType = (string) ($file->getClientMimeType() ?: 'application/octet-stream');
            $type = str_starts_with($mimeType, 'image/')
                ? RecurringExpenseItemFile::TYPE_IMAGE
                : RecurringExpenseItemFile::TYPE_PDF;
            $path = $file->store("recurring-expense-items/{$item->id}", 'public');

            $item->files()->create([
                'path' => $path,
                'type' => $type,
                'mime_type' => $mimeType,
                'original_name' => $file->getClientOriginalName(),
                'size' => (int) ($file->getSize() ?: 0),
            ]);
        }
    }

    private function deleteStoragePath(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    /**
     * @return array<int, string>
     */
    private function parseContactList(mixed $input): array
    {
        if (is_array($input)) {
            $items = $input;
        } else {
            $items = preg_split('/[,;\n\r]+/', (string) $input) ?: [];
        }

        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function redirectAfterMutation(Request $request, string $successMessage): RedirectResponse
    {
        $propertyContext = trim((string) $request->input('property_context', ''));

        if ($propertyContext !== '' && Property::query()->where('uuid', $propertyContext)->exists()) {
            return redirect()
                ->to(route('properties.show', ['property' => $propertyContext]).'#tab-expenses')
                ->with('success', $successMessage);
        }

        return redirect()->back()->with('success', $successMessage);
    }
}
