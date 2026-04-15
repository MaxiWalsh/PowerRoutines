<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    | Flutter mobile no usa CORS (no es un browser), pero lo configuramos
    | correctamente para cuando se agregue un frontend web o se cambie el
    | dominio en producción.
    |
    | En producción reemplazar 'allowed_origins' con el dominio real.
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('CORS_ALLOWED_ORIGIN', '*')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
