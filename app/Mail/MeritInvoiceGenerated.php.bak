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
        return $this->subject('Teie arve #' . $this->invoiceNo)
            ->view('emails.merit-invoice');
    }
}
