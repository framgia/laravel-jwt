<?php

return [

    'secret' => env('JWT_SECRET', env('APP_SECRET', 'SomeRandomString')),

    'signer' => [

        'default' => 'hmac',

        'hmac' => [

            'algorithm' => 'sha256',

        ],

    ],

];
