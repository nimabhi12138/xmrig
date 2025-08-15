<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCoinValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coin_id',
        'field_id',
        'value',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coin()
    {
        return $this->belongsTo(Coin::class);
    }

    public function field()
    {
        return $this->belongsTo(CoinField::class, 'field_id');
    }
}