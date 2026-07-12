<?php

namespace App\Modules\Admin\Achievements\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', Rule::unique('achievements', 'key')->ignore($this->route('achievement'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'reward_credits' => ['required', 'integer', 'min:0'],
            'is_hidden' => ['sometimes', 'boolean'],
        ];
    }
}
