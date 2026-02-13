<?php

return [
    'emails' => [
        'dear'   => 'Lugupeetud :admin_name',
        'thanks' => 'Kui vajate abi, palun võtke meiega ühendust aadressil <a href=":link" style=":style">:email</a>.<br/>Aitäh!',

        'admin' => [
            'forgot-password' => [
                'description'    => 'Saate selle e-kirja, sest saime teie konto parooli lähtestamise taotluse.',
                'greeting'       => 'Unustasid parooli!',
                'reset-password' => 'Lähtesta parool',
                'subject'        => 'Parooli lähtestamise e-kiri',
            ],
        ],

        'customers' => [
            'registration' => [
                'description' => 'Uus kliendikonto on edukalt loodud. Nüüd saavad nad sisse logida oma e-posti aadressi ja parooli abil. Pärast sisselogimist on neil ligipääs erinevatele teenustele, sh varasemate tellimuste ülevaatamine, soovinimekirjade haldamine ja kontoandmete uuendamine.',
                'greeting'    => 'Tere tulemast uuele kliendile, :customer_name, kes just meie juures registreerus!',
                'subject'     => 'Uue kliendi registreerimine',
            ],

            'gdpr' => [
                'new-delete-request' => 'Uus andmete kustutamise taotlus',
                'new-update-request' => 'Uus andmete uuendamise taotlus',

                'new-request' => [
                    'customer-name'  => 'Kliendi nimi : ',
                    'delete-summary' => 'Kustutamise taotluse kokkuvõte',
                    'message'        => 'Sõnum : ',
                    'request-status' => 'Taotluse olek : ',
                    'request-type'   => 'Taotluse tüüp : ',
                    'update-summary' => 'Uuendamise taotluse kokkuvõte',
                ],

                'status-update' => [
                    'subject'        => 'GDPR taotlust on uuendatud',
                    'summary'        => 'GDPR taotluse olekut on uuendatud',
                    'request-status' => 'Taotluse olek:',
                    'request-type'   => 'Taotluse tüüp:',
                    'message'        => 'Sõnum:',
                ],
            ],
        ],

        'orders' => [
            'created' => [
                'greeting' => 'Teil on uus tellimus :order_id, mis esitati :created_at',
                'subject'  => 'Uue tellimuse kinnitus',
                'summary'  => 'Tellimuse kokkuvõte',
                'title'    => 'Tellimuse kinnitus!',
            ],

            'invoiced' => [
                'greeting' => 'Teie arve #:invoice_id tellimuse :order_id kohta loodi :created_at',
                'subject'  => 'Uue arve kinnitus',
                'summary'  => 'Arve kokkuvõte',
                'title'    => 'Arve kinnitus!',
            ],

            'shipped' => [
                'greeting' => 'Olete saatnud tellimuse :order_id, mis esitati :created_at',
                'subject'  => 'Uue saadetise kinnitus',
                'summary'  => 'Saadetise kokkuvõte',
                'title'    => 'Tellimus saadetud!',
            ],

            'inventory-source' => [
                'greeting' => 'Olete saatnud tellimuse :order_id, mis esitati :created_at',
                'subject'  => 'Uue saadetise kinnitus',
                'summary'  => 'Saadetise kokkuvõte',
                'title'    => 'Tellimus saadetud!',
            ],

            'refunded' => [
                'greeting' => 'Olete teinud tagasimakse tellimuse :order_id eest, mis esitati :created_at',
                'subject'  => 'Uue tagasimakse kinnitus',
                'summary'  => 'Tagasimakse kokkuvõte',
                'title'    => 'Tagasimakse tehtud!',
            ],

            'canceled' => [
                'greeting' => 'Olete tühistanud tellimuse :order_id, mis esitati :created_at',
                'subject'  => 'Tellimus tühistatud',
                'summary'  => 'Tellimuse kokkuvõte',
                'title'    => 'Tellimus tühistatud!',
            ],

            'billing-address'            => 'Arve aadress',
            'carrier'                    => 'Vedaja',
            'contact'                    => 'Kontakt',
            'discount'                   => 'Allahindlus',
            'excl-tax'                   => 'Ilma maksuta: ',
            'grand-total'                => 'Kokku',
            'name'                       => 'Nimi',
            'payment'                    => 'Makse',
            'price'                      => 'Hind',
            'qty'                        => 'Kogus',
            'shipping-address'           => 'Tarneaadress',
            'shipping-handling-excl-tax' => 'Transport ja käsitlemine (ilma maksuta)',
            'shipping-handling-incl-tax' => 'Transport ja käsitlemine (koos maksuga)',
            'shipping-handling'          => 'Transport ja käsitlemine',
            'shipping'                   => 'Transport',
            'sku'                        => 'SKU',
            'subtotal-excl-tax'          => 'Vahesumma (ilma maksuta)',
            'subtotal-incl-tax'          => 'Vahesumma (koos maksuga)',
            'subtotal'                   => 'Vahesumma',
            'tax'                        => 'Maks',
            'tracking-number'            => 'Jälgimisnumber : :tracking_number',
        ],
    ],
];
