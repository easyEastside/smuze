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
        'port',
        'username',
        'auth_type',
        'credentials',
        'key_path',
        'key_content',
        'use_sudo',
        'ssh_connect_timeout',
        'ssh_command_timeout',
        'ssh_control_persist',
        'ssh_server_alive_interval',
        'ssh_server_alive_count_max',
        'ssh_connection_attempts',
        'ssh_compression',
        'agent_enabled',
        'agent_token',
        'agent_version',
        'agent_last_seen_at',
        'agent_status',
        'agent_transport',
        'execution_driver',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'use_sudo' => 'boolean',
            'ssh_connect_timeout' => 'integer',
            'ssh_command_timeout' => 'integer',
            'ssh_control_persist' => 'integer',
            'ssh_server_alive_interval' => 'integer',
            'ssh_server_alive_count_max' => 'integer',
            'ssh_connection_attempts' => 'integer',
            'ssh_compression' => 'boolean',
            'agent_enabled' => 'boolean',
            'agent_token' => 'encrypted',
            'agent_last_seen_at' => 'datetime',
            'credentials' => 'encrypted',
            'key_content' => 'encrypted',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ServerCommand, $this> */
    public function commands(): HasMany
    {
        return $this->hasMany(ServerCommand::class);
    }
}
