<?php

namespace App\Modules\Admin\ShopItems\Controllers;

use App\Models\ShopItem;
use App\Modules\Admin\ShopItems\Actions\DeleteShopItemAction;
use App\Modules\Admin\ShopItems\Actions\StoreShopItemAction;
use App\Modules\Admin\ShopItems\Actions\UpdateShopItemAction;
use App\Modules\Admin\ShopItems\Requests\StoreShopItemRequest;
use App\Modules\Admin\ShopItems\Requests\UpdateShopItemRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminShopItemsController
{
    public function index(): View
    {
        $items = ShopItem::orderByDesc('created_at')->paginate(15);

        return view('modules.admin.shop-items.index', compact('items'));
    }

    public function create(): View
    {
        return view('modules.admin.shop-items.create');
    }

    public function store(StoreShopItemRequest $request, StoreShopItemAction $action): RedirectResponse
    {
        $item = $action->handle(
            validated: $request->validated(),
            isActive: $request->boolean('is_active', true),
            image: $request->file('image'),
        );

        return to_route('admin.shop-items.index')
            ->with('flash', ['success' => "Shop item {$item->name} created successfully."]);
    }

    public function edit(ShopItem $shopItem): View
    {
        return view('modules.admin.shop-items.edit', compact('shopItem'));
    }

    public function update(UpdateShopItemRequest $request, ShopItem $shopItem, UpdateShopItemAction $action): RedirectResponse
    {
        $action->handle(
            shopItem: $shopItem,
            validated: $request->validated(),
            isActive: $request->boolean('is_active', true),
            image: $request->file('image'),
            removeImage: $request->boolean('remove_image'),
        );

        return to_route('admin.shop-items.index')
            ->with('flash', ['success' => "Shop item {$shopItem->name} updated successfully."]);
    }

    public function destroy(ShopItem $shopItem, DeleteShopItemAction $action): RedirectResponse
    {
        $name = $shopItem->name;

        $action->handle($shopItem);

        return to_route('admin.shop-items.index')
            ->with('flash', ['success' => "Shop item {$name} deleted successfully."]);
    }
}
