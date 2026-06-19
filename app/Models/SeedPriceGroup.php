<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Product\Models\Product;

class SeedPriceGroup extends Model
{
    protected $fillable = [
        'code',
        'name',
        'price',
        'merit_code',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price'     => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seed_price_group_id');
    }
}
