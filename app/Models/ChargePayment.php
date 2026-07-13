<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargePayment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PENDING_VALIDATION = 'pending_validation';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_STRIPE = 'stripe';
    public const SOURCE_PUBLIC_TRANSFER = 'public_transfer';

    public const METHOD_SPEI = 'spei';
    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_BANK_DEPOSIT = 'bank_deposit';
    public const METHOD_OTHER = 'other';

    public const METHOD_LABELS = [
        self::METHOD_SPEI => 'SPEI',
        self::METHOD_CASH => 'Efectivo',
        self::METHOD_CARD => 'Tarjeta',
        self::METHOD_TRANSFER => 'Transferencia',
        self::METHOD_BANK_DEPOSIT => 'Deposito bancario',
        self::METHOD_OTHER => 'Otro',
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_PENDING_VALIDATION => 'En validacion',
        self::STATUS_SUCCEEDED => 'Validado',
        self::STATUS_FAILED => 'Rechazado',
        self::STATUS_REFUNDED => 'Reembolsado',
    ];

    protected $fillable = [
        'charge_id',
        'amount',
        'currency',
        'status',
        'source',
        'payment_method',
        'reference',
        'receipt_path',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_event_id',
        'paid_at',
        'payment_date',
        'registered_by',
        'validated_by',
        'validation_notes',
        'payload',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'payment_date' => 'date',
            'payload' => 'array',
        ];
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    public function getMethodLabelAttribute(): string
    {
        if (!filled($this->payment_method)) {
            return '-';
        }

        return self::METHOD_LABELS[$this->payment_method] ?? ucfirst(str_replace('_', ' ', $this->payment_method));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }
}
