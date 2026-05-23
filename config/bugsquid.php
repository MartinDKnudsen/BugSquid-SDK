<?php

return [
    'endpoint'    => env('BUGSQUID_ENDPOINT'),
    'key'         => env('BUGSQUID_KEY'),
    'environment' => env('BUGSQUID_ENVIRONMENT', env('APP_ENV', 'production')),
    'release'     => env('BUGSQUID_RELEASE'),
    'server_name' => env('BUGSQUID_SERVER_NAME', gethostname()),
];
