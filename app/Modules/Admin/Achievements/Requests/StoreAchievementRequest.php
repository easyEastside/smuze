<?php

namespace App\Modules\Admin\Achievements\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'unique:achievements,key'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'reward_credits' => ['required', 'integer', 'min:0'],
            'is_hidden' => ['sometimes', 'boolean'],
        ];
    }
}
