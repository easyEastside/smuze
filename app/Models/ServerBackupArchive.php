<?php

namespace App\Models;

use Database\Factories\ServerBackupArchiveFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerBackupArchive extends Model
{
    /** @use HasFactory<ServerBackupArchiveFactory> */
    use HasFactory;

    protected $fillable = [
        'server_backup_id',
        'filename',
        'type',
        'size_bytes',
        'storage_path',
        'storage',
        'status',
        'exit_code',
        'output',
        'error_output',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'exit_code' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ServerBackup, $this> */
    public function backup(): BelongsTo
    {
        return $this->belongsTo(ServerBackup::class, 'server_backup_id');
    }
}
