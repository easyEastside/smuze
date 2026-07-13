<?php

namespace App\Models;

use Database\Factories\ServerBackupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerBackup extends Model
{
    /** @use HasFactory<ServerBackupFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'user_id',
        'name',
        'type',
        'targets',
        'storage',
        's3_config',
        'schedule',
        'enabled',
        'retention_days',
        'last_status',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'targets' => 'array',
            's3_config' => 'encrypted:array',
            'enabled' => 'boolean',
            'retention_days' => 'integer',
            'last_run_at' => 'datetime',
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

    /** @return HasMany<ServerBackupArchive, $this> */
    public function archives(): HasMany
    {
        return $this->hasMany(ServerBackupArchive::class);
    }
}
