<?php

namespace App\Modules\Server\Mysql\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sql' => ['required', 'string', 'min:10'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'sql.required' => 'SQL-Befehl ist erforderlich.',
            'sql.min' => 'SQL-Befehl ist zu kurz.',
        ];
    }
}
