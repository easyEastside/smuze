<?php

namespace App\Models\Traits;

use App\Models\Achievement;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @mixin User */
trait HasCredits
{
    /** @return HasMany<CreditTransaction, $this> */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function addCredits(int $amount, ?string $description = null, ?string $type = null, ?Model $reference = null): CreditTransaction
    {
        $this->increment('credits', $amount);

        $transaction = $this->creditTransactions()->create([
            'amount' => $amount,
            'description' => $description,
            'type' => $type ?? 'manual_adjustment',
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
        ]);

        if ($this->credits >= 1000) {
            $achievement = Achievement::query()->where('key', 'millionaire')->first();
            if ($achievement !== null && ! $this->achievements()->where('achievement_id', $achievement->id)->exists()) {
                $this->achievements()->attach($achievement->id, ['unlocked_at' => now()]);
            }
        }

        return $transaction;
    }

    public function deductCredits(int $amount, ?string $description = null, ?string $type = null, ?Model $reference = null): CreditTransaction
    {
        $this->decrement('credits', $amount);

        /** @var CreditTransaction */
        return $this->creditTransactions()->create([
            'amount' => -$amount,
            'description' => $description,
            'type' => $type ?? 'manual_adjustment',
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
        ]);
    }

    public function hasCredits(int $amount): bool
    {
        return $this->credits >= $amount;
    }
}
