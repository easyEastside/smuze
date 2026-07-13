<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\HasCredits;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar_path', 'credits', 'show_floating_terminal', 'write_debug_logs'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasCredits, HasFactory, HasRoles, Notifiable;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'show_floating_terminal' => 'boolean',
            'write_debug_logs' => 'boolean',
        ];
    }

    /** @return HasMany<Purchase, $this> */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /** @return HasMany<BankInvestment, $this> */
    public function bankInvestments(): HasMany
    {
        return $this->hasMany(BankInvestment::class);
    }

    /** @return HasMany<Message, $this> */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /** @return HasMany<SurveyResponse, $this> */
    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /** @return HasMany<QuestClaim, $this> */
    public function questClaims(): HasMany
    {
        return $this->hasMany(QuestClaim::class);
    }

    /** @return HasMany<DailyLoginBonus, $this> */
    public function dailyLoginBonuses(): HasMany
    {
        return $this->hasMany(DailyLoginBonus::class);
    }

    /** @return HasMany<Server, $this> */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /** @return HasMany<ServerAgentCommand, $this> */
    public function serverAgentCommands(): HasMany
    {
        return $this->hasMany(ServerAgentCommand::class);
    }

    /** @return HasMany<ServerCronjob, $this> */
    public function serverCronjobs(): HasMany
    {
        return $this->hasMany(ServerCronjob::class);
    }

    /** @return BelongsToMany<Achievement, $this> */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class)
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }
}
