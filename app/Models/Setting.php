<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const BANK_BASE_HOURLY_INTEREST_RATE = 'bank_base_hourly_interest_rate';

    public const DEFAULT_BANK_BASE_HOURLY_INTEREST_RATE = '1.00';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function bankBaseHourlyInterestRate(): float
    {
        $value = static::query()
            ->where('key', self::BANK_BASE_HOURLY_INTEREST_RATE)
            ->value('value');

        return $value === null ? (float) self::DEFAULT_BANK_BASE_HOURLY_INTEREST_RATE : (float) $value;
    }

    public static function setBankBaseHourlyInterestRate(float $rate): void
    {
        static::query()->updateOrCreate(
            ['key' => self::BANK_BASE_HOURLY_INTEREST_RATE],
            ['value' => number_format($rate, 2, '.', '')],
        );
    }
}
