<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendChargeReminderRequest extends FormRequest
{
    protected $errorBag = 'chargeReminder';

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
            'channel' => ['required', Rule::in(['email', 'whatsapp'])],
            'days_before' => ['required', 'integer', 'min:0', 'max:30'],
            'message' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
