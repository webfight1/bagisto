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
     * Get next invoice number from Merit API
     * Queries last 3 months of invoices and increments the last invoice number
     */
    public function getNextInvoiceNumber(): ?string
    {
        $periodEnd = gmdate('Ymd');
        $periodStart = gmdate('Ymd', strtotime('-3 months'));
        
        $payload = [
            'PeriodStart' => $periodStart,
            'PeriodEnd' => $periodEnd,
        ];
        
        $httpBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = $this->getTimestamp();
        $signature = $this->createSignature($this->apiId, $timestamp, $httpBody);

        $url = $this->baseUrl . '/getinvoices';
        
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

            if (!$response->successful()) {
                Log::error('Merit getInvoices failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $invoices = $response->json();
            
            if (empty($invoices)) {
                // No invoices found, start with 1
                return '1';
            }

            // Find the highest invoice number
            $lastInvoiceNo = null;
            $maxNumber = 0;
            
            foreach ($invoices as $invoice) {
                $invoiceNo = $invoice['InvoiceNo'] ?? '';
                
                // Extract numeric part from invoice number
                if (preg_match('/(\d+)/', $invoiceNo, $matches)) {
                    $number = (int) $matches[1];
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                        $lastInvoiceNo = $invoiceNo;
                    }
                }
            }
            
            if (!$lastInvoiceNo) {
                return '1';
            }
            
            // Increment the number while preserving format
            // e.g., "100082" -> "100083", "77" -> "78", "INV-0042" -> "INV-0043"
            $nextNumber = $maxNumber + 1;
            
            // Replace the numeric part with incremented value, preserving leading zeros
            $nextInvoiceNo = preg_replace_callback('/(\d+)/', function($matches) use ($nextNumber) {
                $originalLength = strlen($matches[1]);
                return str_pad($nextNumber, $originalLength, '0', STR_PAD_LEFT);
            }, $lastInvoiceNo, 1);
            
            Log::info('Merit: Generated next invoice number', [
                'last_invoice' => $lastInvoiceNo,
                'next_invoice' => $nextInvoiceNo,
            ]);
            
            return $nextInvoiceNo;
            
        } catch (\Exception $e) {
            Log::error('Merit getInvoices exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
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

        // Get shipping address (fallback for street address if billing is empty)
        $shippingAddress = $order->addresses()->where('address_type', 'order_shipping')->first();

        // Prepare customer data
        $customerName = $billingAddress->company_name
            ? $billingAddress->company_name
            : trim($billingAddress->first_name . ' ' . $billingAddress->last_name);

        // Resolve ISO 3166-1 alpha-2 country code.
        // Bagisto normally stores the 2-letter code, but some orders may have the full name.
        $countryRaw = $billingAddress->country ?? 'EE';
        if (strlen($countryRaw) === 2) {
            $countryCode = strtoupper($countryRaw);
        } else {
            $countryModel = \Webkul\Core\Models\Country::whereHas('translations', function ($q) use ($countryRaw) {
                $q->where('name', $countryRaw);
            })->first();
            $countryCode = $countryModel ? $countryModel->code : 'EE';
            Log::info('Merit: resolved country name to code', [
                'raw' => $countryRaw,
                'resolved' => $countryCode,
            ]);
        }

        // Resolve county/state name.
        // Bagisto may store the state as a code ("EE-86") or as a plain name ("võru").
        $stateRaw = $billingAddress->state ?? '';
        if (preg_match('/^[A-Z]{2}-/', $stateRaw)) {
            // It's a Bagisto state code – look up the human-readable name.
            $stateModel = \Webkul\Core\Models\CountryState::where('code', $stateRaw)->first();
            $county = $stateModel ? $stateModel->default_name : $stateRaw;
        } else {
            // Plain text – capitalise the first letter so "võru" becomes "Võru".
            $county = $stateRaw !== '' ? mb_strtoupper(mb_substr($stateRaw, 0, 1)) . mb_substr($stateRaw, 1) : '';
        }

        // Helper: return empty string if value is null, "0", 0, or the em-dash
        // placeholder (—) that Bagisto inserts when address is not filled in.
        $cleanAddr = static function ($value): string {
            $str = (string) ($value ?? '');
            // Strip em-dash placeholder and zero-only values
            if ($str === '—' || $str === '-' || $str === '0' || $str === '') {
                return '';
            }
            return $str;
        };

        // Use billing address, but fallback to shipping address for street if billing is empty
        $streetAddress = $cleanAddr($billingAddress->address ?? $billingAddress->address1 ?? '');
        
        Log::info('Merit: Address resolution', [
            'order_id' => $order->id,
            'billing_address' => $billingAddress->address ?? null,
            'billing_address1' => $billingAddress->address1 ?? null,
            'billing_cleaned' => $streetAddress,
            'has_shipping' => (bool) $shippingAddress,
            'shipping_address' => $shippingAddress ? ($shippingAddress->address ?? null) : null,
            'shipping_address1' => $shippingAddress ? ($shippingAddress->address1 ?? null) : null,
        ]);
        
        if (empty($streetAddress) && $shippingAddress) {
            $streetAddress = $cleanAddr($shippingAddress->address ?? $shippingAddress->address1 ?? '');
            Log::info('Merit: Using shipping address', [
                'order_id' => $order->id,
                'final_street' => $streetAddress,
            ]);
        }

        // Build full address on one line for Merit API
        // Format: "Street, City, County" (e.g., "Tuleviku tee 6, Lohkva, Luunja vald, Tartumaa")
        $city = $cleanAddr($billingAddress->city ?? '');
        
        $addressParts = array_filter([$streetAddress, $city, $county]);
        $fullAddress = implode(', ', $addressParts);

        $customerData = [
            'Name' => $customerName,
            'RegNo' => '', // Not available in current schema
            'NotTDCustomer' => empty($billingAddress->vat_id),
            'VatRegNo' => $billingAddress->vat_id ?? '',
            'CurrencyCode' => config('merit-invoice.invoice.currency_code', 'EUR'),
            'PaymentDeadLine' => config('merit-invoice.invoice.payment_deadline', 7),
            'OverDueCharge' => 0,
            'Address' => $fullAddress,
            'CountryCode' => $countryCode,
            'County' => '',
            'City' => '',
            'PostalCode' => '',
            'PhoneNo' => $cleanAddr($billingAddress->phone ?? ''),
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

            // Force proper float precision by converting through string
            $roundedPrice = floatval(sprintf('%.2f', (float) $itemPrice));

            $invoiceRows[] = [
                'Item' => [
                    'Code' => $item->sku ?? 'ITEM-' . $item->id,
                    'Description' => $item->name,
                    'Type' => 3, // 3 = product
                    'UOMName' => 'tk',
                ],
                'Quantity' => (float) $itemQty,
                'Price' => $roundedPrice,
                'DiscountPct' => 0,
                'DiscountAmount' => 0.00,
                'TaxId' => $taxId,
            ];
        }

        // Add shipping if exists
        if ($order->shipping_amount > 0) {
            $totalAmount += $order->shipping_amount;
            
            // Force proper float precision by converting through string
            $roundedShipping = floatval(sprintf('%.2f', (float) $order->shipping_amount));
            
            $invoiceRows[] = [
                'Item' => [
                    'Code' => 'SHIPPING',
                    'Description' => $order->shipping_title ?? 'Shipping',
                    'Type' => 2, // 2 = service
                    'UOMName' => 'tk',
                ],
                'Quantity' => 1.0,
                'Price' => $roundedShipping,
                'DiscountPct' => 0,
                'DiscountAmount' => 0.00,
                'TaxId' => $taxId,
            ];
        }

        // Use order's grand_total as it already includes everything correctly
        // Bagisto prices are gross (include tax), so we use the order total directly
        // Force proper float precision to match invoice rows
        $orderTotal = floatval(sprintf('%.2f', (float) $order->grand_total));
        $orderTaxAmount = floatval(sprintf('%.2f', (float) $order->tax_amount));

        // Prepare invoice data
        // Get next invoice number from Merit API
        $invoiceNo = $this->getNextInvoiceNumber();
        if (!$invoiceNo) {
            Log::error('Failed to get next invoice number from Merit', ['order_id' => $order->id]);
            // Fallback to old method
            $invoiceNo = config('merit-invoice.invoice.number_prefix', 'ORDER-') . $order->increment_id;
        }
        
        $now = $this->getTimestamp();
        $dueDate = gmdate('YmdHis', strtotime('+' . config('merit-invoice.invoice.payment_deadline', 7) . ' days'));

        // Get Esto reference if payment method is Esto
        $estoReference = null;
        if ($order->payment && $order->payment->method === 'esto') {
            $additional = $order->payment->additional ?? [];
            $estoReference = $additional['esto']['reference'] ?? null;
            Log::info('Merit: Esto payment detected', [
                'order_id' => $order->id,
                'payment_method' => $order->payment->method,
                'additional' => $additional,
                'esto_reference' => $estoReference,
            ]);
        } else {
            Log::info('Merit: Not Esto payment', [
                'order_id' => $order->id,
                'has_payment' => (bool) $order->payment,
                'payment_method' => $order->payment ? $order->payment->method : null,
            ]);
        }

        // Build comments with Esto reference if available
        $fcomment = 'Bagisto order ID: ' . $order->id;
        if ($estoReference) {
            $fcomment .= ' | Esto ref: ' . $estoReference;
        }

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
            'Fcomment' => $fcomment,
        ];

        // Debug: Log invoice data before sending
        Log::info('Merit invoice data to send', [
            'order_id' => $order->id,
            'invoice_data' => $invoiceData,
        ]);

        // Send to Merit API
        // Set precision to avoid float representation issues
        ini_set('serialize_precision', '2');
        ini_set('precision', '14');
        $httpBody = json_encode($invoiceData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        ini_restore('serialize_precision');
        ini_restore('precision');
        
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
                $rawFileName = $pdfData['FileName'] ?? $invoiceNo . '.pdf';

                // Sanitise the filename so the URL never contains spaces or
                // Estonian special characters (Merit returns names like
                // "Bonex OÜ Arve nr ORDER-8.pdf").
                $fileName = $this->sanitizeFileName($rawFileName);

                // Store on the public disk so it is accessible via /storage/ URL.
                // Using Storage::disk('public') ensures the file always lands in
                // storage/app/public/ regardless of the FILESYSTEM_DISK env value.
                $storagePath = config('merit-invoice.pdf_storage_path', 'invoices');
                $filePath = $storagePath . '/' . $fileName;

                Storage::disk('public')->put($filePath, $pdfBytes);

                Log::info('Merit invoice PDF saved', ['path' => $filePath, 'disk' => 'public']);

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

    /**
     * Sanitise a Merit-provided filename so it is safe to use in a URL.
     *
     * Replaces Estonian/special characters with ASCII equivalents, converts
     * spaces to hyphens, and strips any remaining non-alphanumeric characters
     * (except hyphens, underscores and dots).
     *
     * Example: "Bonex OÜ Arve nr ORDER-8.pdf" → "Bonex-OUe-Arve-nr-ORDER-8.pdf"
     */
    protected function sanitizeFileName(string $fileName): string
    {
        $map = [
            'Ä' => 'Ae', 'ä' => 'ae',
            'Ö' => 'Oe', 'ö' => 'oe',
            'Ü' => 'Ue', 'ü' => 'ue',
            'Õ' => 'Oe', 'õ' => 'oe',
            'Š' => 'Sh', 'š' => 'sh',
            'Ž' => 'Zh', 'ž' => 'zh',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
            'É' => 'E', 'Ê' => 'E', 'è' => 'e', 'é' => 'e', 'ê' => 'e',
            'Ï' => 'I', 'î' => 'i', 'ï' => 'i',
            'Ô' => 'O', 'ô' => 'o',
            'Û' => 'U', 'û' => 'u',
            'ß' => 'ss',
        ];

        // Keep the extension intact
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $base = pathinfo($fileName, PATHINFO_FILENAME);

        // Transliterate known characters
        $base = strtr($base, $map);

        // Replace spaces and any remaining non-safe characters with hyphens
        $base = preg_replace('/[^A-Za-z0-9_\-]/', '-', $base);

        // Collapse multiple consecutive hyphens
        $base = preg_replace('/-{2,}/', '-', $base);

        $base = trim($base, '-');

        return $ext ? $base . '.' . $ext : $base;
    }
}
