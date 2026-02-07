<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\Order;

class EstoWebhook extends Model
{
    protected $table = 'esto_webhooks';

    protected $fillable = [
        'reference',
        'order_id',
        'status',
        'amount',
        'currency',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
