<?php

return [
    'latest_version' => env('AGENT_LATEST_VERSION', '0.1.0'),
    'push_port' => env('AGENT_PUSH_PORT', 9300),
    'terminal_token_ttl' => env('AGENT_TERMINAL_TOKEN_TTL', 60),
    'binary_path' => storage_path('app/agent/smuze-agent'),
];
