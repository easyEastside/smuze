<?php

namespace App\Modules\Admin\ShopItems\Actions;

use App\Models\ShopItem;
use Illuminate\Http\UploadedFile;

class StoreShopItemAction
{
    /** @param array<string, mixed> $validated */
    public function handle(array $validated, bool $isActive = true, ?UploadedFile $image = null): ShopItem
    {
        $data = [
            'name' => $validated['name'],
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => $isActive,
        ];

        if ($image) {
            $data['image_path'] = $image->store('shop-items', 'public');
        }

        $item = ShopItem::create($data);

        return $item;
    }
}
