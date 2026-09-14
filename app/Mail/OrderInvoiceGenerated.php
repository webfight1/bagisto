<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Sales\Contracts\Order;

class OrderInvoiceGenerated extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $invoiceNo,
        public string $pdfAbsolutePath
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address'),
            subject: "Arve {$this->invoiceNo} · tellimus #".($this->order->increment_id ?? $this->order->id).' · Aiamaailm',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-invoice',
            with: [
                'order'     => $this->order,
                'invoiceNo' => $this->invoiceNo,
                'company'   => config('aiamaailm-invoice.company'),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfAbsolutePath)
                ->as("{$this->invoiceNo}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
