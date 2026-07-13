<?php

namespace App\Models;

use Database\Factories\ServerDeploymentRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerDeploymentRun extends Model
{
    /** @use HasFactory<ServerDeploymentRunFactory> */
    use HasFactory;

    protected $fillable = [
        'server_deployment_id',
        'server_id',
        'user_id',
        'status',
        'exit_code',
        'output',
        'error_output',
        'duration_ms',
        'started_at',
        'finished_at',
    ];

    /** @return BelongsTo<ServerDeployment, $this> */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(ServerDeployment::class, 'server_deployment_id');
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'exit_code' => 'integer',
            'duration_ms' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
