<?php

return [
    'configuration' => [
        'index' => [
            'back-btn'                     => 'Tagasi',
            'delete'                       => 'Kustuta',
            'enable-at-least-one-payment'  => 'Luba vähemalt üks makseviis.',
            'enable-at-least-one-shipping' => 'Luba vähemalt üks transportviis.',
            'no-result-found'              => 'Tulemusi ei leitud',
            'save-btn'                     => 'Salvesta seadistus',
            'save-message'                 => 'Seadistus salvestatud edukalt',
            'search'                       => 'Otsi',
            'select-country'               => 'Vali riik',
            'select-state'                 => 'Vali osariik',
            'title'                        => 'Seadistus',

            'general' => [
                'info'  => 'Määra ühikute valikud.',
                'title' => 'Üldine',

                'general' => [
                    'info'  => 'Seadista ühikute seaded ja lülita leivakroomid ja külastaja valikud sisse või välja.',
                    'title' => 'Üldine',

                    'unit-options' => [
                        'info'        => 'Määra ühikute valikud.',
                        'title'       => 'Ühiku valikud',
                        'title-info'  => 'Seadista kaal naeltes (lbs) või kilogrammides (kgs).',
                        'weight-unit' => 'Kaalühik',
                    ],

                    'breadcrumbs' => [
                        'shop'       => 'Poe leivakroomid',
                        'title'      => 'Leivakroomid',
                        'title-info' => 'Luba või keela leivakroomide navigatsioon poes.',
                    ],

                    'visitor-options' => [
                        'enable'     => 'Luba külastaja valikud',
                        'title'      => 'Külastaja valikud',
                        'title-info' => 'Võimaldab kontrollida saidi külastuste ja külastajate aktiivsuse jälgimist ning seotud tegevuste monitooringut.',
                    ],
                ],

                'content' => [
                    'info'  => 'Määra päise pakkumise pealkiri ja kohandatud skriptid.',
                    'title' => 'Sisu',

                    'header-offer' => [
                        'title'             => 'Päise pakkumise pealkiri',
                        'title-info'        => 'Seadista päise pakkumise pealkiri koos pakkumise pealkirja, ümbersuunamise pealkirja ja ümbersuunamise lingiga.',
                        'offer-title'       => 'Pakkumise pealkiri',
                        'redirection-title' => 'Ümbersuunamise pealkiri',
                        'redirection-link'  => 'Ümbersuunamise link',
                    ],

                    'speculation-rules' => [
                        'enable-speculation' => 'Luba spekulatsiooni reeglid',
                        'info'               => 'Seadista automaatse spekulatsiooni loogika lubamise või keelamise seaded.',
                        'title'              => 'Spekulatsiooni reeglid',

                        'prerender' => [
                            'conservative'           => 'Konservatiivne',
                            'eager'                  => 'Kiire',
                            'eagerness'              => 'Eelrenderdamise kiiruse tase',
                            'eagerness-info'         => 'Kontrollib, kui agressiivselt spekulatsiooni reegleid rakendatakse. Valikud: kiire (maks), mõõdukas (vaikimisi), konservatiivne (madal).',
                            'enabled'                => 'Luba eelrenderdamise spekulatsiooni reeglid',
                            'ignore-url-params'      => 'Eira eelrenderdamise URL parameetreid',
                            'ignore-url-params-info' => 'Määra URL parameetrid, mida spekulatsiooni reegletes eirata. Kasuta toru (|) mitmete parameetrite eraldamiseks.',
                            'ignore-urls'            => 'Eira eelrenderdamise URL-sid',
                            'ignore-urls-info'       => 'Sisesta URL-id, mida spekulatsiooni loogikast välja jätta. Eralda mitu URL-i toru (|) sümboliga.',
                            'info'                   => 'Määra spekulatsiooni reeglite olek.',
                            'moderate'               => 'Mõõdukas',
                        ],

                        'prefetch' => [
                            'conservative'           => 'Konservatiivne',
                            'eager'                  => 'Kiire',
                            'eagerness'              => 'Eeltõmbamise kiiruse tase',
                            'eagerness-info'         => 'Kontrollib, kui agressiivselt spekulatsiooni reegleid rakendatakse. Valikud: kiire (maks), mõõdukas (vaikimisi), konservatiivne (madal).',
                            'enabled'                => 'Luba eeltõmbamise spekulatsiooni reeglid',
                            'ignore-url-params'      => 'Eira eeltõmbamise URL parameetreid',
                            'ignore-url-params-info' => 'Määra URL parameetrid, mida spekulatsiooni reegletes eirata. Kasuta toru (|) mitmete parameetrite eraldamiseks.',
                            'ignore-urls'            => 'Eira eeltõmbamise URL-sid',
                            'ignore-urls-info'       => 'Sisesta URL-id, mida spekulatsiooni loogikast välja jätta. Eralda mitu URL-i toru (|) sümboliga.',
                            'info'                   => 'Määra spekulatsiooni reeglite olek.',
                            'moderate'               => 'Mõõdukas',
                        ],
                    ],

                    'custom-scripts' => [
                        'custom-css'        => 'Kohandatud CSS',
                        'custom-javascript' => 'Kohandatud Javascript',
                        'title'             => 'Kohandatud skriptid',
                        'title-info'        => 'Kohandatud skriptid on personaalsed koodijupid, mis on loodud tarkvarale spetsiifiliste funktsioonide või võimaluste lisamiseks, parandades selle võimekusi unikaalselt.',
                    ],
                ],

                'design' => [
                    'info'  => 'Määra logo ja favicon ikoon admin paneelile.',
                    'title' => 'Disain',

                    'admin-logo' => [
                        'favicon'    => 'Favicon',
                        'logo-image' => 'Logo pilt',
                        'title'      => 'Admin logo',
                        'title-info' => 'Seadista logo ja favicon pildid oma veebisaidi esiküljele parema brändingu ja äratuntavuse jaoks.',
                    ],

                    'menu-category' => [
                        'default'         => 'Vaikimisi menüü',
                        'info'            => 'See seade kontrollib kategooriate nähtavust päisemenüüs. Saate valida, kas näidata ainult ülemkategooriaid või kõiki pesastatud kategooriaid.',
                        'preview-default' => 'Eelvaade vaikimisi menüüst',
                        'preview-sidebar' => 'Eelvaade külgmenüüst',
                        'sidebar'         => 'Külgmenüü',
                        'title'           => 'Menüü kategooriate vaade',
                    ],
                ],

                'magic-ai' => [
                    'info'  => 'Määra Magic AI valikud ja luba mõningad valikud sisu loomise automatiseerimiseks.',
                    'title' => 'Magic AI',

                    'settings' => [
                        'api-key'        => 'API võti',
                        'enabled'        => 'Lubatud',
                        'llm-api-domain' => 'LLM API domeen',
                        'organization'   => 'Organisatsioon',
                        'title'          => 'Üldised seaded',
                        'title-info'     => 'Parandage oma kogemust Magic AI funktsiooniga, sisestades oma eksklusiivse API võtme ja näidates vastavat organisatsiooni lihtsaks integreerimiseks. Võtke kontroll oma OpenAI andmete üle ja kohandage seadeid vastavalt oma spetsiifilistele vajadustele.',
                    ],

                    'content-generation' => [
                        'category-description-prompt'      => 'Kategooria kirjelduse viide',
                        'cms-page-content-prompt'          => 'CMS lehe sisu viide',
                        'enabled'                          => 'Lubatud',
                        'product-description-prompt'       => 'Toote kirjelduse viide',
                        'product-short-description-prompt' => 'Toote lühikirjelduse viide',
                        'title'                            => 'Sisu genereerimine',
                        'title-info'                       => 'See funktsioon lubab Magic AI iga WYSIWYG redaktori jaoks, kus soovite hallata sisu AI abil.<br/><br/>Kui lubatud, mine igale redaktorile sisu genereerimiseks.',
                    ],

                    'image-generation' => [
                        'enabled'    => 'Lubatud',
                        'title'      => 'Pildi genereerimine',
                        'title-info' => 'See funktsioon lubab Magic AI iga pildi üleslaadimise jaoks, kus soovite genereerida pilte DALL-E abil.<br/><br/>Kui lubatud, mine igale pildi üleslaadimisele pildi genereerimiseks.',
                    ],

                    'review-translation' => [
                        'deepseek-r1-8b'      => 'DeepSeek R1 (8b)',
                        'enabled'             => 'Lubatud',
                        'gemini-2-0-flash'    => 'Gemini 2.0 Flash',
                        'gpt-4-turbo'         => 'OpenAI gpt-4 Turbo',
                        'gpt-4o'              => 'OpenAI gpt-4o',
                        'gpt-4o-mini'         => 'OpenAI gpt-4o mini',
                        'llama-groq'          => 'Llama 3.3 (Groq)',
                        'llama3-1-8b'         => 'Llama 3.1 (8B)',
                        'llama3-2-1b'         => 'Llama 3.2 (1B)',
                        'llama3-2-3b'         => 'Llama 3.2 (3B)',
                        'llama3-8b'           => 'Llama 3 (8B)',
                        'llava-7b'            => 'Llava (7b)',
                        'mistral-7b'          => 'Mistral (7b)',
                        'model'               => 'Mudel',
                        'orca-mini'           => 'Orca Mini',
                        'phi3-5'              => 'Phi 3.5',
                        'qwen2-5-0-5b'        => 'Qwen 2.5 (0.5b)',
                        'qwen2-5-1-5b'        => 'Qwen 2.5 (1.5b)',
                        'qwen2-5-14b'         => 'Qwen 2.5 (14b)',
                        'qwen2-5-3b'          => 'Qwen 2.5 (3b)',
                        'qwen2-5-7b'          => 'Qwen 2.5 (7b)',
                        'starling-lm-7b'      => 'Starling-lm (7b)',
                        'title'               => 'Arvustuse tõlge',
                        'title-info'          => 'Pakuda kliendile või külastajale võimalust tõlkida kliendi arvustus inglise keelde.<br/><br/>Kui lubatud, mine arvustuse juurde ja leiad nupu 'Tõlgi inglise keelde', kui arvustus on muus keeles kui inglise.',
                        'vicuna-13b'          => 'Vicuna (13b)',
                        'vicuna-7b'           => 'Vicuna (7b)',
                    ],

                    'checkout-message' => [
                        'deepseek-r1-8b'      => 'DeepSeek R1 (8b)',
                        'enabled'             => 'Lubatud',
                        'gemini-2-0-flash'    => 'Gemini 2.0 Flash',
                        'gpt-4-turbo'         => 'OpenAI gpt-4 Turbo',
                        'gpt-4o'              => 'OpenAI gpt-4o',
                        'gpt-4o-mini'         => 'OpenAI gpt-4o mini',
                        'llama-groq'          => 'Llama 3.3 (Groq)',
                        'llama3-1-8b'         => 'Llama 3.1 (8B)',
                        'llama3-2-1b'         => 'Llama 3.2 (1B)',
                        'llama3-2-3b'         => 'Llama 3.2 (3B)',
                        'llama3-8b'           => 'Llama 3 (8B)',
                        'llava-7b'            => 'Llava (7b)',
                        'mistral-7b'          => 'Mistral (7b)',
                        'model'               => 'Mudel',
                        'orca-mini'           => 'Orca Mini',
                        'phi3-5'              => 'Phi 3.5',
                        'prompt'              => 'Viide',
                        'qwen2-5-0-5b'        => 'Qwen 2.5 (0.5b)',
                        'qwen2-5-1-5b'        => 'Qwen 2.5 (1.5b)',
                        'qwen2-5-14b'         => 'Qwen 2.5 (14b)',
                        'qwen2-5-3b'          => 'Qwen 2.5 (3b)',
                        'qwen2-5-7b'          => 'Qwen 2.5 (7b)',
                        'starling-lm-7b'      => 'Starling-lm (7b)',
                        'title'               => 'Personaalsustatud kassasõnum',
                        'title-info'          => 'Koosta personaalsustatud kassasõnum klientidele tänulehel, kohandades sisu vastavalt individuaalsete eelistustega ja parandades üldist ostujärgset kogemust.',
                        'vicuna'              => 'Vicuna',
                        'vicuna-13b'          => 'Vicuna (13b)',
                        'vicuna-7b'           => 'Vicuna (7b)',
                    ],
                ],

                'gdpr' => [
                    'title' => 'GDPR',
                    'info'  => 'GDPR ühilduvuse seaded',

                    'settings' => [
                        'title'   => 'GDPR ühilduvuse seaded',
                        'info'    => 'Halda GDPR ühilduvuse seadeid, sealhulgas andmete privaatsuse lepet. Luba või keela GDPR funktsioonid vajaduse järgi',
                        'enabled' => 'Luba GDPR',
                    ],

                    'agreement' => [
                        'title'          => 'GDPR lepe',
                        'info'           => 'Halda kliendi nõusolekut GDPR regulatsioonide all. Luba lepe nõuded andmete kogumiseks ja töötlemiseks.',
                        'enable'         => 'Luba kliendi lepe',
                        'checkbox-label' => 'Lepemärkeruudu silt',
                        'content'        => 'Lepesisu',
                    ],

                    'cookie' => [
                        'bottom-left'  => 'All vasakul',
                        'bottom-right' => 'All paremal',
                        'center'       => 'Keskel',
                        'description'  => 'Kirjeldus',
                        'enable'       => 'Luba küpsise teade',
                        'identifier'   => 'Staatilise ploki identifikaator',
                        'info'         => 'Seadista küpsiste nõusoleku seaded, et teavitada kasutajaid andmete kogumisest ja tagada privaatsusregulatsioonidele vastavus.',
                        'position'     => 'Küpsiseploki kuvamise asukoht',
                        'title'        => 'Küpsise teate seaded',
                        'top-left'     => 'Ülal vasakul',
                        'top-right'    => 'Ülal paremal',
                    ],

                    'cookie-consent' => [
                        'title'                  => 'Halda oma küpsise eelistusi',
                        'info'                   => 'Kontrolli, kuidas sinu andmeid kasutatakse, valides oma eelistatud küpsise seadeid. Kohanda lubasid erinevatele küpsist tüüpidele.',
                        'strictly-necessary'     => 'Range vajalikud',
                        'basic-interaction'      => 'Põhiline interaktsioon ja funktsionaalsus',
                        'experience-enhancement' => 'Kogemuse parandamised',
                        'measurement'            => 'Mõõtmised',
                        'targeting-advertising'  => 'Sihtimine ja reklaamimine',
                    ],
                ],

                'sitemap' => [
                    'info'  => 'Määra saidikaardi valikud.',
                    'title' => 'Saidikaart',

                    'settings' => [
                        'enabled' => 'Lubatud',
                        'info'    => 'Luba või keela saidikaart oma veebisaidil otsingumootori optimeerimise parandamiseks ja kasutajakogemuse täiustamiseks.',
                        'title'   => 'Seaded',
                    ],

                    'file-limits' => [
                        'info'             => 'Määra faili limiidid.',
                        'max-file-size'    => 'Maksimaalne faili suurus',
                        'max-url-per-file' => 'Maksimaalne URL-ide arv faili kohta',
                        'title'            => 'Faili limiidid',
                    ],
                ],
            ],

            'catalog' => [
                'info'  => 'Kataloog',
                'title' => 'Kataloog',

                'products' => [
                    'info'  => 'Toote vaate leht, ostukorvi vaate leht, poe esikülg, arvustus ja atribuudi sotsiaalne jagamine.',
                    'title' => 'Tooted',

                    'settings' => [
                        'compare-options'     => 'Võrdlemise valikud',
                        'image-search-option' => 'Pildi otsingu valik',
                        'title'               => 'Seaded',
                        'title-info'          => 'Seaded on seadistatavad valikud, mis kontrollivad, kuidas süsteem, rakendus või seade käitub, kohandatud kasutaja eelistuste ja nõuete järgi.',
                        'wishlist-options'    => 'Soovinimekirja valikud',
                    ],

                    'search' => [
                        'admin-mode'            => 'Admini otsingu režiim',
                        'admin-mode-info'       => 'Mega otsing, andmete ruudustik ja teised otsingu funktsioonid admin paneelis põhinevad valitud otsingumootoril.',
                        'database'              => 'Andmebaas',
                        'elastic'               => 'Elastic Search',
                        'max-query-length'      => 'Maksimaalne päringu pikkus',
                        'max-query-length-info' => 'Määra maksimaalne päringu pikkus otsingupäringute jaoks.',
                        'min-query-length'      => 'Minimaalne päringu pikkus',
                        'min-query-length-info' => 'Määra minimaalne päringu pikkus otsingupäringute jaoks.',
                        'search-engine'         => 'Otsingumootor',
                        'storefront-mode'       => 'Poe otsingu režiim',
                        'storefront-mode-info'  => 'Otsingu funktsioon poes põhineb valitud otsingumootoril, sealhulgas kategooria leht, otsingu leht ja teised otsingu funktsioonid.',
                        'title'                 => 'Otsing',
                        'title-info'            => 'Otsingumootori seadistamiseks toote otsingute jaoks saate valida andmebaasi ja Elasticsearchi vahel vastavalt oma vajadustele. Kui teil on palju tooteid, on Elasticsearch soovitatav.',
                    ],

                    'guest-checkout' => [
                        'allow-guest-checkout'      => 'Luba külaliskassa',
                        'allow-guest-checkout-hint' => 'Vihje: Kui sisse lülitatud, saab seda valikut konfigureerida iga toote jaoks eraldi.',
                        'title'                     => 'Külaliskassa',
                        'title-info'                => 'Külaliskassa võimaldab klientidel osta tooteid kontot loomata, lihtsustades ostuprotsessi mugavuse ja kiiremate tehingute jaoks.',
                    ],

                    'product-view-page' => [
                        'allow-no-of-related-products'  => 'Lubatud seotud toodete arv',
                        'allow-no-of-up-sells-products' => 'Lubatud ülemüügi toodete arv',
                        'title'                         => 'Toote vaate lehe konfiguratsioon',
                        'title-info'                    => 'Toote vaate lehe konfiguratsioon hõlmab paigutuse ja elementide kohandamist toote kuvamise lehel, parandades kasutajakogemust ja informatsiooni esitamist.',
                    ],

                    'cart-view-page' => [
                        'allow-no-of-cross-sells-products' => 'Lubatud ristmüügi toodete arv',
                        'title'                            => 'Ostukorvi vaate lehe konfiguratsioon',
                        'title-info'                       => 'Ostukorvi vaate lehe konfiguratsioon hõlmab esemete, detaile ja valikute korraldamist ostukorvi lehel, parandades kasutaja interaktsiooni ja ostuvoolu.',
                    ],

                    'storefront' => [
                        'buy-now-button-display' => 'Luba klientidel osta tooteid otse',
                        'cheapest-first'         => 'Odavaimad esimeseks',
                        'comma-separated'        => 'Komadega eraldatud',
                        'default-list-mode'      => 'Vaikimisi loendi režiim',
                        'expensive-first'        => 'Kallimad esimeseks',
                        'from-a-z'               => 'A-st Z-ni',
                        'from-z-a'               => 'Z-st A-ni',
                        'grid'                   => 'Ruudustik',
                        'latest-first'           => 'Uuemad esimeseks',
                        'list'                   => 'Loend',
                        'oldest-first'           => 'Vanemad esimeseks',
                        'products-per-page'      => 'Tooteid leheküljel',
                        'sort-by'                => 'Sorteeri',
                        'title'                  => 'Poe esikülg',
                        'title-info'             => 'Poe esikülg on kliendipoolne liides veebipoes, mis näitab tooteid, kategooriaid ja navigatsiooni sujuva ostukogemuse jaoks.',
                    ],

                    'small-image' => [
                        'height'      => 'Kõrgus',
                        'placeholder' => 'Väikese pildi asendaja',
                        'title'       => 'Väike pilt',
                        'title-info'  => 'Poe esikülg on kliendipoolne liides veebipoes, mis näitab tooteid, kategooriaid ja navigatsiooni sujuva ostukogemuse jaoks.',
                        'width'       => 'Laius',
                    ],

                    'medium-image' => [
                        'height'      => 'Kõrgus',
                        'placeholder' => 'Keskmine pildi asendaja',
                        'title'       => 'Keskmine pilt',
                        'title-info'  => 'Keskmine pilt on mõõdukas suurusega pilt, mis pakub tasakaalu detailide ja ekraaniruumi vahel, mida kasutatakse tavaliselt visuaalide jaoks.',
                        'width'       => 'Laius',
                    ],

                    'large-image' => [
                        'height'      => 'Kõrgus',
                        'placeholder' => 'Suure pildi asendaja',
                        'title'       => 'Suur pilt',
                        'title-info'  => 'Suur pilt on kõrge resolutsiooniga pilt, mis pakub paremaid detaile ja visuaalset mõju, mida kasutatakse tavaliselt toodete või graafikute esitamiseks.',
                        'width'       => 'Laius',
                    ],

                    'review' => [
                        'allow-customer-review'   => 'Luba kliendi arvustus',
                        'allow-guest-review'      => 'Luba külalise arvustus',
                        'censoring-reviewer-name' => 'Arvustaja nime tsenseerimine',
                        'display-review-count'    => 'Kuva arvustuste arv hinnangute jaoks.',
                        'display-star-count'      => 'Kuva tähtede arv hinnangutes.',
                        'summary'                 => 'Kokkuvõte',
                        'title'                   => 'Arvustus',
                        'title-info'              => 'Midagi hindamine või hindamine, mis sageli hõlmab arvamusi ja tagasisidet.',
                    ],

                    'attribute' => [
                        'file-upload-size'  => 'Lubatud faili üleslaadimise suurus (Kb)',
                        'image-upload-size' => 'Lubatud pildi üleslaadimise suurus (Kb)',
                        'title'             => 'Atribuut',
                        'title-info'        => 'Omadus või omadus, mis määratleb objekti, mõjutades selle käitumist, välimust või funktsiooni.',
                    ],

                    'social-share' => [
                        'title-info'                  => 'Seadista sotsiaalse jagamise seaded, et lubada toodete jagamist Instagramis, Twitteris, WhatsAppis, Facebookis, Pinterestis, LinkedInis ja e-posti teel.',
                        'title'                       => 'Sotsiaalne jagamine',
                        'share-message'               => 'Jaga sõnumit',
                        'share'                       => 'Jaga',
                        'enable-social-share'         => 'Luba sotsiaalne jagamine?',
                        'enable-share-whatsapp-info'  => 'WhatsApp jagamise link ilmub ainult mobiilseadmetele.',
                        'enable-share-whatsapp'       => 'Luba jagamine WhatsAppis?',
                        'enable-share-twitter'        => 'Luba jagamine Twitteris?',
                        'enable-share-pinterest'      => 'Luba jagamine Pinterestis?',
                        'enable-share-linkedin'       => 'Luba jagamine LinkedInis?',
                        'enable-share-facebook'       => 'Luba jagamine Facebookis?',
                        'enable-share-email'          => 'Luba jagamine e-postiga?',
                    ],
                ],

                'rich-snippets' => [
                    'info'  => 'Määra tooted ja kategooriad.',
                    'title' => 'Rikastatud väljavõtted',

                    'products' => [
                        'enable'          => 'Luba',
                        'show-categories' => 'Näita kategooriaid',
                        'show-images'     => 'Näita pilte',
                        'show-offers'     => 'Näita pakkumisi',
                        'show-ratings'    => 'Näita hinnanguid',
                        'show-reviews'    => 'Näita arvustusi',
                        'show-sku'        => 'Näita SKU-d',
                        'show-weight'     => 'Näita kaalu',
                        'title'           => 'Tooted',
                        'title-info'      => 'Seadista toote seaded, sealhulgas SKU, kaal, kategooriad, pildid, arvustused, hinnangud, pakkumised jne.',
                    ],

                    'categories' => [
                        'enable'                  => 'Luba',
                        'show-search-input-field' => 'Näita otsingu sisendvälja',
                        'title'                   => 'Kategooriad',
                        'title-info'              => '"Kategooriad" on grupid või klassifikatsioonid, mis aitavad organiseerida ja grupeerida sarnaseid tooteid või esemeid kohti lihtsama sirvimise ja navigeerimise jaoks.',
                    ],
                ],

                'inventory' => [
                    'title'      => 'Ladu',
                    'title-info' => 'Seadista lao seaded, et lubada tagasitellimused ja määrata lõpule müüdud lävi.',

                    'product-stock-options' => [
                        'allow-back-orders'       => 'Luba tagasitellimused',
                        'max-qty-allowed-in-cart' => 'Maksimaalne kogus ostukorvis lubatud',
                        'min-qty-allowed-in-cart' => 'Minimaalne kogus ostukorvis lubatud',
                        'out-of-stock-threshold'  => 'Lõpule müüdud lävi',
                        'title'                   => 'Toote lao valik',
                        'info'                    => 'Seadista toote lao valikud, et lubada tagasitellimused, määrata minimaalsed ja maksimaalsed ostukorvi kogused ja määrata lõpule müüdud lävid.',
                    ],
                ],
            ],

            'customer' => [
                'info'  => 'Klient',
                'title' => 'Klient',

                'address' => [
                    'info'  => 'Määra riik, osariik, sihtnumber ja read tänavaaadressis.',
                    'title' => 'Aadress',

                    'requirements' => [
                        'city'       => 'Linn',
                        'country'    => 'Riik',
                        'state'      => 'Osariik',
                        'title'      => 'Nõuded',
                        'title-info' => 'Nõuded on tingimused, funktsioonid või spetsifikatsioonid, mis on vajalikud midagi täitmiseks, saavutamiseks või täitmiseks edukalt.',
                        'zip'        => 'Sihtnumber',
                    ],

                    'information' => [
                        'street-lines' => 'Read tänavaaadressis',
                        'title'        => 'Informatsioon',
                        'title-info'   => '"Read tänavaaadressis" viitavad aadressi individuaalsetele segmentidele, mis on sageli eraldatud komadega, pakkudes asukoha informatsiooni nagu maja number, tänav, linn ja muud.',
                    ],
                ],

                'captcha' => [
                    'info'  => 'Määra saidi võti, salajane võti ja olek.',
                    'title' => 'Google Captcha',

                    'credentials' => [
                        'secret-key' => 'Salajane võti',
                        'site-key'   => 'Saidi võti',
                        'status'     => 'Olek',
                        'title'      => 'Kredentsiaalid',
                        'title-info' => '"Saidikaart: Veebisaidi paigutuse kaart otsingumootoritele. Salajane võti: Turvaline kood andmete krüpteerimiseks, autentimiseks või API ligipääsu kaitseks."',
                    ],

                    'validations' => [
                        'captcha'  => 'Midagi läks valesti! Palun proovige uuesti.',
                        'required' => 'Palun valige CAPTCHA',
                    ],
                ],

                'settings' => [
                    'settings-info' => 'Määra soovinimekiri, sisselogimise ümbersuunamine, uudiskirja tellimused, vaikimisi grupi valik, e-posti verifitseerimised ja sotsiaalne sisselogimine.',
                    'title'         => 'Seaded',

                    'login-as-customer' => [
                        'allow-option' => 'Luba sisselogimine kliendina',
                        'title'        => 'Logi sisse kliendina',
                        'title-info'   => 'Luba "Logi sisse kliendina" funktsionaalsus.',
                    ],

                    'wishlist' => [
                        'allow-option' => 'Luba soovinimekirja valik',
                        'title'        => 'Soovinimekiri',
                        'title-info'   => 'Luba või keela soovinimekirja valik.',
                    ],

                    'login-options' => [
                        'account'          => 'Konto',
                        'home'             => 'Kodu',
                        'redirect-to-page' => 'Suuna klient valitud lehele',
                        'title'            => 'Sisselogimise valikud',
                        'title-info'       => 'Seadista sisselogimise valikud, et määrata ümbersuunamise leht klientidele pärast sisselogimist.',
                    ],

                    'create-new-account-option' => [
                        'news-letter'      => 'Luba uudiskiri',
                        'news-letter-info' => 'Luba uudiskirja tellimise valik registreerumise lehel.',
                        'title'            => 'Loo uue konto valikud',
                        'title-info'       => 'Määra valikud uutele kontodele, sealhulgas vaikimisi kliendigrupi määramine ja uudiskirja tellimise valiku lubamine registreerumise ajal.',

                        'default-group' => [
                            'general'    => 'Üldine',
                            'guest'      => 'Külaline',
                            'title'      => 'Vaikimisi grupp',
                            'title-info' => 'Määra spetsiifiline kliendigrupp uutele klientidele vaikimisi.',
                            'wholesale'  => 'Hulgi',
                        ],
                    ],

                    'newsletter' => [
                        'subscription' => 'Luba uudiskirja tellimine',
                        'title'        => 'Uudiskirja tellimus',
                        'title-info'   => '"Uudiskirja informatsioon" sisaldab uuendusi, pakkumisi või sisu, mida jagatakse regulaarselt e-postiteel tellijatele, hoides nad informeerituna ja kaasatud.',
                    ],

                    'email' => [
                        'email-verification' => 'Luba e-posti verifitseerimine',
                        'title'              => 'E-posti verifitseerimine',
                        'title-info'         => '"E-posti verifitseerimine" kinnitab e-posti aadressi autentsust, sageli saates kinnituse lingi, parandades konto turvalisust ja kommunikatsiooni usaldusväärsust.',
                    ],

                    'social-login' => [
                        'title' => 'Sotsiaalne sisselogimine',
                        'info'  => '"Sotsiaalne sisselogimine" võimaldab kasutajatel ligi pääseda veebisaidile oma sotsiaalmeedia kontodega, lihtsustades registreerumist ja sisselogimise protsesse.',

                        'google' => [
                            'enable-google' => 'Luba Google',

                            'client-id' => [
                                'title'      => 'Kliendi ID',
                                'title-info' => 'Unikaalne identifikaator, mille Google pakub, kui loote oma OAuth rakendust.',
                            ],

                            'client-secret' => [
                                'title'      => 'Kliendi salajane võti',
                                'title-info' => 'Salajane võti, mis on seotud sinu Google OAuth kliendiga. Hoia see konfidentsiaalselt.',
                            ],

                            'redirect' => [
                                'title'      => 'Ümbersuunamise URL',
                                'title-info' => 'Tagasikutsumise URL, kuhu kasutajad suunatakse pärast Google'iga autentimist. Peab vastama URL-ile, mis on konfigureeritud sinu Google konsoolis.',
                            ],
                        ],

                        'facebook' => [
                            'enable-facebook' => 'Luba Facebook',

                            'client-id' => [
                                'title'      => 'Kliendi ID',
                                'title-info' => 'Rakenduse ID, mille Facebook pakub, kui loote rakenduse Facebooki arendaja konsoolis.',
                            ],

                            'client-secret' => [
                                'title'      => 'Kliendi salajane võti',
                                'title-info' => 'Rakenduse salajane võti, mis on seotud sinu Facebooki rakendusega. Hoia see turvaliselt ja privaatselt.',
                            ],

                            'redirect' => [
                                'title'      => 'Ümbersuunamise URL',
                                'title-info' => 'Tagasikutsumise URL, kuhu kasutajad suunatakse pärast Facebookiga autentimist. Peab vastama URL-ile, mis on konfigureeritud sinu Facebooki rakenduse seadetes.',
                            ],
                        ],

                        'github' => [
                            'enable-github' => 'Luba GitHub',

                            'client-id' => [
                                'title'      => 'Kliendi ID',
                                'title-info' => 'Unikaalne identifikaator, mille GitHub pakub, kui loote oma OAuth rakendust.',
                            ],

                            'client-secret' => [
                                'title'      => 'Kliendi salajane võti',
                                'title-info' => 'Salajane võti, mis on seotud sinu GitHub OAuth kliendiga. Hoia see konfidentsiaalselt.',
                            ],

                            'redirect' => [
                                'title'      => 'Ümbersuunamise URL',
                                'title-info' => 'Tagasikutsumise URL, kuhu kasutajad suunatakse pärast GitHubiga autentimist. Peab vastama URL-ile, mis on konfigureeritud sinu GitHub konsoolis.',
                            ],
                        ],

                        'linkedin' => [
                            'enable-linkedin' => 'Luba LinkedIn',

                            'client-id' => [
                                'title'      => 'Kliendi ID',
                                'title-info' => 'Unikaalne identifikaator, mille LinkedIn pakub, kui loote oma OAuth rakendust.',
                            ],

                            'client-secret' => [
                                'title'      => 'Kliendi salajane võti',
                                'title-info' => 'Salajane võti, mis on seotud sinu LinkedIn OAuth kliendiga. Hoia see konfidentsiaalselt.',
                            ],

                            'redirect' => [
                                'title'      => 'Ümbersuunamise URL',
                                'title-info' => 'Tagasikutsumise URL, kuhu kasutajad suunatakse pärast LinkedIniga autentimist. Peab vastama URL-ile, mis on konfigureeritud sinu LinkedIn konsoolis.',
                            ],
                        ],

                        'twitter' => [
                            'enable-twitter' => 'Luba Twitter',

                            'client-id' => [
                                'title'      => 'Kliendi ID',
                                'title-info' => 'Unikaalne identifikaator, mille Twitter pakub, kui loote oma OAuth rakendust.',
                            ],

                            'client-secret' => [
                                'title'      => 'Kliendi salajane võti',
                                'title-info' => 'Salajane võti, mis on seotud sinu Twitter OAuth kliendiga. Hoia see konfidentsiaalselt.',
                            ],

                            'redirect' => [
                                'title'      => 'Ümbersuunamise URL',
                                'title-info' => 'Tagasikutsumise URL, kuhu kasutajad suunatakse pärast Twitteriga autentimist. Peab vastama URL-ile, mis on konfigureeritud sinu Twitter konsoolis.',
                            ],
                        ],
                    ],
                ],
            ],

            'email' => [
                'info'  => 'E-post',
                'title' => 'E-post',

                'email-settings' => [
                    'admin-email'           => 'Admini e-post',
                    'admin-email-tip'       => 'Selle kanali admini e-posti aadress, et saada e-kirju',
                    'admin-name'            => 'Admini nimi',
                    'admin-name-tip'        => 'See nimi kuvatakse kõigis admini e-kirjades',
                    'admin-page-limit'      => 'Vaikimisi esemeid leheküljel (Admin)',
                    'contact-email'         => 'Kontakti e-post',
                    'contact-email-tip'     => 'E-posti aadress kuvatakse sinu e-kirjede all',
                    'contact-name'          => 'Kontakti nimi',
                    'contact-name-tip'      => 'See nimi kuvatakse sinu e-kirjede all',
                    'email-sender-name'     => 'E-posti saatja nimi',
                    'email-sender-name-tip' => 'See nimi kuvatakse klientide postkastis',
                    'info'                  => 'Määra e-posti saatja nimi, poe e-posti aadress, admini nimi ja admini e-posti aadress.',
                    'shop-email-from'       => 'Poe e-posti aadress',
                    'shop-email-from-tip'   => 'Selle kanali e-posti aadress, et saata e-kirju oma klientidele',
                    'title'                 => 'E-posti seaded',
                ],

                'notifications' => [
                    'cancel-order'                                     => 'Saada teavitus kliendile pärast tellimuse tühistamist',
                    'cancel-order-mail-to-admin'                       => 'Saada teavitus e-kiri adminile pärast tellimuse tühistamist',
                    'customer'                                         => 'Saada kliendi konto andmed pärast registreerumist',
                    'customer-registration-confirmation-mail-to-admin' => 'Saada kinnitus e-kiri adminile pärast kliendi registreerumist',
                    'info'                                             => 'Seadistamiseks, saada e-kirju konto verifitseerimiseks, tellimuste kinnitusteks, arvete uuendusteks, hüvitisteks, saadetisteks ja tellimuste tühistamisteks.',
                    'new-inventory-source'                             => 'Saada teavitus e-kiri lao allikale pärast saadetise loomist',
                    'new-invoice'                                      => 'Saada teavitus e-kiri kliendile pärast uue arve loomist',
                    'new-invoice-mail-to-admin'                        => 'Saada teavitus e-kiri adminile pärast uue arve loomist',
                    'new-order'                                        => 'Saada kinnitus e-kiri kliendile pärast uue tellimuse esitamist',
                    'new-order-mail-to-admin'                          => 'Saada kinnitus e-kiri adminile pärast uue tellimuse esitamist',
                    'new-refund'                                       => 'Saada teavitus e-kiri kliendile pärast hüvitise loomist',
                    'new-refund-mail-to-admin'                         => 'Saada teavitus e-kiri adminile pärast uue hüvitise loomist',
                    'new-shipment'                                     => 'Saada teavitus e-kiri kliendile pärast saadetise loomist',
                    'new-shipment-mail-to-admin'                       => 'Saada teavitus e-kiri adminile pärast uue saadetise loomist',
                    'registration'                                     => 'Saada kinnitus e-kiri pärast kliendi registreerumist',
                    'title'                                            => 'Teavitused',
                    'verification'                                     => 'Saada verifitseerimise e-kiri pärast kliendi registreerumist',
                ],
            ],

            'sales' => [
                'info'  => 'Müük',
                'title' => 'Müük',

                'shipping-setting' => [
                    'info'  => 'Seadista transpordi seaded, sealhulgas riik, osariik, linn, tänava aadress, sihtnumber, poe nimi, KM number, kontakti number ja panganduse detailid.',
                    'title' => 'Transpordi seaded',

                    'origin' => [
                        'bank-details'   => 'Panganduse detailid',
                        'city'           => 'Linn',
                        'contact-number' => 'Kontakti number',
                        'country'        => 'Riik',
                        'state'          => 'Osariik',
                        'store-name'     => 'Poe nimi',
                        'street-address' => 'Tänava aadress',
                        'title'          => 'Päritolu',
                        'title-info'     => 'Transpordi päritolu viitab asukohale, kust kaubad või tooted pärinevad enne sihtkohta transportimist.',
                        'vat-number'     => 'KM number',
                        'zip'            => 'Sihtnumber',
                    ],
                ],

                'shipping-methods' => [
                    'info'  => 'Seadista transpordi meetodid, sealhulgas tasuta transport, fikseeritud hind ja vajaduse korral lisa valikud.',
                    'title' => 'Transpordi meetodid',

                    'free-shipping' => [
                        'description' => 'Kirjeldus',
                        'page-title'  => 'Tasuta transport',
                        'status'      => 'Olek',
                        'title'       => 'Pealkiri',
                        'title-info'  => '"Tasuta transport" viitab transpordi meetodile, kus transpordi kulud on kaotatud ja müüja katab transpiri kulud kaupade ostjale toimtamiseks.',
                    ],

                    'flat-rate-shipping' => [
                        'description' => 'Kirjeldus',
                        'page-title'  => 'Fikseeritud hind transpordil',
                        'rate'        => 'Hind',
                        'status'      => 'Olek',
                        'title'       => 'Pealkiri',
                        'title-info'  => 'Fikseeritud hind transpordil on transpordi meetod, kus transpordi eest määratakse fikseeritud tasu, sõltumata pakendi kaalust, suurusest või kaugusest. See lihtsustab transpordi kulusid ja võib olla kasulik nii ostjatele kui müüjatele.',
                        'type'        => [
                            'per-order' => 'Tellimuse kohta',
                            'per-unit'  => 'Ühiku kohta',
                            'title'     => 'Tüüp',
                        ],
                    ],
                ],

                'payment-methods' => [
                    'accepted-currencies'            => 'Aktsepteeritud valuutad',
                    'accepted-currencies-info'       => 'Lisa valuutakood komadega eraldatult nt USD,INR,...',
                    'business-account'               => 'Ärikonto',
                    'cash-on-delivery'               => 'Tasumine kättetoimetamisel',
                    'cash-on-delivery-info'          => 'Makseviis, kus kliendid maksavad raha, kui nad saavad kaubad või teenused oma ukse taha.',
                    'client-id'                      => 'Kliendi ID',
                    'client-id-info'                 => 'Kasuta "sb" testimiseks.',
                    'client-secret'                  => 'Kliendi salajane võti',
                    'client-secret-info'             => 'Lisa oma salajane võti siia',
                    'description'                    => 'Kirjeldus',
                    'generate-invoice'               => 'Genereeri arve automaatselt pärast tellimuse esitamist',
                    'generate-invoice-applicable'    => 'Rakendub, kui automaatne arve genereerimine on lubatud',
                    'info'                           => 'Määra makseviiside informatsioon',
                    'instructions'                   => 'Juhised',
                    'logo'                           => 'Logo',
                    'logo-information'               => 'Pildi resolutsioon peaks olema nagu 55px X 45px',
                    'mailing-address'                => 'Saada tšekk aadressile',
                    'money-transfer'                 => 'Raha ülekanne',
                    'money-transfer-info'            => 'Vahendite ülekanne ühelt isikult või kontolt teisele, sageli elektrooniliselt, erinevate eesmärkide jaoks, nagu tehingud või ülekanded.',
                    'page-title'                     => 'Makseviisid',
                    'paid'                           => 'Makstud',
                    'paypal-smart-button'            => 'PayPal',
                    'paypal-smart-button-info'       => 'PayPal Smart Button: Lihtsustab veebimakseid kohandatavate nuppudega turvaliste, mitme meetodi tehingute jaoks veebisaitidel ja rakendustes.',
                    'paypal-standard'                => 'PayPal Standard',
                    'paypal-standard-info'           => 'PayPal Standard on põhiline PayPal maksevalik veebiettevõtetele, võimaldades klientidel maksta oma PayPal kontode või kreedit-/deebetkaartide abil.',
                    'pending'                        => 'Ootel',
                    'pending-payment'                => 'Ootel makse',
                    'processing'                     => 'Töötlemisel',
                    'sandbox'                        => 'Sandbox',
                    'set-invoice-status'             => 'Määra arve olek pärast arve loomist',
                    'set-order-status'               => 'Määra tellimuse olek pärast arve loomist',
                    'sort-order'                     => 'Sorteerimise järjekord',
                    'status'                         => 'Olek',
                    'title'                          => 'Pealkiri',
                ],

                'order-settings' => [
                    'info'               => 'Määra tellimuste numbrid, miinimum tellimused ja tagasitellimused.',
                    'title'              => 'Tellimuste seaded',

                    'order-number' => [
                        'generator'   => 'Tellimuse numbri generaator',
                        'info'        => 'Unikaalne identifikaator, mis on määratud spetsiifilisele kliendi tellimusele, aidates jälgimisel, kommunikatsioonil ja viitel kogu ostuprotsessi jooksul.',
                        'length'      => 'Tellimuse numbri pikkus',
                        'prefix'      => 'Tellimuse numbri eesliide',
                        'suffix'      => 'Tellimuse numbri järelliide',
                        'title'       => 'Tellimuse numbri seaded',
                    ],

                    'minimum-order' => [
                        'description'             => 'Kirjeldus',
                        'enable'                  => 'Luba',
                        'include-discount-amount' => 'Kaasa allahindluse summa',
                        'include-tax-amount'      => 'Kaasa maks summasse',
                        'info'                    => 'Konfigureeritud kriteeriumid, mis määravad madalaima vajaliku koguse või väärtuse tellimuse töötlemiseks või soodustuste saamiseks.',
                        'minimum-order-amount'    => 'Miinimum tellimuse summa',
                        'title'                   => 'Miinimum tellimuse seaded',
                    ],

                    'reorder' => [
                        'admin-reorder'      => 'Admini uuesti tellimine',
                        'admin-reorder-info' => 'Luba või keela uuesti tellimise funktsioon admini kasutajatele.',
                        'info'               => 'Luba või keela uuesti tellimise funktsioon admini kasutajatele.',
                        'shop-reorder'       => 'Poe uuesti tellimine',
                        'shop-reorder-info'  => 'Luba või keela uuesti tellimise funktsioon poe kasutajatele.',
                        'title'              => 'Luba uuesti tellimine',
                    ],

                    'stock-options' => [
                        'allow-back-orders' => 'Luba tagasitellimused',
                        'info'              => 'Aktsia valikud on investeeringute lepingud, mis annavad õiguse osta või müüa ettevõtte aktsiaid eelnevalt määratud hinnaga, mõjutades potentsiaalseid kasumeid.',
                        'title'             => 'Aktsia valikud',
                    ],
                ],

                'invoice-settings' => [
                    'info'  => 'Määra arve number, maksetingimused, arve slipi disain ja arve meeldetuletused.',
                    'title' => 'Arve seaded',

                    'invoice-number' => [
                        'generator'  => 'Arve numbri generaator',
                        'info'       => 'Reeglite või parameetrite konfigureerimine arvete unikaalsete identifitseerimisnumbrite genereerimiseks ja määramiseks organisatsioonilistel ja jälgimiseesmärkidel.',
                        'length'     => 'Arve numbri pikkus',
                        'prefix'     => 'Arve numbri eesliide',
                        'suffix'     => 'Arve numbri järelliide',
                        'title'      => 'Arve numbri seaded',
                    ],

                    'payment-terms' => [
                        'due-duration'      => 'Tähtaeg',
                        'due-duration-day'  => ':due-duration päev',
                        'due-duration-days' => ':due-duration päeva',
                        'info'              => 'Kokkulepitud tingimused, mis määravad, millal ja kuidas peaks ostja maksma kaupade või teenuste eest müüjale.',
                        'title'             => 'Maksetingimused',
                    ],

                    'pdf-print-outs' => [
                        'footer-text'      => 'Jaluse tekst',
                        'footer-text-info' => 'Sisesta tekst, mis ilmub PDF jaluseses.',
                        'info'             => 'Konfigureeri PDF printimised, et kuvada arve ID, tellimuse ID päiseses ja lisada arve logo.',
                        'invoice-id-info'  => 'Konfigureeri arve ID kuvamist arve päiseses.',
                        'invoice-id-title' => 'Kuva arve ID päiseses',
                        'logo'             => 'Logo',
                        'logo-info'        => 'Pildi resolutsioon peaks olema nagu 131px X 30px.',
                        'order-id-info'    => 'Konfigureeri tellimuse ID kuvamist arve päiseses.',
                        'order-id-title'   => 'Kuva tellimuse ID päiseses',
                        'title'            => 'PDF printimised',
                    ],

                    'invoice-reminders' => [
                        'info'                       => 'Automaatsed teavitused või kommunikatsioonid, mis saadetakse klientidele, et meenutada neile tulevasi või üle tähtaja makseid arvete eest.',
                        'interval-between-reminders' => 'Intervall meeldetuletuste vahel',
                        'maximum-limit-of-reminders' => 'Meeldetuletuste maksimaalne limiit',
                        'title'                      => 'Arve meeldetuletused',
                    ],
                ],

                'taxes' => [
                    'title'      => 'Maksud',
                    'title-info' => 'Maksud on kohustuslikud tasud, mis on kehtestatud valitsuste poolt kaupadele, teenustele või tehingutele, kogutakse müüjate poolt ja edastatakse võimudele.',

                    'categories' => [
                        'title'      => 'Makukategooriad',
                        'title-info' => 'Makukategooriad on klassifikatsioonid erinevatele maksude tüüpidele, nagu käibemaks, lisandväärtuse maks või aktsiis, mida kasutatakse toodete või teenuste kategoriseerimiseks ja maksumäärade rakendamiseks.',
                        'product'    => 'Toote vaikimisi maksukategooria',
                        'shipping'   => 'Transpordi maksukategooria',
                        'none'       => 'Puudub',
                    ],

                    'calculation' => [
                        'title'            => 'Arvutamise seaded',
                        'title-info'       => 'Üksikasjad kaupade või teenuste kulude kohta, sealhulgas baashind, allahindlused, maksud ja lisa kulud.informatsioon',
                        'based-on'         => 'Arvutamine põhineb',
                        'shipping-address' => 'Transpordi aadress',
                        'billing-address'  => 'Arve aadress',
                        'shipping-origin'  => 'Transpordi päritolu',
                        'product-prices'   => 'Toote hinnad',
                        'shipping-prices'  => 'Transpordi hinnad',
                        'excluding-tax'    => 'Ilma maksudeta',
                        'including-tax'    => 'Koos maksudega',
                    ],

                    'default-destination-calculation' => [
                        'default-country'   => 'Vaikimisi riik',
                        'default-post-code' => 'Vaikimisi sihtnumber',
                        'default-state'     => 'Vaikimisi osariik',
                        'title'             => 'Vaikimisi sihtkoha arvutamine',
                        'title-info'        => 'Automaatne standard- või algse sihtkoha määramine eelnevalt määratud tegurite või seadete alusel.',
                    ],

                    'shopping-cart' => [
                        'title'                   => 'Ostukorvi kuvamise seaded',
                        'title-info'              => 'Määra maksude kuvamine ostukorvis',
                        'display-prices'          => 'Kuva hinnad',
                        'display-subtotal'        => 'Kuva vahesumma',
                        'display-shipping-amount' => 'Kuva transpordi summa',
                        'excluding-tax'           => 'Ilma maksudeta',
                        'including-tax'           => 'Koos maksudega',
                        'both'                    => 'Ilma ja koos maksudega mõlemad',
                    ],

                    'sales' => [
                        'title'                   => 'Tellimused, arved, hüvitised kuvamise seaded',
                        'title-info'              => 'Määra maksude kuvamine tellimustes, arvetes ja hüvitistes',
                        'display-prices'          => 'Kuva hinnad',
                        'display-subtotal'        => 'Kuva vahesumma',
                        'display-shipping-amount' => 'Kuva transpordi summa',
                        'excluding-tax'           => 'Ilma maksudeta',
                        'including-tax'           => 'Koos maksudega',
                        'both'                    => 'Ilma ja koos maksudega mõlemad',
                    ],
                ],

                'checkout' => [
                    'title' => 'Kassa',
                    'info'  => 'Määra külaliskassa, luba või keela Mini ostukorv, ostukorvi kokkuvõte.',

                    'shopping-cart' => [
                        'cart-page'              => 'Ostukorvi leht',
                        'cart-page-info'         => 'Kontrolli ostukorvi lehe nähtavust, et parandada kasutaja ostukogemust.',
                        'cross-sell'             => 'Ristmüügi tooted',
                        'cross-sell-info'        => 'Luba ristmüügi tooteid, et suurendada lisa müügivõimalusi.',
                        'estimate-shipping'      => 'Hinnanguline transport',
                        'estimate-shipping-info' => 'Luba hinnanguline transport, et pakkuda ette transpordi kulud.',
                        'guest-checkout'         => 'Luba külaliskassa',
                        'guest-checkout-info'    => 'Luba külaliskassa kiiremaks, probleemivabaks ostuprotsessiks.',
                        'info'                   => 'Luba külaliskassa, ostukorvi leht, ristmüügi tooted ja hinnanguline transport, et parandada kasutaja mugavust ja lihtsustada ostuprotsessi suurendatud müügi jaoks.',
                        'title'                  => 'Ostukorv',
                    ],

                    'my-cart' => [
                        'display-item-quantities' => 'Kuva esemete koguseid',
                        'display-number-in-cart'  => 'Kuva esemete arv ostukorvis',
                        'info'                    => 'Luba seaded "Minu ostukorv" jaoks, et näidata esemete koguste kokkuvõtet ja kuvada esemete koguarvu ostukorvis lihtsa jälgimise jaoks.',
                        'summary'                 => 'Kokkuvõte',
                        'title'                   => 'Minu ostukorv',
                    ],

                    'mini-cart' => [
                        'display-mini-cart'    => 'Kuva Mini ostukorv',
                        'info'                 => 'Luba Mini ostukorvi seaded, et kuvada mini ostukorvi ja näidata Mini ostukorvi pakkumise informatsiooni kiireks ligipääsuks ostukorvi detailidele ja promotsioonidele.',
                        'mini-cart-offer-info' => 'Mini ostukorvi pakkumise informatsioon',
                        'title'                => 'Mini ostukorv',
                    ],
                ],
            ],
        ],
    ],
];
