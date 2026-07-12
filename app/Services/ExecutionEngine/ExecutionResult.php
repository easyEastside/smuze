<?php

namespace App\Services\ExecutionEngine;

readonly class ExecutionResult
{
    public function __construct(
        public string $stdout,
        public string $stderr,
        public int $exitCode,
        public bool $success,
    ) {}
}
