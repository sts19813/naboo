<?php

namespace App\Http\Requests;

use App\Models\MaintenanceTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceTicketRequest extends FormRequest
{
    protected $errorBag = 'createMaintenanceTicket';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->user()?->hasRole('inquilino') || $this->user()?->hasRole('tenant');
        if ($tenant) {
            return [
                'property_id' => ['required', 'integer', 'exists:properties,id'],
                'title' => ['required', 'string', 'max:190'],
                'description' => ['nullable', 'string', 'max:10000'],
                'files' => ['required', 'array', 'min:1', 'max:20'],
                'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,mp4,mov,avi,doc,docx,xls,xlsx,txt', 'max:51200'],
            ];
        }

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
            'scheduled_visit_at' => ['nullable', 'date', 'after_or_equal:reported_at'],
            'payer' => ['nullable', Rule::in(array_keys(MaintenanceTicket::COST_PAYER_LABELS))],
            'payment_rule' => ['nullable', Rule::in(array_keys(MaintenanceTicket::PAYMENT_RULE_LABELS))],
            'payment_rule_notes' => ['nullable', 'string', 'max:3000'],
            'provider_id' => ['nullable', 'integer', 'exists:maintenance_providers,id'],
            'force_conflict' => ['nullable', 'boolean'],
            'files' => ['nullable', 'array', 'max:20'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,mp4,mov,avi,doc,docx,xls,xlsx,txt', 'max:51200'],
            'status' => ['nullable', Rule::in(array_keys(MaintenanceTicket::STATUS_LABELS))],
        ];
    }
}
