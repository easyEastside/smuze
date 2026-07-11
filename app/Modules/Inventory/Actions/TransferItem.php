<?php

namespace App\Modules\Inventory\Actions;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TransferItem
{
    /**
     * @throws ValidationException
     */
    public function handle(Purchase $purchase, User $from, User $to, int $quantity = 1): void
    {
        if ($purchase->quantity < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'You do not have enough of this item to gift.']);
        }

        if ($from->is($to)) {
            throw ValidationException::withMessages(['recipient' => 'You cannot gift an item to yourself.']);
        }

        $remaining = $purchase->quantity - $quantity;

        if ($remaining > 0) {
            $purchase->update(['quantity' => $remaining]);
        } else {
            $purchase->delete();
        }

        Purchase::create([
            'user_id' => $to->id,
            'shop_item_id' => $purchase->shop_item_id,
            'quantity' => $quantity,
            'total_price' => 0,
            'source' => 'gift',
            'gifted_by' => $from->id,
        ]);
    }
}
