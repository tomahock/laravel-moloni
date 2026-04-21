<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Moloni API Credentials
    |--------------------------------------------------------------------------
    |
    | Your Moloni API credentials. These can be obtained from your Moloni
    | developer account at https://www.moloni.pt/dev/
    |
    */
    'client_id' => env('MOLONI_CLIENT_ID'),
    'client_secret' => env('MOLONI_CLIENT_SECRET'),
    'redirect_uri' => env('MOLONI_REDIRECT_URI'),
    'username' => env('MOLONI_USERNAME'),
    'password' => env('MOLONI_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Grant Type
    |--------------------------------------------------------------------------
    |
    | The OAuth grant type to use. Use 'password' for native applications
    | (direct username/password) or 'authorization_code' for web applications.
    |
    */
    'grant_type' => env('MOLONI_GRANT_TYPE', 'password'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('MOLONI_BASE_URL', 'https://api.moloni.pt/v1'),

    /*
    |--------------------------------------------------------------------------
    | Token Storage
    |--------------------------------------------------------------------------
    |
    | The cache driver and key prefix used to store OAuth tokens.
    |
    */
    'token_cache_driver' => env('MOLONI_CACHE_DRIVER', null),
    'token_cache_prefix' => env('MOLONI_CACHE_PREFIX', 'moloni_token'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    */
    'timeout' => env('MOLONI_TIMEOUT', 30),
    'retry' => [
        'times' => env('MOLONI_RETRY_TIMES', 1),
        'sleep' => env('MOLONI_RETRY_SLEEP', 500),
    ],
];
