<?php

return [
    'errors' => [
        'dashboard' => 'Töölaud',
        'go-back'   => 'Mine tagasi',
        'support'   => 'Kui probleem püsib, võtke meiega ühendust aadressil <a href=":link" class=":class">:email</a> abi saamiseks.',

        '404' => [
            'description' => 'Ups! Leht, mida otsite, on puhkusele läinud. Tundub, et me ei leidnud seda, mida te otsisite.',
            'title'       => '404 Lehte ei leitud',
        ],

        '401' => [
            'description' => 'Ups! Tundub, et teil pole luba seda lehte vaadata. Tundub, et teil puuduvad vajalikud volitused.',
            'title'       => '401 Autoriseerimata',
        ],

        '403' => [
            'description' => 'Ups! See leht on keelatud tsoon. Tundub, et teil pole vajalikke õigusi sisu vaatamiseks.',
            'title'       => '403 Keelatud',
        ],

        '500' => [
            'description' => 'Ups! Midagi läks valesti. Tundub, et meil on probleeme teie otsitava lehe laadimisega.',
            'title'       => '500 Sisemine serveri viga',
        ],

        '503' => [
            'description' => 'Ups! Tundub, et oleme ajutiselt hoolduseks maas. Palun kontrollige hiljem uuesti.',
            'title'       => '503 Teenus pole saadaval',
        ],
    ],
];
