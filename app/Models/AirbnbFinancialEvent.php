<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirbnbFinancialEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'external_event_id',
        'event_type',
        'concept',
        'customer_name',
        'payment_method',
        'external_reservation_id',
        'amount',
        'currency',
        'occurred_on',
        'payload',
        'charge_id',
        'charge_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_on' => 'date',
            'payload' => 'array',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    public function chargePayment(): BelongsTo
    {
        return $this->belongsTo(ChargePayment::class);
    }
}
