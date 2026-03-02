<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OMDb API Configuration
    |--------------------------------------------------------------------------
    */
    'api_key' => env('OMDB_API_KEY', ''),
    'base_url' => env('OMDB_BASE_URL', 'http://www.omdbapi.com/'),

    /*
    |--------------------------------------------------------------------------
    | Discovery Terms
    |--------------------------------------------------------------------------
    |
    | Rotating keywords used on the landing page to find results when the
    | user has not entered a search query. Rotates daily (by day-of-year
    | index) so visitors see variety without hitting the OMDb "Too many
    | results" limit.
    |
    */
    'default_terms' => [
        'dark',
        'rise',
        'night',
        'fire',
        'war',
        'last',
        'dead',
        'black',
        'love',
        'new',
        'man',
        'fall',
        'iron',
        'wild',
        'lost',
    ],
];
