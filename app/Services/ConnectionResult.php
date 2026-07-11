<?php

namespace App\Services;

readonly class ConnectionResult
{
    public function __construct(
        public bool $success,
        public float $latencyMs = 0,
        public ?string $errorMessage = null,
    ) {}
}
