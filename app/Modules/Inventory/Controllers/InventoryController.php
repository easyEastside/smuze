<?php

namespace App\Modules\Inventory\Controllers;

use App\Models\Purchase;
use App\Models\ShopItem;
use App\Modules\Inventory\Actions\TransferItem;
use App\Modules\Inventory\Requests\GiftGroupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController
{
    public function index(): View
    {
        $purchases = Purchase::query()
            ->with(['shopItem', 'giftedBy'])
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        [$gifts, $purchasesCollection] = $purchases->partition(fn ($p) => $p->source === 'gift');

        $groupedPurchases = $purchasesCollection
            ->groupBy('shop_item_id')
            ->map(function ($group) {
                return (object) [
                    'shopItem' => $group->first()->shopItem,
                    'source' => 'purchase',
                    'giftedBy' => null,
                    'total_quantity' => $group->sum('quantity'),
                    'last_acquired' => $group->max('created_at'),
                ];
            });

        $giftItems = $gifts->map(fn ($p) => (object) [
            'shopItem' => $p->shopItem,
            'source' => 'gift',
            'giftedBy' => $p->giftedBy,
            'total_quantity' => $p->quantity,
            'last_acquired' => $p->created_at,
            'purchase_id' => $p->id,
        ]);

        $items = $groupedPurchases
            ->concat($giftItems)
            ->sortByDesc('last_acquired')
            ->values();

        return view('modules.inventory.index', compact('items'));
    }

    public function gift(GiftGroupRequest $request, TransferItem $transferItem): RedirectResponse
    {
        $quantity = $request->integer('quantity', 1);

        if ($request->filled('purchase_id')) {
            $purchase = Purchase::findOrFail($request->integer('purchase_id'));

            if ($purchase->user_id !== $request->user()->id) {
                abort(403);
            }

            $transferItem->handle(
                purchase: $purchase,
                from: $request->user(),
                to: $request->getRecipient(),
                quantity: min($quantity, $purchase->quantity),
            );

            return redirect()->route('inventory.index')->with('status', 'Item gifted successfully.');
        }

        $shopItem = ShopItem::findOrFail($request->integer('shop_item_id'));

        $purchases = Purchase::query()
            ->where('user_id', $request->user()->id)
            ->where('shop_item_id', $shopItem->id)
            ->where('source', $request->input('source', 'purchase'))
            ->where('quantity', '>', 0)
            ->orderBy('created_at')
            ->get();

        $remaining = $quantity;

        foreach ($purchases as $purchase) {
            if ($remaining <= 0) {
                break;
            }

            $transferItem->handle(
                purchase: $purchase,
                from: $request->user(),
                to: $request->getRecipient(),
                quantity: min($remaining, $purchase->quantity),
            );

            $remaining -= $purchase->quantity;
        }

        if ($remaining > 0) {
            return redirect()->route('inventory.index')->withErrors(['quantity' => 'You do not have enough of this item to gift.']);
        }

        return redirect()->route('inventory.index')->with('status', 'Item gifted successfully.');
    }

    public function use(Request $request): RedirectResponse
    {
        $quantity = $request->integer('quantity', 1);

        if ($request->filled('purchase_id')) {
            $purchase = Purchase::findOrFail($request->integer('purchase_id'));

            if ($purchase->user_id !== $request->user()->id) {
                abort(403);
            }

            if ($purchase->quantity < $quantity) {
                return redirect()->route('inventory.index')->withErrors(['quantity' => 'You do not have enough of this item.']);
            }

            $remaining = $purchase->quantity - $quantity;

            if ($remaining > 0) {
                $purchase->update(['quantity' => $remaining]);
            } else {
                $purchase->delete();
            }

            return redirect()->route('inventory.index')->with('status', 'Item used successfully.');
        }

        $shopItem = ShopItem::findOrFail($request->integer('shop_item_id'));

        $purchases = Purchase::query()
            ->where('user_id', $request->user()->id)
            ->where('shop_item_id', $shopItem->id)
            ->where('source', $request->input('source', 'purchase'))
            ->where('quantity', '>', 0)
            ->orderBy('created_at')
            ->get();

        $available = $purchases->sum('quantity');

        if ($available < $quantity) {
            return redirect()->route('inventory.index')->withErrors(['quantity' => 'You do not have enough of this item.']);
        }

        $remaining = $quantity;

        foreach ($purchases as $purchase) {
            if ($remaining <= 0) {
                break;
            }

            $used = min($remaining, $purchase->quantity);
            $left = $purchase->quantity - $used;
            $remaining -= $used;

            if ($left > 0) {
                $purchase->update(['quantity' => $left]);
            } else {
                $purchase->delete();
            }
        }

        return redirect()->route('inventory.index')->with('status', 'Item used successfully.');
    }
}
