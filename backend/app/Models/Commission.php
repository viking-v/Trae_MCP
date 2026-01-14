<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $fillable = [
        'activation_id',
        'from_user_id',
        'to_user_id',
        'level',
        'rate',
        'amount',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'amount' => 'decimal:2',
    ];

    public function activation()
    {
        return $this->belongsTo(Activation::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
