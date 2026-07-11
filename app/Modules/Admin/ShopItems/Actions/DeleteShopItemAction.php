<?php

namespace App\Modules\Admin\ShopItems\Actions;

use App\Models\ShopItem;
use Illuminate\Support\Facades\Storage;

class DeleteShopItemAction
{
    public function handle(ShopItem $shopItem): void
    {
        if ($shopItem->image_path) {
            Storage::disk('public')->delete($shopItem->image_path);
        }

        $shopItem->delete();
    }
}
