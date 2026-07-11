<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankInvestment extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLAIMED = 'claimed';

    protected $fillable = [
        'user_id',
        'principal_amount',
        'interest_amount',
        'base_hourly_rate',
        'term_hours',
        'term_multiplier',
        'amount_multiplier',
        'starts_at',
        'matures_at',
        'claimed_at',
        'status',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'principal_amount' => 'integer',
            'interest_amount' => 'integer',
            'base_hourly_rate' => 'decimal:2',
            'term_hours' => 'integer',
            'term_multiplier' => 'decimal:2',
            'amount_multiplier' => 'decimal:2',
            'starts_at' => 'datetime',
            'matures_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<BankInvestment> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    public function isMatured(): bool
    {
        return $this->matures_at->isPast();
    }

    public function progressPercentage(): int
    {
        if ($this->status === self::STATUS_CLAIMED || $this->isMatured()) {
            return 100;
        }

        $startsAt = $this->starts_at->getTimestamp();
        $maturesAt = $this->matures_at->getTimestamp();
        $currentTime = now()->getTimestamp();

        if ($currentTime <= $startsAt) {
            return 0;
        }

        $duration = max(1, $maturesAt - $startsAt);
        $elapsed = $currentTime - $startsAt;

        return min(100, max(0, (int) floor(($elapsed / $duration) * 100)));
    }

    public function payoutAmount(): int
    {
        return $this->principal_amount + $this->interest_amount;
    }
}
