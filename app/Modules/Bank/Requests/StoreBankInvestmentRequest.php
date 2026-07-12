<?php

namespace App\Modules\Bank\Requests;

use App\Modules\Bank\Actions\CreateBankInvestment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:10'],
            'term_hours' => ['required', 'integer', Rule::in(array_keys(CreateBankInvestment::TERM_MULTIPLIERS))],
        ];
    }
}
