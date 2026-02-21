<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'media_url',
        'media_type',
        'caption',
        'is_exclusive',
        'coin_cost',
        'likes_count',
        'comments_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
