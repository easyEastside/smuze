<?php

namespace App\Services;

readonly class SshResult
{
    public function __construct(
        public string $stdout,
        public string $stderr,
        public int $exitCode,
        public bool $success,
    ) {}
}
