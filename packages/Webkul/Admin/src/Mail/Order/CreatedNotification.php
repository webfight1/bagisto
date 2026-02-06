<?php

namespace Webkul\Admin\Mail\Order;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\Sales\Contracts\Order;

class CreatedNotification extends Mailable
{
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Order $order) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $adminDetails = core()->getAdminEmailDetails();
        $adminEmail = $adminDetails['email'] ?? null;
        $adminName = $adminDetails['name'] ?? null;
        $fallbackEmail = config('mail.from.address');
        $fallbackName = config('mail.from.name');

        $toEmail = $adminEmail ?: $fallbackEmail;
        $toName = $adminName ?: $fallbackName;

        return new Envelope(
            to: [
                new Address(
                    $toEmail,
                    $toName
                ),
            ],
            subject: trans('admin::app.emails.orders.created.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'admin::emails.orders.created',
        );
    }
}
