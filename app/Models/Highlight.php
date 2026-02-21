<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Highlight extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'cover_url',
        'is_exclusive',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stories()
    {
        return $this->belongsToMany(Story::class);
    }
}
