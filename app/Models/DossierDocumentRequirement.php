<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DossierDocumentRequirement extends Model
{
    use HasFactory;

    public const ENTITY_PROPERTY = 'property';
    public const ENTITY_TENANT = 'tenant';
    public const ENTITY_OWNER = 'owner';

    public const ENTITY_LABELS = [
        self::ENTITY_PROPERTY => 'Propiedades',
        self::ENTITY_TENANT => 'Inquilinos',
        self::ENTITY_OWNER => 'Propietarios',
    ];

    protected $fillable = [
        'entity_type',
        'document_type',
        'label',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
