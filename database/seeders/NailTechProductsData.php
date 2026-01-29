<?php

function getNailTechProducts()
{
    return [
        // Geellakid (10)
        ['sku' => 'GL-001', 'name' => 'CND Shellac Ruby Ritz', 'short_description' => 'Ruby red gel polish', 'description' => 'Premium rubiinpunane geellakk professionaalseks kasutuseks.', 'url_key' => 'cnd-shellac-ruby-ritz', 'price' => 24.99, 'category_slug' => 'geellakid', 'new' => 1, 'featured' => 1],
        ['sku' => 'GL-002', 'name' => 'OPI GelColor Black Onyx', 'short_description' => 'Classic black gel polish', 'description' => 'Klassikaline must geellakk tugevate pigmentidega.', 'url_key' => 'opi-gelcolor-black-onyx', 'price' => 22.50, 'category_slug' => 'geellakid', 'new' => 1],
        ['sku' => 'GL-003', 'name' => 'Gelish Pink Smoothie', 'short_description' => 'Soft pink gel', 'description' => 'Õrn roosa toon prantsuse maniküüriks.', 'url_key' => 'gelish-pink-smoothie', 'price' => 21.99, 'category_slug' => 'geellakid'],
        ['sku' => 'GL-004', 'name' => 'Essie Gel Couture Ballet Slippers', 'short_description' => 'Nude pink gel', 'description' => 'Nude geellakk igapäevaseks kandmiseks.', 'url_key' => 'essie-gel-couture-ballet-slippers', 'price' => 19.99, 'category_slug' => 'geellakid'],
        ['sku' => 'GL-005', 'name' => 'Artistic Colour Gloss Red', 'short_description' => 'Vibrant red gel', 'description' => 'Tugeva katvusega erkpunane geellakk.', 'url_key' => 'artistic-colour-gloss-red', 'price' => 18.50, 'category_slug' => 'geellakid'],
        ['sku' => 'GL-006', 'name' => 'Gelish Forever Fabulous Set', 'short_description' => '6 geellaki komplekt', 'description' => 'Populaarsete toonidega geellakkide komplekt.', 'url_key' => 'gelish-forever-fabulous-set', 'price' => 89.90, 'category_slug' => 'geellakid', 'featured' => 1],
        ['sku' => 'GL-007', 'name' => 'CND Shellac Nude Knickers', 'short_description' => 'Nude gel', 'description' => 'Loomulik nude toon küünetehnikule.', 'url_key' => 'cnd-shellac-nude-knickers', 'price' => 24.99, 'category_slug' => 'geellakid'],
        ['sku' => 'GL-008', 'name' => 'OPI GelColor Big Apple Red', 'short_description' => 'Iconic red', 'description' => 'Ikoniline OPI punane toon.', 'url_key' => 'opi-gelcolor-big-apple-red', 'price' => 22.50, 'category_id' => 2],
        ['sku' => 'GL-009', 'name' => 'Gelish Navy Blue', 'short_description' => 'Navy blue gel', 'description' => 'Sügav tumesinine geellakk pidulikeks lookideks.', 'url_key' => 'gelish-navy-blue', 'price' => 21.99, 'category_id' => 2],
        ['sku' => 'GL-010', 'name' => 'CND Shellac Lavender Lace', 'short_description' => 'Lavender gel', 'description' => 'Õrn lavendlitoon kevadeks ja suveks.', 'url_key' => 'cnd-shellac-lavender-lace', 'price' => 24.99, 'category_id' => 2],

        // UV-geelid (7)
        ['sku' => 'UV-001', 'name' => 'IBD Builder Gel Clear', 'short_description' => 'Clear builder gel', 'description' => 'Kristallselge ehitusgeel pikendusteks.', 'url_key' => 'ibd-builder-gel-clear', 'price' => 34.99, 'category_id' => 3, 'featured' => 1],
        ['sku' => 'UV-002', 'name' => 'Gelish Hard Gel Pink', 'short_description' => 'Pink builder gel', 'description' => 'Roosakas kattev ehitusgeel naturaalsete küünte jaoks.', 'url_key' => 'gelish-hard-gel-pink', 'price' => 32.50, 'category_id' => 3],
        ['sku' => 'UV-003', 'name' => 'CND Brisa Sculpting Gel', 'short_description' => 'Sculpting gel', 'description' => 'Profitaseme geelsüsteem küüntepikendusteks.', 'url_key' => 'cnd-brisa-sculpting-gel', 'price' => 45.99, 'category_id' => 3, 'featured' => 1],
        ['sku' => 'UV-004', 'name' => 'Young Nails Cover Pink Gel', 'short_description' => 'Cover pink', 'description' => 'Ilugeel naturaaltooniga küüneplaadi ühtlustamiseks.', 'url_key' => 'young-nails-cover-pink-gel', 'price' => 38.99, 'category_id' => 3],
        ['sku' => 'UV-005', 'name' => 'Artistic Rock Hard LED Gel', 'short_description' => 'LED builder gel', 'description' => 'LED-lambi all kõvastuv tugev ehitusgeel.', 'url_key' => 'artistic-rock-hard-led-gel', 'price' => 36.50, 'category_id' => 3],
        ['sku' => 'UV-006', 'name' => 'Gelish PolyGel Clear', 'short_description' => 'Hybrid polygel', 'description' => 'Polygel–geeli ja akrüüli hübriid küünetehnikule.', 'url_key' => 'gelish-polygel-clear', 'price' => 42.99, 'category_id' => 3, 'new' => 1],
        ['sku' => 'UV-007', 'name' => 'IBD Builder Gel White', 'short_description' => 'White builder', 'description' => 'Valge ehitusgeel prantsuse maniküürile.', 'url_key' => 'ibd-builder-gel-white', 'price' => 34.99, 'category_id' => 3],

        // Baas- ja pealislakid (7)
        ['sku' => 'BP-001', 'name' => 'CND Shellac Base Coat', 'short_description' => 'Base coat', 'description' => 'Tugeva nakkuvusega aluslakk geellakkidele.', 'url_key' => 'cnd-shellac-base-coat', 'price' => 24.99, 'category_id' => 4, 'featured' => 1],
        ['sku' => 'BP-002', 'name' => 'CND Shellac Top Coat', 'short_description' => 'Glossy top', 'description' => 'Kõrgläikega pealislakk, mis kaitseb kriimustuste eest.', 'url_key' => 'cnd-shellac-top-coat', 'price' => 24.99, 'category_id' => 4, 'featured' => 1],
        ['sku' => 'BP-003', 'name' => 'Gelish Foundation Base Gel', 'short_description' => 'Foundation base', 'description' => 'Alusgeel kauapüsivate maniküüride jaoks.', 'url_key' => 'gelish-foundation-base-gel', 'price' => 22.50, 'category_id' => 4],
        ['sku' => 'BP-004', 'name' => 'Gelish Top It Off', 'short_description' => 'Shiny top coat', 'description' => 'Läikiv pealislakk geellakkidele.', 'url_key' => 'gelish-top-it-off', 'price' => 22.50, 'category_id' => 4],
        ['sku' => 'BP-005', 'name' => 'OPI GelColor Base Coat', 'short_description' => 'OPI base', 'description' => 'OPI aluslakk geellakisüsteemile.', 'url_key' => 'opi-gelcolor-base-coat', 'price' => 21.99, 'category_id' => 4],
        ['sku' => 'BP-006', 'name' => 'OPI GelColor Top Coat', 'short_description' => 'OPI top', 'description' => 'Kõrgläikega OPI pealislakk.', 'url_key' => 'opi-gelcolor-top-coat', 'price' => 21.99, 'category_id' => 4],
        ['sku' => 'BP-007', 'name' => 'Gelish No Wipe Top', 'short_description' => 'No-wipe top', 'description' => 'Puhastamisvaba pealislakk kiireks tööks.', 'url_key' => 'gelish-no-wipe-top', 'price' => 24.99, 'category_id' => 4, 'new' => 1],

        // Küünetööriistad (7)
        ['sku' => 'KT-001', 'name' => 'Pro Nail File Set 10pcs', 'short_description' => 'Viilide komplekt', 'description' => '10-osaline küüneviilide komplekt erinevate karedusega.', 'url_key' => 'pro-nail-file-set-10pcs', 'price' => 15.99, 'category_id' => 5],
        ['sku' => 'KT-002', 'name' => 'Cuticle Nipper Pro', 'short_description' => 'Kutikula tangid', 'description' => 'Terasest kutikula tangid täpseks tööks.', 'url_key' => 'cuticle-nipper-pro', 'price' => 24.99, 'category_id' => 5, 'featured' => 1],
        ['sku' => 'KT-003', 'name' => 'Cuticle Pusher Steel', 'short_description' => 'Kutikula lükkaja', 'description' => 'Kahepoolne roostevabast terasest kutikula lükkaja.', 'url_key' => 'cuticle-pusher-steel', 'price' => 8.99, 'category_id' => 5],
        ['sku' => 'KT-004', 'name' => 'Nail Art Brush Set 15pcs', 'short_description' => 'Pintsli komplekt', 'description' => '15-osaline nail art pintslite komplekt detailseks tööks.', 'url_key' => 'nail-art-brush-set-15pcs', 'price' => 29.99, 'category_id' => 5, 'featured' => 1],
        ['sku' => 'KT-005', 'name' => 'Buffer Block 4-way', 'short_description' => '4-astmeline buffer', 'description' => '4-astmeline poleerimisplokk küünte viimistlemiseks.', 'url_key' => 'buffer-block-4-way', 'price' => 3.99, 'category_id' => 5],
        ['sku' => 'KT-006', 'name' => 'Manicure Tool Kit', 'short_description' => 'Tööriistade komplekt', 'description' => 'Maniküüri põhitööriistade komplekt salongile.', 'url_key' => 'manicure-tool-kit', 'price' => 39.99, 'category_id' => 5],
        ['sku' => 'KT-007', 'name' => 'Pinsetid Nail Artile', 'short_description' => 'Nail art pintsetid', 'description' => 'Peenikese otsaga pintsetid kivikeste ja kleebiste paigutamiseks.', 'url_key' => 'pinsetid-nail-art', 'price' => 9.99, 'category_id' => 5],

        // Elektrilised seadmed (7)
        ['sku' => 'ES-001', 'name' => 'Pro E-File 35000 RPM', 'short_description' => 'Profifrees', 'description' => '35000 RPM elektrifrees küünetehnikule.', 'url_key' => 'pro-e-file-35000-rpm', 'price' => 129.00, 'category_id' => 6, 'featured' => 1],
        ['sku' => 'ES-002', 'name' => 'Freesiotsikute Komplekt 10pcs', 'short_description' => 'Freesiotsikud', 'description' => '10 freesiotsikuga komplekt geeli ja akrüüli viilimiseks.', 'url_key' => 'freesiotsikute-komplekt-10pcs', 'price' => 24.99, 'category_id' => 6],
        ['sku' => 'ES-003', 'name' => 'Tolmuimeja Lauale', 'short_description' => 'Lauatolmuimeja', 'description' => 'Maniküüri laud-tolmuimeja filterkotiga.', 'url_key' => 'tolmuimeja-lauale', 'price' => 79.00, 'category_id' => 6],
        ['sku' => 'ES-004', 'name' => 'Mini Tolmuimeja Käetoe All', 'short_description' => 'Mini tolmuimeja', 'description' => 'Kompaktne tolmuimeja väikese salongi jaoks.', 'url_key' => 'mini-tolmuimeja-kaetoe-all', 'price' => 59.00, 'category_id' => 6],
        ['sku' => 'ES-005', 'name' => 'Freesiotsik Karbiid Medium', 'short_description' => 'Karbiid otsik', 'description' => 'Keskmise tugevusega karbiidotsik geeli eemaldamiseks.', 'url_key' => 'freesiotsik-karbiid-medium', 'price' => 11.90, 'category_id' => 6],
        ['sku' => 'ES-006', 'name' => 'Freesiotsik Keraamiline Fine', 'short_description' => 'Keraamiline otsik', 'description' => 'Õrn keraamiline freesiotsik naturaalsetele küüntele.', 'url_key' => 'freesiotsik-keraamiline-fine', 'price' => 13.90, 'category_id' => 6],
        ['sku' => 'ES-007', 'name' => 'LED Lauavalgusti Maniküürile', 'short_description' => 'Lauavalgusti', 'description' => 'Painduv LED-lauavalgusti täpseks tööks.', 'url_key' => 'led-lauavalgusti-manikyyr', 'price' => 49.00, 'category_id' => 6],

        // Hooldusvahendid (7)
        ['sku' => 'HV-001', 'name' => 'Cuticle Oil Mandli', 'short_description' => 'Mandliõli küünenahale', 'description' => 'Toitev mandli küünenahaõli igapäevaseks hoolduseks.', 'url_key' => 'cuticle-oil-mandli', 'price' => 7.90, 'category_id' => 7],
        ['sku' => 'HV-002', 'name' => 'Cuticle Remover Gel', 'short_description' => 'Küünenaha eemaldaja', 'description' => 'Küünenaha pehmendaja ja eemaldusgeel.', 'url_key' => 'cuticle-remover-gel', 'price' => 8.90, 'category_id' => 7],
        ['sku' => 'HV-003', 'name' => 'Käte Kreem Sheavõiga', 'short_description' => 'Sheavõi kätekreem', 'description' => 'Niisutav kätekreem salongi klientidele.', 'url_key' => 'kate-kreem-sheavoi', 'price' => 9.90, 'category_id' => 7],
        ['sku' => 'HV-004', 'name' => 'Küünenaha Niisutuspliiats', 'short_description' => 'Õlipliiats', 'description' => 'Õliga täidetud pliiats küünenahkade hoolduseks.', 'url_key' => 'kyynenaha-niisutuspliiats', 'price' => 6.90, 'category_id' => 7],
        ['sku' => 'HV-005', 'name' => 'Sügavhooldav Mask Kätele', 'short_description' => 'Kätemask', 'description' => 'Intensiivne mask kuivadele kätele.', 'url_key' => 'sugavhooldav-mask-katele', 'price' => 11.90, 'category_id' => 7],
        ['sku' => 'HV-006', 'name' => 'Küünenaha Eemaldusvedelik', 'short_description' => 'Remover vedelik', 'description' => 'Professionaalne küünenaha eemaldusvedelik.', 'url_key' => 'kyynenaha-eemaldusvedelik', 'price' => 7.50, 'category_id' => 7],
        ['sku' => 'HV-007', 'name' => 'Spa Kätevann Soolaga', 'short_description' => 'Spa sool', 'description' => 'Lõõgastav kätevannisool maniküüriprotseduuriks.', 'url_key' => 'spa-katevann-sool', 'price' => 5.90, 'category_id' => 7],

        // Küünetipud ja ehitusmaterjalid (7)
        ['sku' => 'KTIP-001', 'name' => 'Küünetipud Selged 500pcs', 'short_description' => 'Selged tipid', 'description' => '500-osaline selgete küünetippude komplekt karbis.', 'url_key' => 'kyynetipud-selged-500', 'price' => 19.90, 'category_id' => 8],
        ['sku' => 'KTIP-002', 'name' => 'Küünetipud Loomulik 500pcs', 'short_description' => 'Loomulikud tipid', 'description' => 'Loomuliku tooniga küünetipud pikendusteks.', 'url_key' => 'kyynetipud-loomulikud-500', 'price' => 19.90, 'category_id' => 8],
        ['sku' => 'KTIP-003', 'name' => 'Vormid Pikendamiseks Rull', 'short_description' => 'Pikendusvormid', 'description' => 'Rullis pikendusvormid geeli ja polygeli jaoks.', 'url_key' => 'vormid-pikendamiseks-rull', 'price' => 12.90, 'category_id' => 8],
        ['sku' => 'KTIP-004', 'name' => 'Polygel Nude Tube', 'short_description' => 'Nude polygel', 'description' => 'Nude toonis polygel ehituseks.', 'url_key' => 'polygel-nude-tube', 'price' => 24.90, 'category_id' => 8],
        ['sku' => 'KTIP-005', 'name' => 'Polygel Rose Tube', 'short_description' => 'Rose polygel', 'description' => 'Õrn roosa polygel salongi tööks.', 'url_key' => 'polygel-rose-tube', 'price' => 24.90, 'category_id' => 8],
        ['sku' => 'KTIP-006', 'name' => 'Tipiliim Pintsliotsaga', 'short_description' => 'Tipiliim', 'description' => 'Kiirelt kuivav tipiliim pintsliotsaga.', 'url_key' => 'tipiliim-pintsliotsaga', 'price' => 4.90, 'category_id' => 8],
        ['sku' => 'KTIP-007', 'name' => 'Šabloonid Frenchi Joonistamiseks', 'short_description' => 'French šabloonid', 'description' => 'Abivahend prantsuse maniküüri joonistamiseks.', 'url_key' => 'sabloonid-french', 'price' => 3.90, 'category_id' => 8],

        // Nail Art dekoratsioonid (7)
        ['sku' => 'NA-001', 'name' => 'Kristallid Mix 12pots', 'short_description' => 'Kristallide komplekt', 'description' => '12 tooni küünekristallide komplekt karbis.', 'url_key' => 'kristallid-mix-12', 'price' => 14.90, 'category_id' => 9, 'featured' => 1],
        ['sku' => 'NA-002', 'name' => 'Küünekleebised Minimalistlik', 'short_description' => 'Minimal kleebised', 'description' => 'Minimalistlikud must-valged küünekleebised.', 'url_key' => 'kyynekleebised-minimalistlik', 'price' => 4.50, 'category_id' => 9],
        ['sku' => 'NA-003', 'name' => 'Foolium Kuldtone', 'short_description' => 'Kuldne foil', 'description' => 'Kuldne küünefoolium eriefektide loomiseks.', 'url_key' => 'foolium-kuldtone', 'price' => 5.50, 'category_id' => 9],
        ['sku' => 'NA-004', 'name' => 'Pulber Chrome Silver', 'short_description' => 'Chrome pulber', 'description' => 'Hõbedane kroomefektiga pulber.', 'url_key' => 'pulber-chrome-silver', 'price' => 7.90, 'category_id' => 9],
        ['sku' => 'NA-005', 'name' => 'Glitter Mix Rose Gold', 'short_description' => 'Rose gold glitter', 'description' => 'Peen ja jäme glitter roosakuldsetes toonides.', 'url_key' => 'glitter-mix-rose-gold', 'price' => 6.90, 'category_id' => 9],
        ['sku' => 'NA-006', 'name' => 'Joonistusgeel Must', 'short_description' => 'Must joonistusgeel', 'description' => 'Õhukese pintsliga joonistamiseks mõeldud geel.', 'url_key' => 'joonistusgeel-must', 'price' => 9.90, 'category_id' => 9],
        ['sku' => 'NA-007', 'name' => 'Spider Gel Valge', 'short_description' => 'Spider gel', 'description' => 'Veniv spider-geel graafiliste disainide jaoks.', 'url_key' => 'spider-gel-valge', 'price' => 9.90, 'category_id' => 9],

        // Puhastus- ja desinfitseerimisvahendid (7)
        ['sku' => 'PD-001', 'name' => 'Nail Cleaner 500ml', 'short_description' => 'Cleaner geellakile', 'description' => 'Pinnapuhastaja geeli ja geellaki kleepuva kihi eemaldamiseks.', 'url_key' => 'nail-cleaner-500', 'price' => 8.90, 'category_id' => 10],
        ['sku' => 'PD-002', 'name' => 'Prep & Prime Duo', 'short_description' => 'Prep ja primer', 'description' => 'Küünetasandaja ja primer komplekt.', 'url_key' => 'prep-prime-duo', 'price' => 12.90, 'category_id' => 10],
        ['sku' => 'PD-003', 'name' => 'Käte Desinfitseerimisvahend 250ml', 'short_description' => 'Käte deso', 'description' => 'Kiiretoimeline desinfitseerimisvahend enne protseduuri.', 'url_key' => 'kate-desinfitseerimisvahend-250', 'price' => 6.90, 'category_id' => 10],
        ['sku' => 'PD-004', 'name' => 'Instrumentide Desolahus', 'short_description' => 'Instrumentide deso', 'description' => 'Kontsentraat tööriistade desinfitseerimiseks.', 'url_key' => 'instrumentide-desolahus', 'price' => 14.90, 'category_id' => 10],
        ['sku' => 'PD-005', 'name' => 'Lint Free Padjad 500pcs', 'short_description' => 'Padjad ilma karvata', 'description' => 'Karvavabad puhastuspadjad cleaneriga kasutamiseks.', 'url_key' => 'lint-free-padjad-500', 'price' => 5.90, 'category_id' => 10],
        ['sku' => 'PD-006', 'name' => 'Kinda Desinfitseeriv Spray', 'short_description' => 'Spray pintslitele', 'description' => 'Pintslite ja tööpinna kiire desinfitseerija.', 'url_key' => 'kinda-desinfitseeriv-spray', 'price' => 7.90, 'category_id' => 10],
        ['sku' => 'PD-007', 'name' => 'Klaasist Desinfitseerimisanum', 'short_description' => 'Anum vedelikule', 'description' => 'Klaasanum tööriistade leotamiseks desolahuses.', 'url_key' => 'klaasist-desinfitseerimisanum', 'price' => 9.90, 'category_id' => 10],

        // Lambid ja UV/LED valgustid (7)
        ['sku' => 'LM-001', 'name' => 'UV/LED Lamp 48W', 'short_description' => '48W lamp', 'description' => '48W kombineeritud UV/LED lamp geellakkidele ja geelidele.', 'url_key' => 'uv-led-lamp-48w', 'price' => 69.00, 'category_id' => 11, 'featured' => 1],
        ['sku' => 'LM-002', 'name' => 'UV/LED Lamp 72W Pro', 'short_description' => '72W profiseade', 'description' => 'Kiire kõvastusega profitaseme lamp.', 'url_key' => 'uv-led-lamp-72w-pro', 'price' => 99.00, 'category_id' => 11],
        ['sku' => 'LM-003', 'name' => 'Mini Taskulamp Nail Artile', 'short_description' => 'Mini lamp', 'description' => 'Väike taskulamp detailsete disainide fikseerimiseks.', 'url_key' => 'mini-taskulamp-nailart', 'price' => 19.90, 'category_id' => 11],
        ['sku' => 'LM-004', 'name' => 'Varulambid 365/405nm Komplekt', 'short_description' => 'Varu LED-id', 'description' => 'Varu-LED-moodulid UV/LED lampidele.', 'url_key' => 'varulambid-uv-led', 'price' => 14.90, 'category_id' => 11],
        ['sku' => 'LM-005', 'name' => 'Kaasaskantav Reisilamp USB', 'short_description' => 'USB lamp', 'description' => 'Väike USB-lamp mobiilseks tööks.', 'url_key' => 'kaasaskantav-reisilamp-usb', 'price' => 24.90, 'category_id' => 11],
        ['sku' => 'LM-006', 'name' => 'Käetoega UV/LED Lamp', 'short_description' => 'Lamp käetoega', 'description' => 'Lamp sisseehitatud käetoega mugavaks asetuseks.', 'url_key' => 'kaetoega-uv-led-lamp', 'price' => 89.00, 'category_id' => 11],
        ['sku' => 'LM-007', 'name' => 'Lamp Varjuvastase Kupliga', 'short_description' => 'Varjuvastane lamp', 'description' => 'Ühtlaselt valgustav lamp, mis vähendab varje.', 'url_key' => 'lamp-varjuvastase-kupliga', 'price' => 59.00, 'category_id' => 11],
    ];
}
