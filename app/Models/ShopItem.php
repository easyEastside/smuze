<?php

namespace App\Models;

use Database\Factories\ShopItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopItem extends Model
{
    /** @use HasFactory<ShopItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'short_description',
        'description',
        'price',
        'image_path',
        'is_active',
        'stock',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
            'stock' => 'integer',
        ];
    }

    /** @return HasMany<Purchase, $this> */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /** @param Builder<ShopItem> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isInStock(): bool
    {
        return $this->stock === null || $this->stock > 0;
    }

    public function hasSufficientStock(int $quantity): bool
    {
        return $this->stock === null || $this->stock >= $quantity;
    }
}
