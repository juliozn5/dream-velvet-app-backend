<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
        'bg_image',
        'is_online',
        'followers_count',
        'posts_count',
        'rating',
        'rate_message',
        'chat_price',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_online' => 'boolean',
            'rating' => 'float',
            'followers_count' => 'integer',
            'posts_count' => 'integer',
            'rate_message' => 'integer',
        ];
    }

    // Relaciones
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Wallet::class);
    }

    // Métodos Helper
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModel(): bool
    {
        return $this->role === 'modelo';
    }
}
