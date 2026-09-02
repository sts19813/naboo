<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashCutItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_cut_id',
        'charge_payment_id',
        'charge_uuid',
        'charge_concept',
        'property_name',
        'tenant_name',
        'amount',
        'currency',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function cut(): BelongsTo
    {
        return $this->belongsTo(CashCut::class, 'cash_cut_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ChargePayment::class, 'charge_payment_id');
    }
}
