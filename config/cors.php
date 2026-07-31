<?php

return [

    /*
     * Cross-Origin Resource Sharing (CORS) settings for API routes.
     *
     * We scope CORS to the tracking API only (api/*). The only origin that
     * needs cross-origin access is the marketing site. Restricting allowed
     * origins prevents arbitrary sites from calling our JSON API from the
     * browser.
     *
     * The wildcard '*' is intentionally NOT used — it would expose the
     * tracking API to all origins and defeat the purpose of the restriction.
     */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'OPTIONS'],

    'allowed_origins' => [
        'https://hmcargoservices.com',
        'https://www.hmcargoservices.com',
    ],

    'allowed_origins_patterns' => [
        // Allow localhost on any port for local development
        '#^http://localhost(:\d+)?$#',
    ],

    'allowed_headers' => ['Content-Type', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
