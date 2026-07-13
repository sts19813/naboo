<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyOwner extends Model
{
    use HasFactory;

    public const OWNER_INDIVIDUAL = 'individual';
    public const OWNER_COMPANY = 'company';

    public const OWNER_TYPE_LABELS = [
        self::OWNER_INDIVIDUAL => 'Persona física',
        self::OWNER_COMPANY => 'Persona moral',
    ];

    public const PAYMENT_METHOD_TRANSFER = 'transfer';
    public const PAYMENT_METHOD_CASH = 'cash';
    public const PAYMENT_METHOD_CHECK = 'check';

    public const PAYMENT_METHOD_LABELS = [
        self::PAYMENT_METHOD_TRANSFER => 'Transferencia',
        self::PAYMENT_METHOD_CASH => 'Efectivo',
        self::PAYMENT_METHOD_CHECK => 'Cheque',
    ];

    protected $fillable = [
        'property_id',
        'name',
        'phone',
        'email',
        'owner_type',
        'bank_name',
        'clabe',
        'account_holder',
        'payment_method',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getOwnerTypeLabelAttribute(): string
    {
        return self::OWNER_TYPE_LABELS[$this->owner_type] ?? $this->owner_type;
    }

    public function getPaymentMethodLabelAttribute(): ?string
    {
        if (!$this->payment_method) {
            return null;
        }

        return self::PAYMENT_METHOD_LABELS[$this->payment_method] ?? $this->payment_method;
    }
}

