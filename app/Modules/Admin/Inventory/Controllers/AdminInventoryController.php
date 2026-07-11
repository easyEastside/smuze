<?php

namespace App\Modules\Admin\Inventory\Controllers;

use App\Models\ShopItem;
use App\Models\User;
use App\Modules\Inventory\Requests\AdminGiftRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminInventoryController
{
    public function create(): View
    {
        $users = User::orderBy('name')->get();
        $items = ShopItem::active()->orderBy('name')->get();

        return view('modules.admin.inventory.create', compact('users', 'items'));
    }

    public function store(AdminGiftRequest $request): RedirectResponse
    {
        $item = ShopItem::findOrFail($request->integer('shop_item_id'));
        $recipient = User::findOrFail($request->integer('user_id'));
        $admin = $request->user();

        $recipient->purchases()->create([
            'shop_item_id' => $item->id,
            'quantity' => $request->integer('quantity', 1),
            'total_price' => 0,
            'source' => 'gift',
            'gifted_by' => $admin->id,
        ]);

        return to_route('admin.inventory.create')
            ->with('flash', ['success' => "Gifted {$item->name} to {$recipient->name}."]);
    }
}
