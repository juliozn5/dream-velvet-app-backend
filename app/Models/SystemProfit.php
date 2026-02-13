<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemProfit extends Model
{
    protected $fillable = ['user_id', 'model_id', 'amount', 'source'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function model()
    {
        return $this->belongsTo(User::class, 'model_id');
    }
}
