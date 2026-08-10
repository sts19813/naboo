<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_conversation_id',
        'openai_call_id',
        'name',
        'arguments',
        'result',
        'status',
        'latency_ms',
    ];

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'latency_ms' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
