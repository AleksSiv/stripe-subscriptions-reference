<?php

return [

    // Two recurring tiers. Create matching recurring Prices in the Stripe
    // dashboard and drop their price IDs into the env file.
    'tiers' => [
        'basic' => [
            'name'     => 'Basic',
            'price_id' => env('STRIPE_PRICE_BASIC'),
            'blurb'    => 'A single listing with standard placement.',
            'price'    => '$19 / month',
        ],
        'pro' => [
            'name'     => 'Pro',
            'price_id' => env('STRIPE_PRICE_PRO'),
            'blurb'    => 'Featured placement and priority search.',
            'price'    => '$49 / month',
        ],
    ],
];
