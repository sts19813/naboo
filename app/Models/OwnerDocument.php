<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OwnerDocument extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_UPLOADED => 'En revisión',
        self::STATUS_APPROVED => 'Vigente',
        self::STATUS_REJECTED => 'Rechazado',
        self::STATUS_EXPIRED => 'Vencido',
    ];

    public const STATUS_BADGE_CLASSES = [
        self::STATUS_PENDING => 'badge-light-secondary text-secondary',
        self::STATUS_UPLOADED => 'badge-light-primary text-primary',
        self::STATUS_APPROVED => 'badge-light-success text-success',
        self::STATUS_REJECTED => 'badge-light-danger text-danger',
        self::STATUS_EXPIRED => 'badge-light-warning text-warning',
    ];

    protected $fillable = [
        'owner_id',
        'document_type',
        'label',
        'file_path',
        'status',
        'uploaded_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'expires_at' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(OwnerDocumentVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(OwnerDocumentVersion::class)->latestOfMany('version_number');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge-light-secondary text-secondary';
    }
}
