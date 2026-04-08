<?php

namespace App\Listeners;

use App\Mail\MeritInvoiceGenerated;
use App\Models\MeritInvoice;
use App\Services\MeritInvoiceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Webkul\Sales\Contracts\Order;

class CreateMeritInvoice
{
    protected MeritInvoiceService $meritService;

    public function __construct(MeritInvoiceService $meritService)
    {
        $this->meritService = $meritService;
    }

    /**
     * Handle the event - create Merit invoice after order is created
     */
    public function handle(Order $order): void
    {
        // Check if Merit Invoice integration is enabled
        if (!config('merit-invoice.enabled', true)) {
            Log::info('Merit invoice integration disabled', [
                'order_id' => $order->id,
            ]);
            return;
        }

        // Only create invoice for paid orders (processing or completed status)
        $allowedStatuses = [
            \Webkul\Sales\Models\Order::STATUS_PROCESSING,
            \Webkul\Sales\Models\Order::STATUS_COMPLETED,
        ];
        
        if (!in_array($order->status, $allowedStatuses, true)) {
            Log::info('Skipping Merit invoice - order not paid yet', [
                'order_id' => $order->id,
                'status' => $order->status,
            ]);
            return;
        }

        // Check if invoice already successfully created – do not duplicate.
        // Failed or pending records are retried on the next event fire.
        $existingInvoice = MeritInvoice::where('order_id', $order->id)->first();
        if ($existingInvoice) {
            if ($existingInvoice->status === 'created') {
                Log::info('Merit invoice already created for order, skipping', [
                    'order_id' => $order->id,
                    'merit_invoice_id' => $existingInvoice->merit_invoice_id,
                ]);
                return;
            }
            // Previous attempt failed or is stuck as pending – retry and reuse the record.
            Log::info('Retrying Merit invoice for order', [
                'order_id' => $order->id,
                'previous_status' => $existingInvoice->status,
            ]);
            $meritInvoice = $existingInvoice;
            $meritInvoice->update(['status' => 'pending', 'error_message' => null]);
        } else {
            // Create pending invoice record
            $meritInvoice = MeritInvoice::create([
                'order_id' => $order->id,
                'status' => 'pending',
            ]);
        }

        try {
            // Create invoice in Merit
            $result = $this->meritService->createInvoice($order);

            if (!$result) {
                $meritInvoice->update([
                    'status' => 'failed',
                    'error_message' => 'Failed to create invoice in Merit API',
                ]);
                
                Log::error('Merit invoice creation failed', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            $invoiceId = $result['InvoiceId'] ?? null;
            $invoiceNo = $result['InvoiceNo'] ?? null;

            if (!$invoiceId) {
                $meritInvoice->update([
                    'status' => 'failed',
                    'error_message' => 'No InvoiceId in Merit response',
                    'merit_response' => $result,
                ]);
                
                Log::error('No InvoiceId in Merit response', [
                    'order_id' => $order->id,
                    'response' => $result,
                ]);
                return;
            }

            // Download PDF
            $pdfPath = $this->meritService->getInvoicePdf($invoiceId, $invoiceNo);

            // Update invoice record
            $meritInvoice->update([
                'merit_invoice_id' => $invoiceId,
                'invoice_no' => $invoiceNo,
                'pdf_path' => $pdfPath,
                'status' => 'created',
                'merit_response' => $result,
            ]);

            if ($pdfPath) {
                // Generate URL via the public disk so it matches where the file was saved.
                $invoiceUrl = Storage::disk('public')->url($pdfPath);

                $billingEmail = optional(
                    $order->addresses()->where('address_type', 'order_billing')->first()
                )->email;

                collect([$order->customer_email, $billingEmail])
                    ->filter()
                    ->unique()
                    ->each(function ($email) use ($order, $invoiceNo, $invoiceUrl): void {
                        Mail::to($email)->send(new MeritInvoiceGenerated($order, (string) $invoiceNo, $invoiceUrl));
                    });
            }

            Log::info('Merit invoice created successfully', [
                'order_id' => $order->id,
                'merit_invoice_id' => $invoiceId,
                'invoice_no' => $invoiceNo,
                'pdf_path' => $pdfPath,
            ]);

        } catch (\Exception $e) {
            $meritInvoice->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Merit invoice creation exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
