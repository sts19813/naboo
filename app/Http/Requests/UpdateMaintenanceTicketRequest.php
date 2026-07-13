<?php

namespace App\Http\Requests;

use App\Models\MaintenanceTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceTicketRequest extends FormRequest
{
    protected $errorBag = 'updateMaintenanceTicket';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'category' => ['required', Rule::in(array_keys(MaintenanceTicket::CATEGORY_LABELS))],
            'priority' => ['required', Rule::in(array_keys(MaintenanceTicket::PRIORITY_LABELS))],
            'title' => ['required', 'string', 'max:190'],
            'reference' => ['nullable', 'string', 'max:190'],
            'exact_location' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'additional_notes' => ['nullable', 'string', 'max:10000'],
            'reported_at' => ['required', 'date'],
            'scheduled_visit_at' => ['nullable', 'date'],
            'payer' => ['nullable', Rule::in(array_keys(MaintenanceTicket::COST_PAYER_LABELS))],
            'payment_rule' => ['nullable', Rule::in(array_keys(MaintenanceTicket::PAYMENT_RULE_LABELS))],
            'payment_rule_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
