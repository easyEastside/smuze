<?php

namespace App\Models;

use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'reward_credits',
        'is_hidden',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reward_credits' => 'integer',
            'is_hidden' => 'boolean',
        ];
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }
}
