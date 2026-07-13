<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'server_id',
        'cpu_percent',
        'ram_percent',
        'ram_used_mb',
        'ram_total_mb',
        'disk_percent',
        'disk_used_mb',
        'disk_total_mb',
        'load',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
