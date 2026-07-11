<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MessageThread extends Model
{
    protected $fillable = [
        'participant_one_id',
        'participant_two_id',
        'subject',
    ];

    /** @return BelongsTo<User, $this> */
    public function participantOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_one_id');
    }

    /** @return BelongsTo<User, $this> */
    public function participantTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_two_id');
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /** @return HasOne<Message, $this> */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /** @param Builder<MessageThread> $query */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('participant_one_id', $user->id)
                ->orWhere('participant_two_id', $user->id);
        });
    }

    public function includes(User $user): bool
    {
        return $this->participant_one_id === $user->id || $this->participant_two_id === $user->id;
    }

    public function otherParticipant(User $user): User
    {
        return $this->participant_one_id === $user->id
            ? $this->participantTwo
            : $this->participantOne;
    }
}
