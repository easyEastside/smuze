<?php

namespace App\Modules\Shop\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuyItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
