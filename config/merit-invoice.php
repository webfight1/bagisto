<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Merit Invoice Integration Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether the Merit invoice integration is active.
    | Set to false to disable all Merit invoice functionality.
    |
    */
    'enabled' => env('MERIT_INVOICE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Merit API Credentials
    |--------------------------------------------------------------------------
    |
    | Your Merit API credentials for accessing the accounting system.
    |
    */
    'api' => [
        'id'   => env('MERIT_API_ID', ''),
        'key'  => env('MERIT_API_KEY', ''),
        'base_url'    => env('MERIT_BASE_URL', 'https://aktiva.merit.ee/api/v1'),
        'base_url_v2' => env('MERIT_BASE_URL_V2', 'https://aktiva.merit.ee/api/v2'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for generated invoices.
    |
    */
    'invoice' => [
        'number_prefix'   => env('MERIT_INVOICE_PREFIX', 'ORDER-'),
        'payment_deadline'=> env('MERIT_PAYMENT_DEADLINE', 7),
        'currency_code'   => env('MERIT_CURRENCY_CODE', 'EUR'),
        'default_tax_pct' => env('MERIT_DEFAULT_TAX_PCT', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | Bank IBAN for registering payments (sendPayment endpoint).
    | Without an IBAN, Merit places the payment in the cash register.
    |
    */
    'payment' => [
        'bank_iban' => env('MERIT_BANK_IBAN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Storage
    |--------------------------------------------------------------------------
    |
    | Path where Merit invoice PDFs are stored.
    |
    */
    'pdf_storage_path' => env('MERIT_PDF_STORAGE_PATH', 'invoices'),
];
