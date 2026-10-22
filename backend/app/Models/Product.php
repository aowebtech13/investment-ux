<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'subtitle',
        'description',
        'price',
        'old_price',
        'percent',
        'rating',
        'brand',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * The frontend reads `img` and `desc` on each product. We append:
     *   - `img`        -> absolute URL to the product image (or null)
     *   - `desc`       -> the description (frontend compatibility alias)
     *   - `image_url`  -> absolute URL to the product image (or null)
     */
    protected $appends = ['img', 'desc', 'image_url'];

    public function getImgAttribute()
    {
        return $this->image ? url(Storage::url($this->image)) : null;
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? url(Storage::url($this->image)) : null;
    }

    public function getDescAttribute()
    {
        return $this->description;
    }
}

