<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'credentials' => 'encrypted',
            'key_content' => 'encrypted',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
