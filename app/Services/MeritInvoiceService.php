<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\Sales\Models\Order;

class MeritInvoiceService
{
    protected string $apiId;
    protected string $apiKey;
    protected string $baseUrl;
    protected string $baseUrlV2;

    public function __construct()
    {
        $this->apiId = config('merit-invoice.api.id');
        $this->apiKey = config('merit-invoice.api.key');
        $this->baseUrl = config('merit-invoice.api.base_url');
        $this->baseUrlV2 = config('merit-invoice.api.base_url_v2');
    }

    /**
     * Create HMAC-SHA256 signature for Merit API
     */
    protected function createSignature(string $apiId, string $timestamp, string $httpBody): string
    {
        $dataToSign = $apiId . $timestamp . $httpBody;
        $hmacKey = $this->apiKey;
        
        $signatureBytes = hash_hmac('sha256', $dataToSign, $hmacKey, true);
        $signature = base64_encode($signatureBytes);
        
        return $signature;
    }

    /**
     * Get current timestamp in Merit API format (yyyyMMddHHmmss UTC)
     */
    protected function getTimestamp(): string
    {
        return gmdate('YmdHis');
    }

    /**
     * Get tax rates from Merit API
     */
    public function getTaxes(): ?array
    {
        $timestamp = $this->getTimestamp();
        $httpBody = json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = $this->createSignature($this->apiId, $timestamp, $httpBody);

        $url = $this->baseUrl . '/gettaxes';
        
        // Build URL with query parameters
        $url .= '?' . http_build_query([
            'apiId' => $this->apiId,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);
        
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->withBody($httpBody, 'application/json')->post($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Merit getTaxes failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Merit getTaxes exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create invoice in Merit for an order
     */
    public function createInvoice(Order $order): ?array
    {
        Log::info('Creating Merit invoice for order', ['order_id' => $order->id]);

        // Get taxes first
        $taxes = $this->getTaxes();
        if (!$taxes) {
            Log::error('Failed to get taxes from Merit');
            return null;
        }

        // Find tax with configured percentage (default 0%)
        $defaultTaxPct = config('merit-invoice.invoice.default_tax_pct', 0);
        $taxId = null;
        
        foreach ($taxes as $tax) {
            if ($tax['TaxPct'] == $defaultTaxPct) {
                $taxId = $tax['Id'];
                break;
            }
        }

        if (!$taxId && count($taxes) > 0) {
            $taxId = $taxes[0]['Id'];
            Log::warning('Using first available tax', ['tax_id' => $taxId]);
        }

        // Get billing address
        $billingAddress = $order->addresses()->where('address_type', 'order_billing')->first();
        
        if (!$billingAddress) {
            Log::error('No billing address found for order', ['order_id' => $order->id]);
            return null;
        }

        // Prepare customer data
        $customerName = $billingAddress->company_name 
            ? $billingAddress->company_name 
            : trim($billingAddress->first_name . ' ' . $billingAddress->last_name);

        $customerData = [
            'Name' => $customerName,
            'RegNo' => '', // Not available in current schema
            'NotTDCustomer' => empty($billingAddress->vat_id),
            'VatRegNo' => $billingAddress->vat_id ?? '',
            'CurrencyCode' => config('merit-invoice.invoice.currency_code', 'EUR'),
            'PaymentDeadLine' => config('merit-invoice.invoice.payment_deadline', 7),
            'OverDueCharge' => 0,
            'Address' => $billingAddress->address1 ?? '',
            'CountryCode' => $billingAddress->country ?? 'EE',
            'County' => $billingAddress->state ?? '',
            'City' => $billingAddress->city ?? '',
            'PostalCode' => $billingAddress->postcode ?? '',
            'PhoneNo' => $billingAddress->phone ?? '',
            'Email' => $billingAddress->email ?? $order->customer_email ?? '',
        ];

        // Prepare invoice rows from order items
        $invoiceRows = [];
        $totalAmount = 0;

        foreach ($order->items as $item) {
            $itemPrice = $item->price;
            $itemQty = $item->qty_ordered;
            $itemTotal = $itemPrice * $itemQty;
            $totalAmount += $itemTotal;

            $invoiceRows[] = [
                'Item' => [
                    'Code' => $item->sku ?? 'ITEM-' . $item->id,
                    'Description' => $item->name,
                    'Type' => 3, // 3 = product
                    'UOMName' => 'tk',
                ],
                'Quantity' => (float) $itemQty,
                'Price' => (float) $itemPrice,
                'DiscountPct' => 0,
                'DiscountAmount' => 0.00,
                'TaxId' => $taxId,
            ];
        }

        // Add shipping if exists
        if ($order->shipping_amount > 0) {
            $totalAmount += $order->shipping_amount;
            
            $invoiceRows[] = [
                'Item' => [
                    'Code' => 'SHIPPING',
                    'Description' => $order->shipping_title ?? 'Shipping',
                    'Type' => 2, // 2 = service
                    'UOMName' => 'tk',
                ],
                'Quantity' => 1.0,
                'Price' => (float) $order->shipping_amount,
                'DiscountPct' => 0,
                'DiscountAmount' => 0.00,
                'TaxId' => $taxId,
            ];
        }

        // Use order's grand_total as it already includes everything correctly
        // Bagisto prices are gross (include tax), so we use the order total directly
        $orderTotal = (float) $order->grand_total;
        $orderTaxAmount = (float) $order->tax_amount;

        // Prepare invoice data
        $invoiceNo = config('merit-invoice.invoice.number_prefix', 'ORDER-') . $order->increment_id;
        $now = $this->getTimestamp();
        $dueDate = gmdate('YmdHis', strtotime('+' . config('merit-invoice.invoice.payment_deadline', 7) . ' days'));

        $invoiceData = [
            'Customer' => $customerData,
            'DocDate' => $now,
            'DueDate' => $dueDate,
            'TransactionDate' => $now,
            'InvoiceNo' => $invoiceNo,
            'CurrencyCode' => config('merit-invoice.invoice.currency_code', 'EUR'),
            'InvoiceRow' => $invoiceRows,
            'TotalAmount' => round($orderTotal, 2),
            'RoundingAmount' => 0.00,
            'TaxAmount' => [
                [
                    'TaxId' => $taxId,
                    'Amount' => round($orderTaxAmount, 2),
                ],
            ],
            'Hcomment' => 'Order #' . $order->increment_id,
            'Fcomment' => 'Bagisto order ID: ' . $order->id,
        ];

        // Debug: Log invoice data before sending
        Log::info('Merit invoice data to send', [
            'order_id' => $order->id,
            'invoice_data' => $invoiceData,
        ]);

        // Send to Merit API
        $httpBody = json_encode($invoiceData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = $this->getTimestamp();
        $signature = $this->createSignature($this->apiId, $timestamp, $httpBody);

        $url = $this->baseUrl . '/sendinvoice';

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->withQueryParameters([
                'apiId' => $this->apiId,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ])->withBody($httpBody, 'application/json')->post($url);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Merit invoice created successfully', [
                    'order_id' => $order->id,
                    'invoice_id' => $result['InvoiceId'] ?? null,
                ]);
                return $result;
            }

            Log::error('Merit invoice creation failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Merit invoice creation exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get invoice PDF from Merit
     */
    public function getInvoicePdf(string $invoiceId, string $invoiceNo): ?string
    {
        $payload = [
            'Id' => $invoiceId,
            'DelivNote' => false,
        ];

        $httpBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = $this->getTimestamp();
        $signature = $this->createSignature($this->apiId, $timestamp, $httpBody);

        $url = $this->baseUrlV2 . '/getsalesinvpdf';

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->withQueryParameters([
                'apiId' => $this->apiId,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ])->withBody($httpBody, 'application/json')->post($url);

            if ($response->successful()) {
                $pdfData = $response->json();
                $fileContent = $pdfData['FileContent'] ?? null;

                if (!$fileContent) {
                    Log::error('No PDF content in Merit response');
                    return null;
                }

                $pdfBytes = base64_decode($fileContent);
                $fileName = $pdfData['FileName'] ?? $invoiceNo . '.pdf';

                // Store in storage/app/invoices/
                $storagePath = config('merit-invoice.pdf_storage_path', 'invoices');
                $filePath = $storagePath . '/' . $fileName;
                
                Storage::put($filePath, $pdfBytes);

                Log::info('Merit invoice PDF saved', ['path' => $filePath]);

                return $filePath;
            }

            Log::error('Merit PDF download failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Merit PDF download exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
