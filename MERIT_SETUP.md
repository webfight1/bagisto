# Merit Invoice Integration Setup

## Overview

Automatic Merit invoice creation when orders are placed via ESTO payment webhook.

## Installation Steps

### 1. Environment Configuration

Merit API credentials are already added to `.env`:

```env
MERIT_API_ID=7eef5aa5-70f4-4b37-9a69-e5c371776e37
MERIT_API_KEY=Lpui4NTBSFOzWvQn3me2ksjIqVrN+hSFQYjW1LgFrVo=
MERIT_BASE_URL=https://aktiva.merit.ee/api/v1
MERIT_BASE_URL_V2=https://aktiva.merit.ee/api/v2
MERIT_INVOICE_PREFIX=ORDER-
MERIT_PAYMENT_DEADLINE=7
MERIT_CURRENCY_CODE=EUR
MERIT_DEFAULT_TAX_PCT=0
MERIT_PDF_STORAGE_PATH=invoices
```

### 2. Run Database Migration

```bash
cd /Applications/MAMP/htdocs/nailedit_laravel/bagisto/src
php artisan migrate
```

This creates the `merit_invoices` table.

### 3. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### 4. Create Storage Directory

```bash
mkdir -p storage/app/invoices
chmod -R 775 storage/app/invoices
```

## How It Works

1. **ESTO Webhook** receives payment confirmation
2. **Order Created** with status `processing`
3. **Event Listener** (`CreateMeritInvoice`) triggers automatically
4. **Merit API** creates invoice with order data
5. **PDF Downloaded** and stored in `storage/app/invoices/`
6. **Database Record** created in `merit_invoices` table

## Database Schema

Table: `merit_invoices`

- `id` - Primary key
- `order_id` - Foreign key to orders table
- `merit_invoice_id` - Merit's InvoiceId
- `invoice_no` - Invoice number (e.g., ORDER-12345)
- `pdf_path` - Path to stored PDF
- `status` - pending, created, failed
- `merit_response` - Full JSON response from Merit
- `error_message` - Error details if failed
- `created_at`, `updated_at`

## Customer Data Mapping

From order billing address:

- **Company Name** → Merit Customer.Name (or first_name + last_name)
- **VAT ID** → Merit Customer.VatRegNo
- **Address** → Merit Customer.Address
- **City** → Merit Customer.City
- **State** → Merit Customer.County
- **Country** → Merit Customer.CountryCode
- **Postcode** → Merit Customer.PostalCode
- **Phone** → Merit Customer.PhoneNo
- **Email** → Merit Customer.Email

## Invoice Details

- **Invoice Number**: `ORDER-{order_increment_id}`
- **Tax Rate**: 0% (no VAT obligation currently)
- **Payment Deadline**: 7 days
- **Currency**: EUR
- **Items**: All order items + shipping

## Testing

### Test Invoice Creation Manually

```php
use App\Services\MeritInvoiceService;
use Webkul\Sales\Models\Order;

$service = app(MeritInvoiceService::class);
$order = Order::find(1); // Replace with actual order ID
$result = $service->createInvoice($order);
```

### Check Logs

```bash
tail -f storage/logs/laravel.log
```

Look for:
- `Creating Merit invoice for order`
- `Merit invoice created successfully`
- `Merit invoice creation failed`

### Check Database

```sql
SELECT * FROM merit_invoices ORDER BY created_at DESC LIMIT 10;
```

## Troubleshooting

### Invoice Not Created

1. Check order status is `processing`
2. Check logs: `storage/logs/laravel.log`
3. Verify Merit API credentials in `.env`
4. Check `merit_invoices` table for error messages

### PDF Not Downloaded

1. Check storage permissions: `chmod -R 775 storage/app/invoices`
2. Check Merit API v2 endpoint is accessible
3. Check logs for PDF download errors

### Connection Errors

1. Verify Merit API credentials
2. Check network connectivity to `aktiva.merit.ee`
3. Verify HMAC signature generation

## Files Created

- `config/merit.php` - Configuration
- `app/Services/MeritInvoiceService.php` - Merit API integration
- `app/Models/MeritInvoice.php` - Database model
- `app/Listeners/CreateMeritInvoice.php` - Event listener
- `database/migrations/2026_04_02_151900_create_merit_invoices_table.php` - Migration
- `app/Providers/AppServiceProvider.php` - Event registration (modified)
- `.env` - Merit credentials (modified)

## Future Enhancements

1. **Invoice Number Format**: Adjust per accountant requirements
2. **VAT Handling**: Add product-specific tax rates when VAT obligation starts
3. **Retry Logic**: Automatic retry for failed invoices
4. **Admin Panel**: View/download invoices from order details
5. **Email Notifications**: Send invoice PDF to customer

## Support

For issues or questions, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Merit API documentation: https://api.merit.ee/
3. Database records: `merit_invoices` table
