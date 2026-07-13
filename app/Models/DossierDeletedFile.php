<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierDeletedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'entity_name',
        'document_group',
        'document_id',
        'document_type',
        'document_label',
        'version_id',
        'version_number',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'file_deleted',
        'delete_reason',
        'deleted_by_user_id',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'file_deleted' => 'boolean',
            'deleted_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }
}

