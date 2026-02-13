<?php

return [
    'components' => [
        'layouts' => [
            'header' => [
                'account-title' => 'Konto',
                'app-version'   => 'Versioon : :version',
                'logout'        => 'Logi välja',
                'my-account'    => 'Minu konto',
                'notifications' => 'Teavitused',
                'visit-shop'    => 'Külasta poodi',

                'mega-search' => [
                    'categories'                      => 'Kategooriad',
                    'customers'                       => 'Kliendid',
                    'explore-all-categories'          => 'Sirvi kõiki kategooriaid',
                    'explore-all-customers'           => 'Sirvi kõiki kliente',
                    'explore-all-matching-categories' => 'Sirvi kõiki kategooriaid, mis vastavad otsingule " :query" (:count)',
                    'explore-all-matching-customers'  => 'Sirvi kõiki kliente, kes vastavad otsingule " :query" (:count)',
                    'explore-all-matching-orders'     => 'Sirvi kõiki tellimusi, mis vastavad otsingule " :query" (:count)',
                    'explore-all-matching-products'   => 'Sirvi kõiki tooteid, mis vastavad otsingule " :query" (:count)',
                    'explore-all-orders'              => 'Sirvi kõiki tellimusi',
                    'explore-all-products'            => 'Sirvi kõiki tooteid',
                    'orders'                          => 'Tellimused',
                    'products'                        => 'Tooted',
                    'sku'                             => 'SKU: :sku',
                    'title'                           => 'Mega otsing',
                ],
            ],

            'sidebar' => [
                'attribute-families'       => 'Atribuudi perekonnad',
                'attributes'               => 'Atribuudid',
                'booking-product'          => 'Broneeringud',
                'campaigns'                => 'Kampaaniad',
                'catalog'                  => 'Kataloog',
                'categories'               => 'Kategooriad',
                'channels'                 => 'Kanalid',
                'cms'                      => 'CMS',
                'collapse'                 => 'Ahenda',
                'communications'           => 'Kommunikatsioon',
                'configure'                => 'Seadista',
                'currencies'               => 'Valuutad',
                'customers'                => 'Kliendid',
                'dashboard'                => 'Töölaud',
                'data-transfer'            => 'Andmete ülekanne',
                'discount'                 => 'Allahindlus',
                'email-templates'          => 'E-posti mallid',
                'events'                   => 'Sündmused',
                'exchange-rates'           => 'Vahetuskursid',
                'gdpr-data-requests'       => 'GDPR andmepäringud',
                'groups'                   => 'Grupid',
                'imports'                  => 'Importimine',
                'inventory-sources'        => 'Lao allikad',
                'invoices'                 => 'Arved',
                'locales'                  => 'Lokaalid',
                'marketing'                => 'Turundus',
                'mode'                     => 'Tume režiim',
                'newsletter-subscriptions' => 'Uudiskirja tellimused',
                'orders'                   => 'Tellimused',
                'products'                 => 'Tooted',
                'promotions'               => 'Promotsioonid',
                'refunds'                  => 'Hüvitised',
                'reporting'                => 'Aruandlus',
                'reviews'                  => 'Arvustused',
                'roles'                    => 'Rollid',
                'sales'                    => 'Müük',
                'search-seo'               => 'Otsing ja SEO',
                'search-synonyms'          => 'Otsingu sünonüümid',
                'search-terms'             => 'Otsingu terminid',
                'settings'                 => 'Seaded',
                'shipments'                => 'Saadetised',
                'sitemaps'                 => 'Saidikaardid',
                'tax-categories'           => 'Maksukategooriad',
                'tax-rates'                => 'Maksumäärad',
                'taxes'                    => 'Maksud',
                'themes'                   => 'Teemad',
                'transactions'             => 'Tehingud',
                'url-rewrites'             => 'URL ümberkirjutused',
                'users'                    => 'Kasutajad',
            ],

            'powered-by' => [
                'description' => 'Toetatud :bagisto, avatud lähtekoodiga projekt :webkul poolt.',
            ],
        ],

        'datagrid' => [
            'index' => [
                'no-records-selected'              => 'Kirjeid pole valitud.',
                'must-select-a-mass-action-option' => 'Te peate valima mass-tegevuse valiku.',
                'must-select-a-mass-action'        => 'Te peate valima mass-tegevuse.',
            ],

            'toolbar' => [
                'length-of' => ':length /',
                'of'        => '/',
                'per-page'  => 'Leheküljel',
                'results'   => ':total tulemust',
                'selected'  => ':total valitud',

                'mass-actions' => [
                    'submit'        => 'Kinnita',
                    'select-option' => 'Vali valik',
                    'select-action' => 'Vali tegevus',
                ],

                'filter' => [
                    'apply-filters-btn' => 'Rakenda filtrid',
                    'back-btn'          => 'Tagasi',
                    'create-new-filter' => 'Loo uus filter',
                    'custom-filters'    => 'Kohandatud filtrid',
                    'delete-error'      => 'Filtri kustutamisel tekkis viga, palun proovige uuesti.',
                    'delete-success'    => 'Filter on edukalt kustutatud.',
                    'empty-description' => 'Salvestamiseks pole valitud filtreid saadaval. Palun valige filtrid salvestamiseks.',
                    'empty-title'       => 'Lisa filtrid salvestamiseks',
                    'name'              => 'Nimi',
                    'quick-filters'     => 'Kii filtrid',
                    'save-btn'          => 'Salvesta',
                    'save-filter'       => 'Salvesta filter',
                    'saved-success'     => 'Filter on edukalt salvestatud.',
                    'selected-filters'  => 'Valitud filtrid',
                    'title'             => 'Filter',
                    'update'            => 'Uuenda',
                    'update-filter'     => 'Uuenda filterit',
                    'updated-success'   => 'Filter on edukalt uuendatud.',
                ],

                'search' => [
                    'title' => 'Otsi',
                ],
            ],

            'filters' => [
                'select' => 'Vali',
                'title'  => 'Filtrid',

                'dropdown' => [
                    'searchable' => [
                        'atleast-two-chars' => 'Sisestage vähemalt 2 tähemärki...',
                        'no-results'        => 'Tulemusi ei leitud...',
                    ],
                ],

                'custom-filters' => [
                    'clear-all' => 'Tühjenda kõik',
                    'title'     => 'Kohandatud filtrid',
                ],

                'boolean-options' => [
                    'false' => 'Väär',
                    'true'  => 'Tõene',
                ],

                'date-options' => [
                    'last-month'        => 'Eelmine kuu',
                    'last-six-months'   => 'Viimased 6 kuud',
                    'last-three-months' => 'Viimased 3 kuud',
                    'this-month'        => 'See kuu',
                    'this-week'         => 'See nädal',
                    'this-year'         => 'See aasta',
                    'today'             => 'Täna',
                    'yesterday'         => 'Eile',
                ],
            ],

            'table' => [
                'actions'              => 'Tegevused',
                'no-records-available' => 'Kirjeid pole saadaval.',
            ],
        ],

        'modal' => [
            'confirm' => [
                'agree-btn'    => 'Nõustu',
                'disagree-btn' => 'Ei nõustu',
                'message'      => 'Olete kindel, et soovite seda tegevust sooritada?',
                'title'        => 'Olete kindel?',
            ],
        ],

        'products' => [
            'search' => [
                'add-btn'       => 'Lisa valitud toode',
                'empty-info'    => 'Otsinguterminile tooteid pole saadaval.',
                'empty-title'   => 'Tooteid ei leitud',
                'product-image' => 'Toote pilt',
                'qty'           => ':qty saadaval',
                'sku'           => 'SKU - :sku',
                'title'         => 'Vali tooted',
            ],
        ],

        'media' => [
            'images' => [
                'add-image-btn'     => 'Lisa pilt',
                'ai-add-image-btn'  => 'Magic AI',
                'ai-btn-info'       => 'Genereeri pilt',
                'allowed-types'     => 'png, jpeg, jpg',
                'not-allowed-error' => 'Lubatud on ainult pildifailid (.jpeg, .jpg, .png, ..).',

                'ai-generation' => [
                    '1024x1024'        => '1024x1024',
                    '1024x1792'        => '1024x1792',
                    '1792x1024'        => '1792x1024',
                    'apply'            => 'Rakenda',
                    'dall-e-2'         => 'Dall.E 2',
                    'dall-e-3'         => 'Dall.E 3',
                    'generate'         => 'Genereeri',
                    'generating'       => 'Genereerimine...',
                    'hd'               => 'HD',
                    'model'            => 'Mudel',
                    'number-of-images' => 'Piltide arv',
                    'prompt'           => 'Viide',
                    'quality'          => 'Kvaliteet',
                    'regenerate'       => 'Genereeri uuesti',
                    'regenerating'     => 'Uuesti genereerimine...',
                    'size'             => 'Suurus',
                    'standard'         => 'Standard',
                    'title'            => 'AI pildi genereerimine',
                ],

                'placeholders' => [
                    'front'     => 'Esimene',
                    'next'      => 'Järgmine',
                    'size'      => 'Suurus',
                    'use-cases' => 'Kasutusjuhud',
                    'zoom'      => 'Suurendus',
                ],
            ],

            'videos' => [
                'add-video-btn'     => 'Lisa video',
                'allowed-types'     => 'mp4, webm, mkv',
                'not-allowed-error' => 'Lubatud on ainult videofailid (.mp4, .mov, .ogg ..).',
            ],
        ],

        'tinymce' => [
            'ai-btn-tile' => 'Magic AI',

            'ai-generation' => [
                'apply'                    => 'Rakenda',
                'deepseek-r1-8b'           => 'DeepSeek R1 (8b)',
                'enabled'                  => 'Lubatud',
                'gemini-2-0-flash'         => 'Gemini 2.0 Flash',
                'generate'                 => 'Genereeri',
                'generated-content'        => 'Genereeritud sisu',
                'generated-content-info'   => 'AI-generateeritud sisu võib olla eksitav. Vaadake genereeritud sisu üle enne rakendamist.',
                'generating'               => 'Genereerimine...',
                'gpt-4-turbo'              => 'OpenAI gpt-4 Turbo',
                'gpt-4o'                   => 'OpenAI gpt-4o',
                'gpt-4o-mini'              => 'OpenAI gpt-4o Mini',
                'llama-groq'               => 'Llama 3.3 (Groq)',
                'llama3-1-8b'              => '(Ollama) Llama 3.1 (8B)',
                'llama3-2-1b'              => '(Ollama) Llama 3.2 (1B)',
                'llama3-2-3b'              => '(Ollama) Llama 3.2 (3B)',
                'llama3-8b'                => '(Ollama) Llama 3 (8B)',
                'llava-7b'                 => 'Llava (7b)',
                'mistral-7b'               => 'Mistral (7b)',
                'model'                    => 'Mudel',
                'orca-mini'                => 'Orca Mini',
                'phi3-5'                   => 'Phi 3.5',
                'prompt'                   => 'Viide',
                'qwen2-5-0-5b'             => 'Qwen 2.5 (0.5b)',
                'qwen2-5-1-5b'             => 'Qwen 2.5 (1.5b)',
                'qwen2-5-14b'              => 'Qwen 2.5 (14b)',
                'qwen2-5-3b'               => 'Qwen 2.5 (3b)',
                'qwen2-5-7b'               => 'Qwen 2.5 (7b)',
                'starling-lm-7b'           => 'Starling-lm (7b)',
                'title'                    => 'AI abi',
                'vicuna-13b'               => 'Vicuna (13b)',
                'vicuna-7b'                => 'Vicuna (7b)',
            ],

            'errors' => [
                'file-extension-mismatch'        => 'Faili laiend ei vasta faili tüübile.',
                'file-upload-failed'             => 'Faili üleslaadimine ebaõnnestus.',
                'http-error'                     => 'HTTP viga.',
                'invalid-file-type'              => 'Vale faili tüüp. Lubatud tüübid: JPEG, PNG, GIF, WebP, SVG',
                'invalid-json'                   => 'Vale JSON.',
                'no-file-uploaded'               => 'Faili ei laaditud üles.',
                'upload-failed'                  => 'Pildi üleslaadimine ebaõnnestus XHR Transport vea tõttu.',
            ],
        ],
    ],
];
