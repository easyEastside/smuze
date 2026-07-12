<?php

namespace App\Modules\Inventory\Actions;

use App\Models\Purchase;
use App\Models\ShopItem;
use App\Models\User;

class GiftItem
{
    public function handle(ShopItem $item, User $to, int $quantity = 1, ?User $giftedBy = null): Purchase
    {
        $totalPrice = 0;

        return Purchase::create([
            'user_id' => $to->id,
            'shop_item_id' => $item->id,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
            'source' => 'gift',
            'gifted_by' => $giftedBy?->id,
        ]);
    }
}
