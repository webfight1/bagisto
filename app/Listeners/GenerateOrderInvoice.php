<?php

namespace App\Listeners;

use App\Mail\OrderInvoiceGenerated;
use App\Services\OrderInvoicePdfService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Webkul\Sales\Contracts\Order;

class GenerateOrderInvoice
{
    public function __construct(
        private readonly OrderInvoicePdfService $pdfService
    ) {}

    /**
     * Fired by Bagisto `checkout.order.save.after` — creates a branded PDF invoice
     * and (if enabled) emails it to the customer. Idempotent per order.
     */
    public function handle(Order $order): void
    {
        if (! config('aiamaailm-invoice.auto_generate', true)) {
            return;
        }

        $email = $order->customer_email;
        if (! $email) {
            Log::warning('Order invoice: skipping — no customer email', ['order_id' => $order->id]);
            return;
        }

        try {
            $relativePath = $this->pdfService->generate($order);
            $absolutePath = $this->pdfService->absolutePathFor($relativePath);
            $invoiceNo    = $this->pdfService->invoiceNumberFor($order);

            if (config('aiamaailm-invoice.auto_email', true)) {
                Mail::to($email)->send(new OrderInvoiceGenerated($order, $invoiceNo, $absolutePath));

                Log::info('Order invoice mailed', [
                    'order_id'   => $order->id,
                    'invoice_no' => $invoiceNo,
                    'to'         => $email,
                ]);
            }
        } catch (\Throwable $e) {
            // Never let invoice failure block order creation flow.
            Log::error('Order invoice generation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
        }
    }
}
