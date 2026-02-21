<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'model_id',
        'amount',
        'type',
        'reference_id',
        'description',
    ];

    /**
     * El cliente que gastó las monedas
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * La modelo en la que se gastaron las monedas
     */
    public function modelUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'model_id');
    }

    /**
     * Scope: Solo desbloqueos de chat
     */
    public function scopeChatUnlocks($query)
    {
        return $query->where('type', 'chat_unlock');
    }

    /**
     * Scope: Solo desbloqueos de contenido
     */
    public function scopeContentUnlocks($query)
    {
        return $query->where('type', 'content_unlock');
    }
}
