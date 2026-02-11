<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type', // deposit, withdrawal, purchase, message, tip
        'amount',
        'description',
        'related_user_id',
        'reference_id'
    ];

    /**
     * Scope para ver solo ingresos
     */
    public function scopeIncome($query)
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * Scope para ver solo gastos
     */
    public function scopeExpense($query)
    {
        return $query->where('amount', '<', 0);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * El usuario con el que interactuaste (ej: modelo que recibió el tip)
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }
}
