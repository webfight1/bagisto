<?php

namespace App\Listeners;

use App\Mail\MeritInvoiceGenerated;
use App\Models\MeritInvoice;
use App\Services\MeritInvoiceService;
use Illuminate\Support\Facades\Cache;
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

        // Check if invoice already successfully created – never duplicate.
        // We check status='created' explicitly so that leftover 'failed' records
        // from previous retries do not shadow a successfully created record.
        $createdInvoice = MeritInvoice::where('order_id', $order->id)
            ->where('status', 'created')
            ->first();
        if ($createdInvoice) {
            Log::info('Merit invoice already created for order, skipping', [
                'order_id' => $order->id,
                'merit_invoice_id' => $createdInvoice->merit_invoice_id,
            ]);
            return;
        }

        // Reuse the most-recent non-created record for retry, or create a fresh one.
        $existingInvoice = MeritInvoice::where('order_id', $order->id)
            ->whereIn('status', ['failed', 'pending'])
            ->latest('id')
            ->first();

        if ($existingInvoice) {
            Log::info('Retrying Merit invoice for order', [
                'order_id' => $order->id,
                'previous_status' => $existingInvoice->status,
            ]);
            $meritInvoice = $existingInvoice;
            $meritInvoice->update(['status' => 'pending', 'error_message' => null]);
        } else {
            // Create fresh pending invoice record
            $meritInvoice = MeritInvoice::create([
                'order_id' => $order->id,
                'status' => 'pending',
            ]);
        }

        // Check for a pre-reserved invoice number (set during Esto checkout).
        $reservedInvoiceNo = null;
        if ($order->cart_id) {
            $cacheKey          = $this->meritService->reservedInvoiceCacheKey($order->cart_id);
            $reservedInvoiceNo = Cache::get($cacheKey);
            if ($reservedInvoiceNo) {
                Cache::forget($cacheKey);
                Log::info('Merit listener: using pre-reserved invoice number', [
                    'order_id'   => $order->id,
                    'invoice_no' => $reservedInvoiceNo,
                ]);
            }
        }

        try {
            // Create invoice in Merit
            $result = $this->meritService->createInvoice($order, $reservedInvoiceNo);

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

            // Mark as 'created' BEFORE sending the email so that if email fails
            // the retry logic does not attempt to re-create the invoice in Merit
            // (which would return "Korduv arve number" / duplicate invoice error).
            $meritInvoice->update([
                'merit_invoice_id' => $invoiceId,
                'invoice_no' => $invoiceNo,
                'pdf_path' => $pdfPath,
                'status' => 'created',
                'error_message' => null,
                'merit_response' => $result,
            ]);

            // For Esto orders: payment is already confirmed — mark invoice as paid in Merit.
            if ($order->payment && $order->payment->method === 'esto') {
                $billingAddress = $order->addresses()->where('address_type', 'order_billing')->first();
                $customerName   = $billingAddress
                    ? ($billingAddress->company_name ?: trim($billingAddress->first_name . ' ' . $billingAddress->last_name))
                    : ($order->customer_first_name . ' ' . $order->customer_last_name);

                $estoRef = $order->payment->additional['esto']['reference'] ?? '';

                $paid = $this->meritService->sendPayment(
                    invoiceNo:    $invoiceNo,
                    customerName: $customerName,
                    amount:       round((float) $order->grand_total, 2),
                    paymentDate:  gmdate('Ymd'),
                    refNo:        $estoRef,
                );

                if ($paid) {
                    $meritInvoice->update(['paid_at' => now()]);
                    Log::info('Merit invoice marked as paid via sendPayment', [
                        'order_id'   => $order->id,
                        'invoice_no' => $invoiceNo,
                    ]);
                } else {
                    Log::warning('Merit sendPayment failed after invoice creation', [
                        'order_id'   => $order->id,
                        'invoice_no' => $invoiceNo,
                    ]);
                }
            }

            if ($pdfPath) {
                // Generate URL via the public disk so it matches where the file was saved.
                $invoiceUrl = Storage::disk('public')->url($pdfPath);

                $billingEmail = optional(
                    $order->addresses()->where('address_type', 'order_billing')->first()
                )->email;

                try {
                    collect([$order->customer_email, $billingEmail])
                        ->filter()
                        ->unique()
                        ->each(function ($email) use ($order, $invoiceNo, $invoiceUrl): void {
                            Mail::to($email)->send(new MeritInvoiceGenerated($order, (string) $invoiceNo, $invoiceUrl));
                        });
                } catch (\Exception $mailException) {
                    // Email failure must NOT roll back the 'created' status – the invoice
                    // exists in Merit and the PDF is saved. Log the error separately.
                    Log::error('Merit invoice email send failed', [
                        'order_id' => $order->id,
                        'invoice_no' => $invoiceNo,
                        'error' => $mailException->getMessage(),
                    ]);
                    $meritInvoice->update([
                        'error_message' => 'Invoice created OK; email failed: ' . $mailException->getMessage(),
                    ]);
                }
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
