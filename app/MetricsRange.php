<?php

namespace App;

enum MetricsRange: string
{
    case LastHour = '1h';
    case Last24Hours = '24h';
    case Last7Days = '7d';

    public function since(): \DateTimeImmutable
    {
        return match ($this) {
            self::LastHour => now()->subHour()->toDateTimeImmutable(),
            self::Last24Hours => now()->subDay()->toDateTimeImmutable(),
            self::Last7Days => now()->subWeek()->toDateTimeImmutable(),
        };
    }
}
