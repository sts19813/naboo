<?php

namespace App\Http\Requests;

use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePropertyRequest extends FormRequest
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
        return [
            'internal_name' => ['required', 'string', 'max:150'],
            'internal_reference' => ['nullable', 'string', 'max:100'],
            'property_type_id' => ['required', 'exists:property_types,id'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'zone_text' => ['nullable', 'string', 'max:100'],
            'full_address' => ['required', 'string', 'max:255'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'complex_name' => ['nullable', 'string', 'max:255'],
            'official_number' => ['nullable', 'string', 'max:100'],
            'unit_number' => ['nullable', 'string', 'max:100'],
            'advisor_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'monthly_rent_price' => ['nullable', 'numeric', 'min:0'],
            'facade_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'details' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'rental_requirements' => ['nullable', 'string'],
            'amenities' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Property::STATUS_LABELS))],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'current_tenant_name' => ['nullable', 'string', 'max:255'],
            'contract_starts_at' => ['nullable', 'date'],
            'contract_expires_at' => ['nullable', 'date'],
            'rent_charge_plan' => ['nullable', 'array'],
            'rent_charge_plan.*.period_month' => ['required_with:rent_charge_plan', 'integer', 'between:1,12'],
            'rent_charge_plan.*.period_year' => ['required_with:rent_charge_plan', 'integer', 'between:2000,2200'],
            'rent_charge_plan.*.due_date' => ['required_with:rent_charge_plan', 'date'],
            'rent_charge_plan.*.amount' => ['required_with:rent_charge_plan', 'numeric', 'min:0.01'],
            'rent_charge_plan.*.concept' => ['nullable', 'string', 'max:190'],
            'rent_charge_plan.*.notes' => ['nullable', 'string', 'max:4000'],
            'rent_charge_plan.*.is_custom_amount' => ['nullable', 'boolean'],
            'owner_ids' => ['nullable', 'array'],
            'owner_ids.*' => ['integer', 'exists:owners,id'],
            'new_owners' => ['nullable', 'array'],
            'new_owners.*.name' => ['nullable', 'string', 'max:255'],
            'new_owners.*.phone' => ['nullable', 'string', 'max:40'],
            'new_owners.*.email' => ['nullable', 'email', 'max:255'],
            'new_owners.*.rfc' => ['nullable', 'string', 'max:20'],
            'new_owners.*.curp' => ['nullable', 'string', 'max:20'],
            'new_owners.*.owner_type' => ['nullable', Rule::in(array_keys(Owner::OWNER_TYPE_LABELS))],
            'new_owners.*.bank_name' => ['nullable', 'string', 'max:255'],
            'new_owners.*.clabe' => ['nullable', 'digits:18'],
            'new_owners.*.account_holder' => ['nullable', 'string', 'max:255'],
            'new_owners.*.payment_method' => ['nullable', Rule::in(array_keys(Owner::PAYMENT_METHOD_LABELS))],
            'new_owners.*.address' => ['nullable', 'string', 'max:2000'],
            'new_owners.*.notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'existing_custom_documents' => ['nullable', 'array'],
            'existing_custom_documents.*.label' => ['nullable', 'string', 'max:150'],
            'existing_custom_documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'existing_custom_documents.*.expires_at' => ['nullable', 'date'],
            'new_custom_documents' => ['nullable', 'array'],
            'new_custom_documents.*.label' => ['nullable', 'string', 'max:150'],
            'new_custom_documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'new_custom_documents.*.expires_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'internal_name' => 'nombre interno',
            'property_type_id' => 'tipo de propiedad',
            'zone_id' => 'zona',
            'full_address' => 'direccion completa',
            'tenant_id' => 'inquilino',
            'advisor_user_id' => 'asesor responsable',
            'owner_ids' => 'propietarios',
            'new_owners.*.name' => 'nombre del propietario',
            'new_owners.*.phone' => 'telefono del propietario',
            'new_owners.*.email' => 'correo del propietario',
            'new_owners.*.owner_type' => 'tipo de titular',
            'new_owners.*.clabe' => 'CLABE',
            'existing_custom_documents.*.label' => 'nombre del documento personalizado',
            'existing_custom_documents.*.file' => 'archivo del documento personalizado',
            'new_custom_documents.*.label' => 'nombre del nuevo documento',
            'new_custom_documents.*.file' => 'archivo del nuevo documento',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_type_id.required' => 'Debes seleccionar un tipo de propiedad.',
            'property_type_id.exists' => 'El tipo de propiedad seleccionado no es valido.',
            'zone_id.required' => 'Debes seleccionar una zona.',
            'zone_id.exists' => 'La zona seleccionada no es valida.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $advisorId = $this->input('advisor_user_id');
            if (filled($advisorId)) {
                $isAssignableAdvisor = User::query()
                    ->whereKey($advisorId)
                    ->where('is_active', true)
                    ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                        'asesores',
                        'asesor',
                        'advisor',
                        'administrador',
                        'admin',
                    ]))
                    ->exists();

                if (! $isAssignableAdvisor) {
                    $validator->errors()->add('advisor_user_id', 'Debes seleccionar un asesor o administrador activo.');
                }
            }

            $ownerIds = collect($this->input('owner_ids', []))
                ->filter(fn ($ownerId) => filled($ownerId));

            $newOwners = collect($this->input('new_owners', []))
                ->filter(fn ($owner) => is_array($owner));

            $validNewOwners = $newOwners->filter(
                fn ($owner) => filled($owner['name'] ?? null) && filled($owner['phone'] ?? null),
            );

            if ($ownerIds->isEmpty() && $validNewOwners->isEmpty()) {
                $validator->errors()->add('owner_ids', 'Debes seleccionar al menos un propietario o capturar uno nuevo.');
            }

            foreach ($newOwners as $index => $ownerData) {
                $hasAnyData = collect($ownerData)->contains(fn ($value) => filled($value));
                if (!$hasAnyData) {
                    continue;
                }

                if (blank($ownerData['name'] ?? null)) {
                    $validator->errors()->add("new_owners.$index.name", 'El nombre del nuevo propietario es obligatorio.');
                }

                if (blank($ownerData['phone'] ?? null)) {
                    $validator->errors()->add("new_owners.$index.phone", 'El telefono del nuevo propietario es obligatorio.');
                }
            }

            $contractStartsAt = $this->input('contract_starts_at');
            $contractExpiresAt = $this->input('contract_expires_at');
            if (filled($contractStartsAt) && filled($contractExpiresAt) && $contractStartsAt > $contractExpiresAt) {
                $validator->errors()->add('contract_starts_at', 'La fecha de inicio del contrato debe ser anterior o igual al vencimiento.');
            }

            $chargePlanRows = collect((array) $this->input('rent_charge_plan', []))
                ->filter(fn ($row) => is_array($row));
            $duplicatePeriods = $chargePlanRows
                ->map(fn ($row) => sprintf('%s-%s', (string) ($row['period_year'] ?? ''), (string) ($row['period_month'] ?? '')))
                ->filter(fn ($period) => $period !== '-')
                ->duplicates();
            if ($duplicatePeriods->isNotEmpty()) {
                $validator->errors()->add(
                    'rent_charge_plan',
                    'La lista de pagos tiene periodos repetidos. Verifica la tabla de cargos.',
                );
            }

            // Validate zone: either zone_id or zone_text must be provided
            $zoneId = $this->input('zone_id');
            $zoneText = $this->input('zone_text');
            if (blank($zoneId) && blank($zoneText)) {
                $validator->errors()->add('zone_text', 'Debes seleccionar una zona del listado o capturar una zona personalizada.');
            }

            if ($this->input('status') === Property::STATUS_OCCUPIED && blank($this->input('tenant_id'))) {
                $validator->errors()->add('tenant_id', 'Debes seleccionar un inquilino para marcar la propiedad como ocupada.');
            }

            foreach ((array) $this->input('new_custom_documents', []) as $index => $documentData) {
                if (!is_array($documentData)) {
                    continue;
                }

                $hasLabel = filled($documentData['label'] ?? null);
                $hasFile = $this->hasFile("new_custom_documents.$index.file");

                if ($hasLabel && !$hasFile) {
                    $validator->errors()->add(
                        "new_custom_documents.$index.file",
                        'Debes cargar el archivo del nuevo documento personalizado.',
                    );
                }

                if (!$hasLabel && $hasFile) {
                    $validator->errors()->add(
                        "new_custom_documents.$index.label",
                        'Debes capturar el nombre del nuevo documento personalizado.',
                    );
                }
            }

            // Validate facade photo: required for new properties, optional for updates if already exists
            $hasNewFacadePhoto = $this->hasFile('facade_photo');
            $propertyId = $this->route('property')?->id ?? null;
            $existingFacadePhoto = $propertyId ? Property::find($propertyId)?->facade_photo_path : null;

            if (!$hasNewFacadePhoto && !$existingFacadePhoto) {
                $validator->errors()->add('facade_photo', 'La foto de fachada es obligatoria.');
            }
        });
    }
}
