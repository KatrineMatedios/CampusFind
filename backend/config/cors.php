<?php

$origins = array_values(array_filter(array_map('trim', explode(',', (string) env('FRONTEND_URL', '')))));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Allow the configured frontend origin(s). Falls back to "*" so local dev
    // and unconfigured deployments keep working. Set FRONTEND_URL in the
    // backend environment to your Vercel domain (comma-separated for several).
    'allowed_origins' => empty($origins) ? ['*'] : $origins,

    // Vercel preview deployments use unique subdomains, so allow them by pattern.
    'allowed_origins_patterns' => [
        '#^https://.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Authentication uses bearer tokens (Sanctum personal access tokens) stored
    // client-side, so no cookies/credentials are required cross-origin.
    'supports_credentials' => false,
];
