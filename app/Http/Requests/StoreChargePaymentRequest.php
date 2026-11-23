<?php

namespace App\Http\Requests;

use App\Models\ChargePayment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(ChargePayment::METHOD_LABELS))],
            'reference' => ['nullable', 'string', 'max:190'],
            // receipt remains accepted for backwards compatibility with older clients.
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'receipts' => ['nullable', 'array', 'max:10'],
            'receipts.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'receipts.max' => 'Puedes adjuntar hasta 10 comprobantes por pago.',
            'receipt.mimes' => 'El comprobante debe ser una imagen JPG, PNG, WEBP o un archivo PDF.',
            'receipts.*.mimes' => 'Cada comprobante debe ser una imagen JPG, PNG, WEBP o un archivo PDF.',
            'receipt.max' => 'El comprobante no debe pesar más de 10 MB.',
            'receipts.*.max' => 'Cada comprobante no debe pesar más de 10 MB.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (count((array) $this->file('receipts', [])) + ($this->hasFile('receipt') ? 1 : 0) > 10) {
                $validator->errors()->add('receipts', 'Puedes adjuntar hasta 10 comprobantes por pago.');
            }
        }];
    }
}
