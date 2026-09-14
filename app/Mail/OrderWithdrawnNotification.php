<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Webkul\Sales\Models\Order;

class OrderWithdrawnNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $previousStatus) {}

    public function build()
    {
        return $this
            ->subject('Klient taganes tellimusest #'.($this->order->increment_id ?? $this->order->id))
            ->view('emails.order-withdrawn');
    }
}
