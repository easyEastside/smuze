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
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'use_sudo' => 'boolean',
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
