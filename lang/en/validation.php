<?php

return [

   'accepted'             => ':attribute väli peab olema aktsepteeritud.',
'accepted_if'          => ':attribute väli peab olema aktsepteeritud, kui :other on :value.',
'active_url'           => ':attribute väli peab olema kehtiv URL.',
'after'                => ':attribute peab olema kuupäev pärast :date.',
'after_or_equal'       => ':attribute peab olema kuupäev, mis on pärast või võrdne :date.',
'alpha'                => ':attribute väli võib sisaldada ainult tähti.',
'alpha_dash'           => ':attribute väli võib sisaldada ainult tähti, numbreid, sidekriipse ja alakriipse.',
'alpha_num'            => ':attribute väli võib sisaldada ainult tähti ja numbreid.',
'array'                => ':attribute väli peab olema massiiv.',
'ascii'                => ':attribute väli võib sisaldada ainult ühebaidiseid tähti, numbreid ja sümboleid.',

'between' => [
    'array'   => ':attribute väljas peab olema :min kuni :max elementi.',
    'file'    => ':attribute faili suurus peab olema :min kuni :max kilobaiti.',
    'numeric' => ':attribute väärtus peab olema vahemikus :min kuni :max.',
    'string'  => ':attribute pikkus peab olema :min kuni :max märki.',
],

'boolean'              => ':attribute väli peab olema tõene või väär.',
'confirmed'            => ':attribute kinnitus ei ühti.',
'current_password'     => 'Parool on vale.',
'date'                 => ':attribute ei ole kehtiv kuupäev.',
'date_equals'          => ':attribute peab olema kuupäev, mis on võrdne :date.',
'date_format'          => ':attribute ei vasta vormingule :format.',
'declined'             => ':attribute väli peab olema tagasi lükatud.',
'declined_if'          => ':attribute väli peab olema tagasi lükatud, kui :other on :value.',
'different'            => ':attribute ja :other peavad olema erinevad.',
'digits'               => ':attribute peab olema :digits-kohaline.',
'digits_between'       => ':attribute peab olema :min kuni :max numbrit pikk.',
'dimensions'           => ':attribute pildi mõõtmed ei ole lubatud.',
'distinct'             => ':attribute väljal on duplikaatväärtus.',
'email'                => ':attribute peab olema kehtiv e-posti aadress.',
'ends_with'            => ':attribute peab lõppema ühega järgmistest: :values.',
'enum'                 => 'Valitud :attribute on vigane.',
'exists'               => 'Valitud :attribute on vigane.',
'file'                 => ':attribute peab olema fail.',
'filled'               => ':attribute väljal peab olema väärtus.',
'gt' => [
    'array'   => ':attribute väljas peab olema rohkem kui :value elementi.',
    'file'    => ':attribute faili suurus peab olema suurem kui :value kilobaiti.',
    'numeric' => ':attribute peab olema suurem kui :value.',
    'string'  => ':attribute peab olema pikem kui :value märki.',
],

'gte' => [
    'array'   => ':attribute väljas peab olema vähemalt :value elementi.',
    'file'    => ':attribute faili suurus peab olema vähemalt :value kilobaiti.',
    'numeric' => ':attribute peab olema vähemalt :value.',
    'string'  => ':attribute peab olema vähemalt :value märki pikk.',
],

'image'                => ':attribute peab olema pilt.',
'in'                   => 'Valitud :attribute on vigane.',
'in_array'             => ':attribute väli ei esine :other väljas.',
'integer'              => ':attribute peab olema täisarv.',
'ip'                   => ':attribute peab olema kehtiv IP-aadress.',
'ipv4'                 => ':attribute peab olema kehtiv IPv4-aadress.',
'ipv6'                 => ':attribute peab olema kehtiv IPv6-aadress.',

'mac_address'          => ':attribute väli peab olema kehtiv MAC-aadress.',

'max' => [
    'array'   => ':attribute väljas ei tohi olla rohkem kui :max elementi.',
    'file'    => ':attribute faili suurus ei tohi olla suurem kui :max kilobaiti.',
    'numeric' => ':attribute ei tohi olla suurem kui :max.',
    'string'  => ':attribute ei tohi olla pikem kui :max märki.',
],

'min' => [
    'array'   => ':attribute väljas peab olema vähemalt :min elementi.',
    'file'    => ':attribute faili suurus peab olema vähemalt :min kilobaiti.',
    'numeric' => ':attribute peab olema vähemalt :min.',
    'string'  => ':attribute peab olema vähemalt :min märki pikk.',
],

'numeric'             => ':attribute peab olema number.',
'password' => [
    'letters'       => ':attribute peab sisaldama vähemalt üht tähte.',
    'mixed'         => ':attribute peab sisaldama nii suuri kui ka väikseid tähti.',
    'numbers'       => ':attribute peab sisaldama vähemalt üht numbrit.',
    'symbols'       => ':attribute peab sisaldama vähemalt üht sümbolit.',
    'uncompromised' => 'See :attribute on lekkinud andmebaasides. Palun vali mõni muu parool.',
],

'required'             => ':attribute väli on kohustuslik.',
'required_if'          => ':attribute väli on kohustuslik, kui :other on :value.',
'required_unless'      => ':attribute väli on kohustuslik, välja arvatud kui :other on väärtustest :values.',
'required_with'        => ':attribute väli on kohustuslik, kui :values on olemas.',
'required_without'     => ':attribute väli on kohustuslik, kui :values ei ole olemas.',
'same'                 => ':attribute ja :other peavad ühtima.',

'string'               => ':attribute peab olema string.',
'unique'               => ':attribute on juba kasutusel.',
'url'                  => ':attribute peab olema kehtiv URL.',
'uuid'                 => ':attribute peab olema kehtiv UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
