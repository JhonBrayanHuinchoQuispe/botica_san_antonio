<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIChatHistory extends Model
{
    protected $table = 'ai_chat_history';

    protected $fillable = [
        'user_id',
        'message',
        'response',
        'sql_query',
        'success',
    ];

    protected $casts = [
        'success' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
