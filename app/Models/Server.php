<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'host',
        'agent_enabled',
        'agent_token',
        'agent_version',
        'agent_last_seen_at',
        'agent_status',
        'agent_port',
        'agent_public_url',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'agent_enabled' => 'boolean',
            'agent_token' => 'encrypted',
            'agent_last_seen_at' => 'datetime',
            'agent_port' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ServerAgentCommand, $this> */
    public function agentCommands(): HasMany
    {
        return $this->hasMany(ServerAgentCommand::class);
    }

    /** @return HasMany<ServerCronjob, $this> */
    public function cronjobs(): HasMany
    {
        return $this->hasMany(ServerCronjob::class);
    }
}
