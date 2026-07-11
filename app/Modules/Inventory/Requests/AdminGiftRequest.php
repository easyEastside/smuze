<?php

namespace App\Modules\Inventory\Requests;

use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminGiftRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', Rule::exists(User::class, 'id')],
            'shop_item_id' => ['required', Rule::exists(ShopItem::class, 'id')],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
