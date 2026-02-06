<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\Order;

class OrderParcelLocker extends Model
{
    protected $table = 'order_parcel_lockers';

    protected $fillable = [
        'order_id',
        'carrier',
        'locker_id',
        'locker_name',
        'locker_address',
        'locker_city',
        'locker_postcode',
        'locker_country',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
