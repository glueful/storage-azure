<?php

return [
    'disk' => [
        'driver' => 'azure',
        'container' => env('AZURE_STORAGE_CONTAINER', ''),
        'connection_string' => env('AZURE_STORAGE_CONNECTION_STRING', ''),
        'prefix' => env('AZURE_STORAGE_PREFIX', ''),
        'signed_ttl' => (int) env('AZURE_SIGNED_URL_TTL', 3600),
    ],
];
