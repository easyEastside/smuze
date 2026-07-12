<?php

namespace App\Modules\Inventory\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GiftGroupRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'shop_item_id' => ['sometimes', 'integer', 'exists:shop_items,id'],
            'source' => ['sometimes', 'string', 'in:purchase,gift'],
            'purchase_id' => ['sometimes', 'integer', 'exists:purchases,id'],
            'recipient_id' => ['required', Rule::exists(User::class, 'id')],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function getRecipient(): User
    {
        return User::findOrFail($this->integer('recipient_id'));
    }
}
