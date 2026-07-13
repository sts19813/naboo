<?php

namespace App\Http\Requests;

use App\Models\Charge;
use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreChargeRequest extends FormRequest
{
    protected $errorBag = 'createCharge';

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
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'type' => ['required', 'string', Rule::in(array_keys(Charge::TYPE_LABELS))],
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'between:2000,2200'],
            'concept' => ['required', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $propertyId = $this->input('property_id');
            $tenantId = $this->input('tenant_id');
            if (!filled($propertyId) || !filled($tenantId)) {
                return;
            }

            $property = Property::query()->find((int) $propertyId);
            if (!$property) {
                return;
            }

            if (!$property->tenant_id) {
                $validator->errors()->add('property_id', 'La propiedad seleccionada no tiene inquilino asignado.');
                return;
            }

            if ((int) $property->tenant_id !== (int) $tenantId) {
                $validator->errors()->add(
                    'tenant_id',
                    'El inquilino debe coincidir con el inquilino activo de la propiedad seleccionada.',
                );
            }
        });
    }
}
