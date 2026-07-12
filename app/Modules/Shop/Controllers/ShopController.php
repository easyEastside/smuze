<?php

namespace App\Modules\Shop\Controllers;

use App\Models\ShopItem;
use App\Modules\Shop\Actions\BuyItem;
use App\Modules\Shop\Requests\BuyItemRequest;
use Illuminate\View\View;

class ShopController
{
    public function index(): View
    {
        $items = ShopItem::active()->get();

        return view('modules.shop.index', compact('items'));
    }

    public function show(ShopItem $shopItem): View
    {
        return view('modules.shop.show', compact('shopItem'));
    }

    public function buy(BuyItemRequest $request, ShopItem $shopItem, BuyItem $buyItem): mixed
    {
        $buyItem->handle(
            item: $shopItem,
            user: $request->user(),
            quantity: $request->integer('quantity', 1),
        );

        return redirect()->route('shop.index')->with('status', 'Item purchased successfully.');
    }
}
