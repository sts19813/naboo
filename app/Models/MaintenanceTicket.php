<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MaintenanceTicket extends Model
{
    use HasFactory;

    public const CATEGORY_LABELS = [
        'sin_categoria' => 'Sin categoría',
        'plomeria' => 'Plomería',
        'electricidad' => 'Electricidad',
        'aire_acondicionado' => 'Aire acondicionado',
        'limpieza' => 'Limpieza',
        'seguridad' => 'Seguridad',
        'electrodomesticos' => 'Electrodomésticos',
        'estructural' => 'Estructural',
    ];

    public const PRIORITY_LABELS = [
        'sin_asignar' => 'Sin asignar',
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
        'urgente' => 'Urgente',
    ];

    public const STATUS_LABELS = [
        'pendiente' => 'Pendiente',
        'revisado' => 'Revisado',
        'asignado' => 'Asignado',
        'programado' => 'Programado',
        'en_proceso' => 'En proceso',
        'esperando_material' => 'Esperando piezas/material',
        'completado' => 'Completado',
        'cancelado' => 'Cancelado',
        'reabierto' => 'Reabierto',
    ];

    public const REPORTER_ROLE_LABELS = [
        'administrador' => 'Administrador',
        'propietario' => 'Propietario',
        'inquilino' => 'Inquilino',
        'tecnico' => 'Técnico',
    ];

    public const PAYER_LABELS = [
        'propietario' => 'Propietario',
        'inquilino' => 'Inquilino',
        'administracion' => 'Administración',
    ];

    public const COST_PAYER_LABELS = [
        'inquilino' => 'Inquilino',
        'administracion' => 'Administración',
    ];

    public const PAYMENT_RULE_LABELS = [
        'mal_uso' => 'Daños por mal uso',
        'preventivo' => 'Mantenimiento preventivo',
        'garantia' => 'Garantía',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'uuid',
        'property_id',
        'reported_by_user_id',
        'current_provider_id',
        'reported_by_role',
        'reported_by_name',
        'category',
        'priority',
        'status',
        'title',
        'reference',
        'exact_location',
        'description',
        'additional_notes',
        'reported_at',
        'scheduled_visit_at',
        'payer',
        'payment_rule',
        'payment_rule_notes',
        'assigned_at',
        'started_at',
        'completed_at',
        'canceled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'scheduled_visit_at' => 'datetime',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            if (blank($ticket->uuid)) {
                $ticket->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function currentProvider(): BelongsTo
    {
        return $this->belongsTo(MaintenanceProvider::class, 'current_provider_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MaintenanceTicketAssignment::class, 'ticket_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(MaintenanceTicketFile::class, 'ticket_id');
    }

    public function costs(): HasMany
    {
        return $this->hasMany(MaintenanceTicketCost::class, 'ticket_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(MaintenanceTicketStatusHistory::class, 'ticket_id')->latest('changed_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MaintenanceTicketMessage::class, 'ticket_id')->latest();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(MaintenanceTicketNotification::class, 'ticket_id')->latest();
    }

    public function getDisplayReferenceAttribute(): string
    {
        $reference = trim((string) $this->reference);
        if ($reference !== '') {
            return $reference;
        }
        if ($this->id) {
            return str_pad((string) $this->id, 8, '0', STR_PAD_LEFT);
        }

        return Str::upper(Str::substr((string) $this->uuid, 0, 8));
    }
}
