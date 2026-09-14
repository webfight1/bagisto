<?php

return [
    // Aiamaailm OÜ ametlikud rekvisiidid — kajastuvad kliendile saadetaval PDF-arvel.
    // Muudad seda, tuleb muuta ka MEMORY.md ja info@aiamaailm.ee vms.
    'company' => [
        'name'    => env('INVOICE_COMPANY_NAME', 'Aiamaailm OÜ'),
        'address' => env('INVOICE_COMPANY_ADDRESS', 'Jõekalda tee 21-1, Arkna, Lääne-Virumaa'),
        'reg'     => env('INVOICE_COMPANY_REG', '14292822'),
        'vat'     => env('INVOICE_COMPANY_VAT', 'EE102017292'),
        'iban'    => env('INVOICE_COMPANY_IBAN', 'EE542200221067271295'),
        'phone'   => env('INVOICE_COMPANY_PHONE', '+372 555 17 070'),
        'email'   => env('INVOICE_COMPANY_EMAIL', 'pillemataloja@gmail.com'),
    ],

    // Arvenumbri prefiks — "A000001", "A000002"…
    'number_prefix' => env('INVOICE_NUMBER_PREFIX', 'A'),
    'number_pad'    => env('INVOICE_NUMBER_PAD', 6),

    // Maksetähtaja pikkus päevades — kehtib ainult pangaülekande (moneytransfer)
    // puhul; kõik "prepaid" makseviisid (Esto, Everypay jne) märgitakse arvele
    // kohe TASUTUD sildiga ja tähtaega ei kuvata.
    'payment_deadline_days' => (int) env('INVOICE_PAYMENT_DEADLINE_DAYS', 7),

    // Kuhu PDF-id salvestatakse (kettal, storage/app/… suhtes).
    'storage_path'  => env('INVOICE_STORAGE_PATH', 'invoices'),

    // Kas listener automaatselt genereerib PDF + saadab mail kliendile pärast tellimuse loomist.
    'auto_generate' => (bool) env('INVOICE_AUTO_GENERATE', true),
    'auto_email'    => (bool) env('INVOICE_AUTO_EMAIL', true),
];
