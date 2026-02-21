<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'is_admin_reply',
        'attachment_url',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_admin_reply' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    /**
     * El ticket al que pertenece este mensaje
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * El usuario que escribió el mensaje (puede ser el cliente/modelo o el admin)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
