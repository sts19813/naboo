<?php

namespace App\Http\Requests;

use App\Models\ChargePayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChargePaymentRequest extends FormRequest
{
    protected $errorBag = 'registerPayment';

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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(ChargePayment::METHOD_LABELS))],
            'reference' => ['nullable', 'string', 'max:190'],
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
