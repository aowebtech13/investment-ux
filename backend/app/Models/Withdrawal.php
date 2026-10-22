<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'method',
        'status',
        'details',
        'bank_name',
        'bank_account_holder',
        'bank_account_number',
        'bank_routing_number',
        'account_type',
        'rejection_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
