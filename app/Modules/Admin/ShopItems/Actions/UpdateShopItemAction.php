<?php

namespace App\Modules\Admin\ShopItems\Actions;

use App\Models\ShopItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateShopItemAction
{
    /** @param array<string, mixed> $validated */
    public function handle(
        ShopItem $shopItem,
        array $validated,
        bool $isActive = true,
        ?UploadedFile $image = null,
        bool $removeImage = false,
    ): ShopItem {
        $data = [
            'name' => $validated['name'],
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => $isActive,
        ];

        if ($removeImage && $shopItem->image_path) {
            Storage::disk('public')->delete($shopItem->image_path);

            $data['image_path'] = null;
        }

        if ($image) {
            if ($shopItem->image_path) {
                Storage::disk('public')->delete($shopItem->image_path);
            }

            $data['image_path'] = $image->store('shop-items', 'public');
        }

        $shopItem->update($data);

        return $shopItem;
    }
}
