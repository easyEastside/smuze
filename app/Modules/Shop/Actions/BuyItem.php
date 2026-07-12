<?php

namespace App\Modules\Shop\Actions;

use App\Models\Purchase;
use App\Models\ShopItem;
use App\Models\User;
use App\Modules\Achievements\Actions\UnlockAchievement;
use Illuminate\Validation\ValidationException;

class BuyItem
{
    /**
     * @throws ValidationException
     */
    public function handle(ShopItem $item, User $user, int $quantity = 1): Purchase
    {
        if (! $item->is_active) {
            throw ValidationException::withMessages(['item' => 'This item is not available for purchase.']);
        }

        if (! $item->hasSufficientStock($quantity)) {
            throw ValidationException::withMessages(['item' => 'This item is out of stock.']);
        }

        $totalPrice = $item->price * $quantity;

        if (! $user->hasCredits($totalPrice)) {
            throw ValidationException::withMessages(['credits' => 'You do not have enough credits.']);
        }

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'shop_item_id' => $item->id,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
        ]);

        $user->deductCredits(
            amount: $totalPrice,
            description: "Purchased: {$item->name}".($quantity > 1 ? " x{$quantity}" : ''),
            type: 'shop_purchase',
            reference: $purchase,
        );

        if ($item->stock !== null) {
            $item->decrement('stock', $quantity);
        }

        app(UnlockAchievement::class)->handle($user, 'first_purchase');

        if (Purchase::query()->where('user_id', $user->id)->count() >= 10) {
            app(UnlockAchievement::class)->handle($user, 'shopping_spree');
        }

        return $purchase;
    }
}
