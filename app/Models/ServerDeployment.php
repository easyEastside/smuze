<?php

namespace App\Models;

use Database\Factories\ServerDeploymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerDeployment extends Model
{
    /** @use HasFactory<ServerDeploymentFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'user_id',
        'name',
        'repo_url',
        'target_path',
        'domain',
        'webserver',
        'php_version',
        'install_node',
        'run_build',
        'run_migrations',
        'write_env',
        'env',
        'last_status',
        'last_run_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'webserver' => 'none',
        'php_version' => '8.5',
        'install_node' => false,
        'run_build' => false,
        'run_migrations' => false,
        'write_env' => true,
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

    /** @return HasMany<ServerDeploymentRun, $this> */
    public function runs(): HasMany
    {
        return $this->hasMany(ServerDeploymentRun::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'install_node' => 'boolean',
            'run_build' => 'boolean',
            'run_migrations' => 'boolean',
            'write_env' => 'boolean',
            'env' => 'array',
            'last_run_at' => 'datetime',
        ];
    }
}
