<?php

return [
    'facebook_url' => env('THUNDERPOINT_FACEBOOK_URL', 'https://www.facebook.com/search/top?q=thunderpoint%20eastman'),
    'payment_methods' => [
        'pay_later' => 'Pay later',
        'paypal' => 'PayPal',
        'venmo' => 'Venmo',
        'zelle' => 'Zelle',
    ],
    'living_areas' => [
        [
            'name' => 'Boathouse',
            'slug' => 'boathouse',
            'description' => 'Closest to the dock.',
            'deep_color' => '#c66a2b',
            'soft_color' => '#f1d0b7',
        ],
        [
            'name' => "Jack's Part",
            'slug' => 'jacks-part',
            'description' => 'Lake-facing rooms and porch.',
            'deep_color' => '#2f6f87',
            'soft_color' => '#cae2e9',
        ],
        [
            'name' => "Jann's Part",
            'slug' => 'janns-part',
            'description' => 'The wooded side of the point.',
            'deep_color' => '#315b3f',
            'soft_color' => '#cad9c9',
        ],
        [
            'name' => "Joyce's Part",
            'slug' => 'joyces-part',
            'description' => 'A quieter corner facing the trees.',
            'deep_color' => '#9e4a4a',
            'soft_color' => '#e8c9c5',
        ],
    ],
];