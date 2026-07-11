<?php

namespace App\Modules\Inventory\Actions;

use App\Models\Purchase;
use App\Models\ShopItem;
use App\Models\User;

class AdminGiftItem
{
    public function handle(ShopItem $item, User $to, User $giftedBy, int $quantity = 1): Purchase
    {
        return Purchase::create([
            'user_id' => $to->id,
            'shop_item_id' => $item->id,
            'quantity' => $quantity,
            'total_price' => 0,
            'source' => 'gift',
            'gifted_by' => $giftedBy->id,
        ]);
    }
}
