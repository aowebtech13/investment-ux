<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    protected $fillable = [
        'user_id',
        'investment_plan_id',
        'amount',
        'profit',
        'start_date',
        'end_date',
        'last_profit_at',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(InvestmentPlan::class, 'investment_plan_id');
    }

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_profit_at' => 'datetime',
        'amount' => 'float',
        'profit' => 'float',
    ];
}
