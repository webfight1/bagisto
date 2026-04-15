<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\Order;

class MeritInvoice extends Model
{
    protected $table = 'merit_invoices';

    protected $fillable = [
        'order_id',
        'merit_invoice_id',
        'invoice_no',
        'pdf_path',
        'status',
        'paid_at',
        'merit_response',
        'error_message',
    ];

    protected $casts = [
        'merit_response' => 'array',
        'paid_at'        => 'datetime',
    ];

    /**
     * Get the order that owns the invoice
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if invoice was successfully created
     */
    public function isCreated(): bool
    {
        return $this->status === 'created';
    }

    /**
     * Check if invoice creation failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if invoice is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
