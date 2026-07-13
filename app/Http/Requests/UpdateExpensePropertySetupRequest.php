<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpensePropertySetupRequest extends FormRequest
{
    protected $errorBag = 'expensePropertySetup';

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
            'use_global_setup' => ['required', 'boolean'],
            'days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
            'emails' => ['nullable'],
            'phones' => ['nullable'],
        ];
    }
}
