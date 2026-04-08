<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Webkul\Sales\Contracts\Order;

class MeritInvoiceGenerated extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $invoiceNo,
        public string $invoiceUrl
    ) {}

    public function build(): self
    {
        return $this->from(config('mail.from.address'), 'Nailedit')
            ->subject('Tellimuse kinnitus #' . $this->order->increment_id . ' – arve ' . $this->invoiceNo)
            ->view('emails.merit-invoice');
    }
}
