<?php

return [
    'websocket_url' => env('TERMINAL_WS_URL'),
    'websocket_port' => env('TERMINAL_WS_PORT', 8081),
    'secret' => env('TERMINAL_SHARED_SECRET', env('APP_KEY')),
    'token_ttl_seconds' => env('TERMINAL_TOKEN_TTL_SECONDS', 60),
];
