<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MaintenanceTicketFile extends Model
{
    use HasFactory;

    public const KIND_LABELS = [
        'reporte' => 'Reporte',
        'factura' => 'Factura',
        'evidencia' => 'Evidencia',
        'trabajo_finalizado' => 'Trabajo finalizado',
        'firma' => 'Firma',
        'documento' => 'Documento',
        'video' => 'Video',
    ];

    protected $fillable = [
        'ticket_id',
        'uploaded_by_user_id',
        'kind',
        'path',
        'original_name',
        'mime_type',
        'size',
        'is_compressed',
    ];

    protected function casts(): array
    {
        return [
            'is_compressed' => 'boolean',
            'size' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (blank($this->path)) {
            return null;
        }

        if (Str::startsWith((string) $this->path, ['http://', 'https://', '//'])) {
            return (string) $this->path;
        }

        return '/storage/' . ltrim((string) $this->path, '/');
    }
}
