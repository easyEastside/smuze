<?php

namespace App\Modules\Server\Mysql\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'db_name' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_][A-Za-z0-9_-]{0,63}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'db_name.required' => 'Datenbankname ist erforderlich.',
            'db_name.regex' => 'Datenbankname darf nur Buchstaben, Zahlen, Unterstrich und Bindestrich enthalten.',
        ];
    }
}
