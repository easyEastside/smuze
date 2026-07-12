<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'shop_item_id',
        'quantity',
        'total_price',
        'source',
        'gifted_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'total_price' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ShopItem, $this> */
    public function shopItem(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function giftedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gifted_by');
    }

    /** @return MorphMany<CreditTransaction, $this> */
    public function creditTransactions(): MorphMany
    {
        return $this->morphMany(CreditTransaction::class, 'reference');
    }

    /** @param Builder<Purchase> $query */
    public function scopePurchased(Builder $query): Builder
    {
        return $query->where('source', 'purchase');
    }

    /** @param Builder<Purchase> $query */
    public function scopeGifted(Builder $query): Builder
    {
        return $query->where('source', 'gift');
    }
}
