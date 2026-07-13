<?php

namespace App\Http\Requests;

use App\Models\RecurringExpenseItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRecurringExpenseItemRequest extends FormRequest
{
    protected $errorBag = 'recurringExpenseItem';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $frequency = (string) $this->input('frequency');

        if ($frequency === RecurringExpenseItem::FREQUENCY_ONCE) {
            $this->merge(['occurrences_count' => 1]);

            return;
        }

        if (blank($this->input('occurrences_count'))) {
            $this->merge([
                'occurrences_count' => $frequency === RecurringExpenseItem::FREQUENCY_MONTHLY ? 12 : 1,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'concept' => ['required', 'string', 'max:190'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', Rule::in(array_keys(RecurringExpenseItem::FREQUENCY_LABELS))],
            'starts_on' => ['required', 'date'],
            'occurrences_count' => ['required', 'integer', 'min:1', 'max:120'],
            'description' => ['nullable', 'string', 'max:4000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
