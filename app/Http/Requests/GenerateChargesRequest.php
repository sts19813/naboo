<?php

namespace App\Http\Requests;

use App\Models\Charge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GenerateChargesRequest extends FormRequest
{
    protected $errorBag = 'generateCharges';

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
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'contract_starts_at' => ['nullable', 'date'],
            'contract_expires_at' => ['nullable', 'date'],
            'monthly_rent_price' => ['nullable', 'numeric', 'min:0'],
            'charge_day' => ['nullable', 'integer', 'between:1,31'],
            'charge_tolerance_days' => ['nullable', 'integer', 'min:0', 'max:31'],
            'rows' => ['nullable', 'array'],
            'rows.*.period_month' => ['required_with:rows', 'integer', 'between:1,12'],
            'rows.*.period_year' => ['required_with:rows', 'integer', 'between:2000,2200'],
            'rows.*.due_date' => ['required_with:rows', 'date'],
            'rows.*.amount' => ['required_with:rows', 'numeric', 'min:0.01'],
            'rows.*.concept' => ['required_with:rows', 'string', 'max:190'],
            'rows.*.type' => ['nullable', 'string', 'in:' . implode(',', array_keys(Charge::TYPE_LABELS))],
            'rows.*.notes' => ['nullable', 'string', 'max:4000'],
            'property_context' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $contractStartsAt = $this->input('contract_starts_at');
            $contractExpiresAt = $this->input('contract_expires_at');

            if (filled($contractStartsAt) xor filled($contractExpiresAt)) {
                $validator->errors()->add(
                    'contract_starts_at',
                    'Debes capturar fecha de inicio y fecha de vencimiento del contrato.',
                );
            }

            if (filled($contractStartsAt) && filled($contractExpiresAt) && $contractStartsAt > $contractExpiresAt) {
                $validator->errors()->add(
                    'contract_starts_at',
                    'La fecha de inicio del contrato debe ser anterior o igual al vencimiento.',
                );
            }
        });
    }
}
