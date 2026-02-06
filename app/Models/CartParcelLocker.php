<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Checkout\Models\Cart;

class CartParcelLocker extends Model
{
    protected $table = 'cart_parcel_lockers';

    protected $fillable = [
        'cart_id',
        'carrier',
        'locker_id',
        'locker_name',
        'locker_address',
        'locker_city',
        'locker_postcode',
        'locker_country',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}
