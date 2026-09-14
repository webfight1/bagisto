<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\Sales\Models\Order;

class OrderInvoicePdfService
{
    /**
     * Generate a branded PDF invoice for the order and return its storage-relative path.
     * Idempotent: if a PDF already exists for the order, returns the existing path.
     */
    public function generate(Order $order): string
    {
        // Bagisto's Order::billing_address() is a filtered collection accessor,
        // not a real Eloquent relation, so eager-load the underlying `addresses`
        // + `payment` and let the blade read via the magic accessor.
        $order->loadMissing(['items', 'addresses', 'payment']);

        $invoiceNo = $this->invoiceNumberFor($order);
        $filename  = $this->filenameFor($invoiceNo, $order);
        $relative  = rtrim(config('aiamaailm-invoice.storage_path', 'invoices'), '/') . '/' . $filename;

        if (Storage::exists($relative)) {
            return $relative;
        }

        $pdf = Pdf::loadView('invoices.pdf', [
            'order'        => $order,
            'invoiceNo'    => $invoiceNo,
            'company'      => config('aiamaailm-invoice.company'),
            'paymentTitle' => $this->paymentTitle($order),
        ])->setPaper('A4', 'portrait');

        Storage::put($relative, $pdf->output());

        Log::info('Order invoice PDF generated', [
            'order_id'   => $order->id,
            'invoice_no' => $invoiceNo,
            'path'       => $relative,
        ]);

        return $relative;
    }

    public function invoiceNumberFor(Order $order): string
    {
        $prefix = (string) config('aiamaailm-invoice.number_prefix', 'A');
        $pad    = (int) config('aiamaailm-invoice.number_pad', 6);
        return $prefix . str_pad((string) $order->id, $pad, '0', STR_PAD_LEFT);
    }

    public function absolutePathFor(string $relativePath): string
    {
        return Storage::path($relativePath);
    }

    private function filenameFor(string $invoiceNo, Order $order): string
    {
        return "{$invoiceNo}_order-{$order->id}.pdf";
    }

    private function paymentTitle(Order $order): string
    {
        $method = optional($order->payment)->method;
        return match ($method) {
            'esto'                => 'Esto (kaardimakse)',
            'everypay'            => 'Pangalink (Everypay)',
            'moneytransfer'       => 'Pangaülekanne',
            'cashondelivery'      => 'Sularaha üleandmisel',
            'paypal_standard'     => 'PayPal',
            'paypal_smart_button' => 'PayPal',
            default               => (string) ($method ?? '—'),
        };
    }
}
