<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class QuestClaim extends Model
{
    public const DAILY_COMPLETION_BONUS = 'daily_completion_bonus';

    protected $fillable = [
        'user_id',
        'quest_key',
        'reward_credits',
        'claimed_for_date',
        'claimed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reward_credits' => 'integer',
            'claimed_for_date' => 'date',
            'claimed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphMany<CreditTransaction, $this> */
    public function creditTransactions(): MorphMany
    {
        return $this->morphMany(CreditTransaction::class, 'reference');
    }
}
