<?php

namespace App\Modules\Inventory\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferItemRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', Rule::exists(User::class, 'id')],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function getRecipient(): User
    {
        return User::findOrFail($this->integer('recipient_id'));
    }
}
