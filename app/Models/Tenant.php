<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    public const DOSSIER_COMPLETE = 'complete';
    public const DOSSIER_IN_REVIEW = 'in_review';
    public const DOSSIER_INCOMPLETE = 'incomplete';
    public const DOSSIER_REJECTED = 'rejected';

    public const DOSSIER_STATUS_LABELS = [
        self::DOSSIER_COMPLETE => 'Completo',
        self::DOSSIER_IN_REVIEW => 'En revision',
        self::DOSSIER_INCOMPLETE => 'Incompleto',
        self::DOSSIER_REJECTED => 'Rechazado',
    ];

    public const DOSSIER_STATUS_BADGE_CLASSES = [
        self::DOSSIER_COMPLETE => 'badge-light-success text-success',
        self::DOSSIER_IN_REVIEW => 'badge-light-primary text-primary',
        self::DOSSIER_INCOMPLETE => 'badge-light-warning text-warning',
        self::DOSSIER_REJECTED => 'badge-light-danger text-danger',
    ];

    protected $fillable = [
        'uuid',
        'full_name',
        'phone_primary',
        'phone_secondary',
        'email',
        'rfc',
        'curp',
        'employer',
        'occupation',
        'monthly_income',
        'employment_years',
        'personal_reference_name',
        'personal_reference_phone',
        'work_reference_name',
        'work_reference_phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'previous_address',
        'current_address',
        'dossier_status',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_income' => 'decimal:2',
            'employment_years' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $tenant) {
            if (blank($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
        });
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TenantDocument::class);
    }

    public function pendingDocuments(): HasMany
    {
        return $this->documents()->where('status', TenantDocument::STATUS_PENDING);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function getDossierStatusLabelAttribute(): string
    {
        return self::DOSSIER_STATUS_LABELS[$this->dossier_status] ?? ucfirst(str_replace('_', ' ', $this->dossier_status));
    }

    public function getDossierStatusBadgeClassAttribute(): string
    {
        return self::DOSSIER_STATUS_BADGE_CLASSES[$this->dossier_status] ?? 'badge-light-secondary text-secondary';
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
