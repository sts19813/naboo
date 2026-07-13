<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
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
        $tenantId = $this->route('tenant')?->id;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone_primary' => ['required', 'string', 'max:40'],
            'phone_secondary' => ['nullable', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tenants', 'email')->ignore($tenantId)],
            'rfc' => ['nullable', 'string', 'max:20'],
            'curp' => ['nullable', 'string', 'max:20'],
            'employer' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'employment_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'personal_reference_name' => ['nullable', 'string', 'max:255'],
            'personal_reference_phone' => ['nullable', 'string', 'max:40'],
            'work_reference_name' => ['nullable', 'string', 'max:255'],
            'work_reference_phone' => ['nullable', 'string', 'max:40'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'previous_address' => ['nullable', 'string', 'max:2000'],
            'current_address' => ['nullable', 'string', 'max:2000'],
            'dossier_status' => ['required', Rule::in(array_keys(Tenant::DOSSIER_STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'access_password' => ['nullable', 'string', 'min:8', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'nombre completo',
            'phone_primary' => 'telefono principal',
            'monthly_income' => 'ingreso mensual',
            'dossier_status' => 'estado del expediente',
            'access_password' => 'contrasena de acceso',
        ];
    }
}
