<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UseGroupRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'shop_item_id' => ['sometimes', 'integer', 'exists:shop_items,id'],
            'source' => ['sometimes', 'string', 'in:purchase,gift'],
            'purchase_id' => ['sometimes', 'integer', 'exists:purchases,id'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
