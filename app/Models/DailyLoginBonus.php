<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DailyLoginBonus extends Model
{
    protected $fillable = [
        'user_id',
        'streak_day',
        'reward_credits',
        'claimed_for_date',
        'claimed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'streak_day' => 'integer',
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
