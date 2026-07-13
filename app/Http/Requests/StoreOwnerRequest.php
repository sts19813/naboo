<?php

namespace App\Http\Requests;

use App\Models\Owner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOwnerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'curp' => ['nullable', 'string', 'max:20'],
            'owner_type' => ['required', Rule::in(array_keys(Owner::OWNER_TYPE_LABELS))],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'clabe' => ['nullable', 'digits:18'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', Rule::in(array_keys(Owner::PAYMENT_METHOD_LABELS))],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre completo',
            'phone' => 'telefono',
            'owner_type' => 'tipo de titular',
            'clabe' => 'CLABE',
        ];
    }
}

