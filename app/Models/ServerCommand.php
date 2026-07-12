<?php

namespace App\Models;

use Database\Factories\ServerCommandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCommand extends Model
{
    /** @use HasFactory<ServerCommandFactory> */
    use HasFactory;

    public const StatusQueued = 'queued';

    public const StatusRunning = 'running';

    public const StatusCompleted = 'completed';

    public const StatusFailed = 'failed';

    public const StatusTimeout = 'timeout';

    public const StatusCancelled = 'cancelled';

    protected $fillable = [
        'server_id',
        'user_id',
        'uuid',
        'command',
        'use_sudo',
        'timeout',
        'status',
        'stdout',
        'stderr',
        'exit_code',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'use_sudo' => 'boolean',
            'timeout' => 'integer',
            'exit_code' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

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
}
