<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | O frontend é hospedado separadamente da API. As origens permitidas são
    | configuradas por FRONTEND_URL no ambiente de cada deploy. Para liberar
    | mais de uma origem, separe as URLs por vírgula.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FRONTEND_URL', '')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // A autenticação da API usa JWT no header Authorization, não cookies.
    'supports_credentials' => false,

];
