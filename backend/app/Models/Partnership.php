<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partnership extends Model
{
    protected $fillable = [
        'name',
        'description',
        'amount',
        'benefits',
        'is_active',
    ];

    protected $casts = [
        'benefits' => 'array',
    ];
}
