<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinField extends Model
{
    use HasFactory;

    protected $fillable = [
        'coin_id',
        'title',
        'type',
        'placeholder',
        'is_required',
        'options_json',
        'help_text',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function coin()
    {
        return $this->belongsTo(Coin::class);
    }
}