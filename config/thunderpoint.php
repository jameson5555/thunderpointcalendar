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
            'deep_color' => '#ed7009',
            'soft_color' => '#f6d3ae',
        ],
        [
            'name' => "Jack's Part",
            'slug' => 'jacks-part',
            'description' => 'Lake-facing rooms and porch.',
            'deep_color' => '#1a8c91',
            'soft_color' => '#c8e5e5',
        ],
        [
            'name' => "Joyce's Part",
            'slug' => 'joyces-part',
            'description' => 'A quieter corner facing the trees.',
            'deep_color' => '#e7a30f',
            'soft_color' => '#f4dd9e',
        ],
        [
            'name' => "Jann's Part",
            'slug' => 'janns-part',
            'description' => 'The wooded side of the point.',
            'deep_color' => '#6f7429',
            'soft_color' => '#dbe0b5',
        ],
    ],
];