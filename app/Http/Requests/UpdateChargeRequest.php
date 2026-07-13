<?php

namespace App\Http\Requests;

use App\Models\Charge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChargeRequest extends FormRequest
{
    protected $errorBag = 'updateCharge';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'between:2000,2200'],
            'concept' => ['required', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'type' => ['required', 'string', Rule::in(array_keys(Charge::TYPE_LABELS))],
            'charge_uuid' => ['nullable', 'string'],
            'property_context' => ['nullable', 'string'],
        ];
    }
}
