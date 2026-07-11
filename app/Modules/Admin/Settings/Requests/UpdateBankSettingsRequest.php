<?php

namespace App\Modules\Admin\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'bank_base_hourly_interest_rate' => ['required', 'numeric', 'min:0', 'max:10'],
        ];
    }
}
