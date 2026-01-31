<?php

return [
    'flatrate' => [
        'code'         => 'flatrate',
        'title'        => 'Flat Rate',
        'description'  => 'Flat Rate Shipping',
        'active'       => true,
        'default_rate' => '10',
        'type'         => 'per_unit',
        'class'        => 'Webkul\Shipping\Carriers\FlatRate',
    ],

    'free' => [
        'code'         => 'free',
        'title'        => 'Free Shipping',
        'description'  => 'Free Shipping',
        'active'       => true,
        'default_rate' => '0',
        'class'        => 'Webkul\Shipping\Carriers\Free',
    ],

    'omniva' => [
        'code'         => 'omniva',
        'title'        => 'Omniva',
        'description'  => 'Omniva parcel locker',
        'active'       => true,
        'default_rate' => '2.99',
        'class'        => 'Webkul\Shipping\Carriers\Omniva',
    ],
];
