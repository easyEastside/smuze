<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerAgentCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'user_id',
        'source',
        'command',
        'timeout',
        'use_sudo',
        'exit_code',
        'success',
        'duration_ms',
        'stdout',
        'stderr',
        'started_at',
        'finished_at',
    ];

    /** @return BelongsTo<Server, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'timeout' => 'integer',
            'use_sudo' => 'boolean',
            'exit_code' => 'integer',
            'success' => 'boolean',
            'duration_ms' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
