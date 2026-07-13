<?php

namespace App\Models;

use Database\Factories\ServerCronjobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCronjob extends Model
{
    /** @use HasFactory<ServerCronjobFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'user_id',
        'name',
        'schedule',
        'command',
        'working_directory',
        'run_as',
        'enabled',
        'last_run_at',
        'last_exit_code',
        'last_stdout',
        'last_stderr',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_run_at' => 'datetime',
            'last_exit_code' => 'integer',
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
