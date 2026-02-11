<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'balance', 'total_earned'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->orderBy('created_at', 'desc');
    }

    /**
     * Helper para agregar saldo (ej: compra)
     */
    public function deposit(int $amount, string $type, ?string $description = null): Transaction
    {
        $this->increment('balance', $amount);
        if ($type !== 'deposit') { // Solo sumamos a 'ganado' si no es un depósito directo manual
            // Depende de la lógica negocio, por ahora simple
        }

        return $this->transactions()->create([
            'type' => $type,
            'amount' => $amount, // positivo
            'description' => $description,
        ]);
    }

    /**
     * Helper para gastar saldo (ej: mensaje)
     */
    public function withdraw(int $amount, string $type, ?string $description = null): Transaction
    {
        if ($this->balance < $amount) {
            throw new \Exception("Saldo insuficiente");
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'type' => $type,
            'amount' => -$amount, // negativo
            'description' => $description,
        ]);
    }
}
