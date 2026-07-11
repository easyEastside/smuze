<?php

namespace App\Services;

readonly class SystemInfo
{
    public function __construct(
        public ?string $error = null,
        public ?string $hostname = null,
        public ?string $os = null,
        public ?string $uptime = null,
        public ?string $load = null,
        public ?int $cpuPercent = null,
        public ?int $ramTotalMb = null,
        public ?int $ramUsedMb = null,
        public ?int $ramPercent = null,
        public ?int $diskTotalMb = null,
        public ?int $diskUsedMb = null,
        public ?int $diskPercent = null,
        public ?string $phpVersion = null,
        public ?string $apacheVersion = null,
        public ?string $mysqlVersion = null,
        public ?string $nodeVersion = null,
        public ?string $nvmVersion = null,
        public ?string $composerVersion = null,
    ) {}

    public function hasError(): bool
    {
        return $this->error !== null && $this->error !== '';
    }

    public function errorMessage(): string
    {
        if ($this->error !== null && $this->error !== '') {
            return $this->error;
        }

        if ($this->hostname === null) {
            return 'Server konnte nicht erreicht werden.';
        }

        return '';
    }
}
