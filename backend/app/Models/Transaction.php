<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'status',
        'method',
        'reference',
        'description',
        'receipt_path',
    ];

    protected $appends = ['receipt_url'];

    public function getReceiptUrlAttribute()
    {
        return $this->receipt_path ? asset('storage/' . $this->receipt_path) : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
